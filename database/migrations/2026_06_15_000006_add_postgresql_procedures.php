<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Replace the function-based overdue refresh with a true PROCEDURE —
        // this is a batch write/maintenance operation with no queryable
        // result set, so CALL is the correct invocation form.
        DB::unprepared('DROP FUNCTION IF EXISTS fn_refresh_overdue_borrowings()');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE PROCEDURE sp_refresh_overdue_borrowings(OUT p_updated INTEGER)
            LANGUAGE plpgsql
            AS $$
            BEGIN
                UPDATE borrowings
                   SET is_overdue = (
                        borrow_status = 'collected'
                        AND return_date IS NOT NULL
                        AND return_date < CURRENT_DATE
                   )
                 WHERE borrow_status = 'collected';

                GET DIAGNOSTICS p_updated = ROW_COUNT;
            END;
            $$;
        SQL);

        DB::unprepared('CALL sp_refresh_overdue_borrowings(NULL)');

        // Guarded status transition: reserved -> collected
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE PROCEDURE sp_collect_borrowing(
                IN  p_borrow_id INTEGER,
                IN  p_staff_id  INTEGER,
                OUT p_success   BOOLEAN,
                OUT p_message   TEXT
            )
            LANGUAGE plpgsql
            AS $$
            DECLARE
                v_status borrowings.borrow_status%TYPE;
            BEGIN
                SELECT borrow_status INTO v_status
                  FROM borrowings
                 WHERE borrow_id = p_borrow_id
                 FOR UPDATE;

                IF NOT FOUND THEN
                    p_success := FALSE;
                    p_message := 'Borrowing request not found.';
                    RETURN;
                END IF;

                IF v_status <> 'reserved' THEN
                    p_success := FALSE;
                    p_message := 'Only reserved requests can be marked as collected.';
                    RETURN;
                END IF;

                UPDATE borrowings
                   SET borrow_status = 'collected',
                       collected_at  = NOW(),
                       collected_by  = p_staff_id
                 WHERE borrow_id = p_borrow_id;

                p_success := TRUE;
                p_message := 'Items marked as collected.';
            END;
            $$;
        SQL);

        // Approval with row locking + per-item availability validation
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE PROCEDURE sp_approve_borrowing(
                IN  p_borrow_id INTEGER,
                IN  p_staff_id  INTEGER,
                OUT p_success   BOOLEAN,
                OUT p_message   TEXT
            )
            LANGUAGE plpgsql
            AS $$
            DECLARE
                v_status      borrowings.borrow_status%TYPE;
                v_pickup_date borrowings.pickup_date%TYPE;
                v_return_date borrowings.return_date%TYPE;
                v_detail      RECORD;
                v_available   INTEGER;
            BEGIN
                SELECT borrow_status, pickup_date, return_date
                  INTO v_status, v_pickup_date, v_return_date
                  FROM borrowings
                 WHERE borrow_id = p_borrow_id
                 FOR UPDATE;

                IF NOT FOUND THEN
                    p_success := FALSE;
                    p_message := 'Borrowing request not found.';
                    RETURN;
                END IF;

                IF v_status <> 'pending' THEN
                    p_success := FALSE;
                    p_message := 'Only pending requests can be approved.';
                    RETURN;
                END IF;

                FOR v_detail IN
                    SELECT bd.item_id, bd.quantity_borrowed, i.item_name
                      FROM borrowing_details bd
                      JOIN items i ON i.item_id = bd.item_id
                     WHERE bd.borrow_id = p_borrow_id
                     FOR UPDATE OF i
                LOOP
                    v_available := fn_item_available_quantity(
                        v_detail.item_id::INTEGER, v_pickup_date, v_return_date, p_borrow_id
                    );

                    IF v_available < v_detail.quantity_borrowed THEN
                        p_success := FALSE;
                        p_message := format(
                            'Only %s unit(s) of "%s" available for the selected dates.',
                            v_available, v_detail.item_name
                        );
                        RETURN;
                    END IF;
                END LOOP;

                UPDATE borrowings
                   SET borrow_status = 'reserved',
                       staff_id      = p_staff_id
                 WHERE borrow_id = p_borrow_id;

                p_success := TRUE;
                p_message := 'Borrowing request approved and items issued.';
            END;
            $$;
        SQL);

        // Multi-table transactional return processing (JSONB input)
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE PROCEDURE sp_process_borrowing_return(
                IN  p_borrow_id INTEGER,
                IN  p_staff_id  INTEGER,
                IN  p_returns   JSONB,
                OUT p_success   BOOLEAN,
                OUT p_message   TEXT
            )
            LANGUAGE plpgsql
            AS $$
            DECLARE
                v_status         borrowings.borrow_status%TYPE;
                v_item           JSONB;
                v_return_id      INTEGER;
                v_total_borrowed NUMERIC;
                v_total_returned NUMERIC;
            BEGIN
                SELECT borrow_status INTO v_status
                  FROM borrowings
                 WHERE borrow_id = p_borrow_id
                 FOR UPDATE;

                IF NOT FOUND THEN
                    p_success := FALSE;
                    p_message := 'Borrowing request not found.';
                    RETURN;
                END IF;

                IF v_status <> 'collected' THEN
                    p_success := FALSE;
                    p_message := 'Only collected borrowings can be returned.';
                    RETURN;
                END IF;

                FOR v_item IN SELECT * FROM jsonb_array_elements(p_returns)
                LOOP
                    INSERT INTO return_records (
                        borrow_id, item_id, staff_id, return_date,
                        quantity_returned, item_condition, return_status, damage_note
                    ) VALUES (
                        p_borrow_id,
                        (v_item->>'item_id')::INTEGER,
                        p_staff_id,
                        CURRENT_DATE,
                        (v_item->>'quantity_returned')::INTEGER,
                        v_item->>'item_condition',
                        'completed',
                        v_item->>'damage_note'
                    ) RETURNING return_id INTO v_return_id;

                    IF v_item->>'item_condition' <> 'good' THEN
                        UPDATE items
                           SET available_quantity = available_quantity - (v_item->>'quantity_returned')::INTEGER
                         WHERE item_id = (v_item->>'item_id')::INTEGER;

                        INSERT INTO maintenances (item_id, staff_id, return_id, report_date, issue_type, description, maintenance_status)
                        VALUES (
                            (v_item->>'item_id')::INTEGER,
                            p_staff_id,
                            v_return_id,
                            CURRENT_DATE,
                            CASE WHEN v_item->>'item_condition' = 'lost' THEN 'lost' ELSE 'damage' END,
                            v_item->>'damage_note',
                            'pending'
                        );
                    END IF;
                END LOOP;

                SELECT COALESCE(SUM(quantity_borrowed), 0) INTO v_total_borrowed
                  FROM borrowing_details WHERE borrow_id = p_borrow_id;

                SELECT COALESCE(SUM(quantity_returned), 0) INTO v_total_returned
                  FROM return_records WHERE borrow_id = p_borrow_id;

                IF v_total_returned >= v_total_borrowed THEN
                    UPDATE borrowings
                       SET borrow_status = 'returned',
                           returned_at   = NOW(),
                           returned_by   = p_staff_id
                     WHERE borrow_id = p_borrow_id;
                END IF;

                p_success := TRUE;
                p_message := 'Return processed successfully.';
            END;
            $$;
        SQL);

        // Display-data demo via REFCURSOR
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE PROCEDURE sp_display_studio_utilization(
                IN  p_start DATE,
                IN  p_end   DATE,
                OUT p_cursor refcursor
            )
            LANGUAGE plpgsql
            AS $$
            BEGIN
                p_cursor := 'studio_util_cursor';

                OPEN p_cursor FOR
                    SELECT s.studio_id,
                           s.studio_name,
                           COUNT(b.booking_id) AS total_bookings,
                           ROUND(
                               COUNT(b.booking_id) * 100.0
                               / NULLIF((SELECT COUNT(*) FROM bookings
                                          WHERE booking_date BETWEEN p_start AND p_end
                                            AND booking_status IN ('confirmed','completed')), 0),
                               2
                           ) AS utilization_percent
                    FROM studios s
                    LEFT JOIN bookings b
                           ON b.studio_id = s.studio_id
                          AND b.booking_date BETWEEN p_start AND p_end
                          AND b.booking_status IN ('confirmed', 'completed')
                    GROUP BY s.studio_id, s.studio_name
                    ORDER BY total_bookings DESC;
            END;
            $$;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_display_studio_utilization(DATE, DATE, refcursor)');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_process_borrowing_return(INTEGER, INTEGER, JSONB, BOOLEAN, TEXT)');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_approve_borrowing(INTEGER, INTEGER, BOOLEAN, TEXT)');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_collect_borrowing(INTEGER, INTEGER, BOOLEAN, TEXT)');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_refresh_overdue_borrowings(INTEGER)');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_refresh_overdue_borrowings()
            RETURNS INTEGER AS
            $$
            DECLARE
                v_updated INTEGER;
            BEGIN
                UPDATE borrowings
                   SET is_overdue = (
                        borrow_status = 'collected'
                        AND return_date IS NOT NULL
                        AND return_date < CURRENT_DATE
                   )
                 WHERE borrow_status = 'collected';

                GET DIAGNOSTICS v_updated = ROW_COUNT;
                RETURN v_updated;
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }
};
