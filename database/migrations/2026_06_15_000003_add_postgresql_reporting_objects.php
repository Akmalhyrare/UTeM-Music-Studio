<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the "Option B" PostgreSQL reporting/business objects:
 *   - Views:     v_inventory_low_stock, v_monthly_booking_summary, v_studio_utilization
 *   - Functions: fn_item_available_quantity, fn_studio_next_available_slot,
 *                fn_set_borrowing_overdue, fn_refresh_overdue_borrowings
 *   - Column + trigger: borrowings.is_overdue, trg_borrowings_set_overdue
 *   - Check constraints: bookings.booking_status, borrowings.borrow_status,
 *                        items.item_status, maintenances.maintenance_status
 *
 * Mirrors database/sql/db_objects_option_b.sql. No-op on non-PostgreSQL
 * connections (e.g. SQLite used in tests).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // ── VIEWS ──────────────────────────────────────────────────
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW v_inventory_low_stock AS
            SELECT
                i.item_id,
                i.item_name,
                c.category_id,
                c.category_name,
                i.quantity,
                i.available_quantity,
                i.condition_status,
                i.item_status,
                CASE
                    WHEN i.quantity > 0
                        THEN ROUND((i.available_quantity::numeric / i.quantity) * 100, 2)
                    ELSE 0
                END AS availability_pct
            FROM items i
            JOIN categories c ON c.category_id = i.category_id
            WHERE i.item_status = 'available'
              AND i.quantity > 0
              AND i.available_quantity <= (i.quantity * 0.2);
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW v_monthly_booking_summary AS
            SELECT
                to_char(b.booking_date, 'YYYY-MM') AS report_month,
                s.studio_id,
                s.studio_name,
                COUNT(*)                                                         AS total_bookings,
                COUNT(*) FILTER (WHERE b.booking_status = 'pending')             AS pending_count,
                COUNT(*) FILTER (WHERE b.booking_status = 'confirmed')           AS confirmed_count,
                COUNT(*) FILTER (WHERE b.booking_status = 'completed')           AS completed_count,
                COUNT(*) FILTER (WHERE b.booking_status = 'cancelled')           AS cancelled_count
            FROM bookings b
            JOIN studios s ON s.studio_id = b.studio_id
            GROUP BY to_char(b.booking_date, 'YYYY-MM'), s.studio_id, s.studio_name
            ORDER BY report_month DESC, s.studio_name;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW v_studio_utilization AS
            SELECT
                s.studio_id,
                s.studio_name,
                s.studio_type,
                COUNT(b.booking_id)                                                            AS total_bookings,
                COUNT(b.booking_id) FILTER (WHERE b.booking_status IN ('confirmed','completed')) AS active_bookings,
                CASE
                    WHEN (SELECT COUNT(*) FROM bookings WHERE booking_status IN ('confirmed','completed')) > 0
                        THEN ROUND(
                            100.0 * COUNT(b.booking_id) FILTER (WHERE b.booking_status IN ('confirmed','completed'))
                            / (SELECT COUNT(*) FROM bookings WHERE booking_status IN ('confirmed','completed'))
                        , 2)
                    ELSE 0
                END AS utilization_pct
            FROM studios s
            LEFT JOIN bookings b ON b.studio_id = s.studio_id
            GROUP BY s.studio_id, s.studio_name, s.studio_type
            ORDER BY utilization_pct DESC;
        SQL);

        // ── FUNCTIONS ──────────────────────────────────────────────
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_item_available_quantity(
                p_item_id           INTEGER,
                p_pickup_date       DATE,
                p_return_date       DATE,
                p_exclude_borrow_id INTEGER DEFAULT NULL
            )
            RETURNS INTEGER AS
            $$
            DECLARE
                v_stock    INTEGER;
                v_reserved INTEGER;
            BEGIN
                SELECT available_quantity
                  INTO v_stock
                  FROM items
                 WHERE item_id = p_item_id;

                IF v_stock IS NULL THEN
                    RETURN 0;
                END IF;

                SELECT COALESCE(SUM(bd.quantity_borrowed), 0)
                  INTO v_reserved
                  FROM borrowing_details bd
                  JOIN borrowings b ON b.borrow_id = bd.borrow_id
                 WHERE bd.item_id = p_item_id
                   AND b.borrow_status IN ('pending', 'reserved', 'collected')
                   AND b.pickup_date <= p_return_date
                   AND b.return_date >= p_pickup_date
                   AND (p_exclude_borrow_id IS NULL OR b.borrow_id <> p_exclude_borrow_id);

                RETURN GREATEST(0, v_stock - v_reserved);
            END;
            $$ LANGUAGE plpgsql STABLE;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_studio_next_available_slot(
                p_studio_id        INTEGER,
                p_date             DATE,
                p_duration_minutes INTEGER DEFAULT 60,
                p_operating_start  TIME    DEFAULT '08:00',
                p_operating_end    TIME    DEFAULT '22:00'
            )
            RETURNS TABLE(slot_start TIMESTAMP, slot_end TIMESTAMP) AS
            $$
            DECLARE
                v_cursor   TIMESTAMP;
                v_day_end  TIMESTAMP;
                v_duration INTERVAL;
                rec        RECORD;
            BEGIN
                v_cursor   := p_date + p_operating_start;
                v_day_end  := p_date + p_operating_end;
                v_duration := (p_duration_minutes || ' minutes')::INTERVAL;

                FOR rec IN (
                    SELECT busy_start, busy_end
                    FROM (
                        SELECT (booking_date + start_time)::TIMESTAMP AS busy_start,
                               (booking_date + end_time)::TIMESTAMP   AS busy_end
                          FROM bookings
                         WHERE studio_id = p_studio_id
                           AND booking_date = p_date
                           AND booking_status IN ('confirmed', 'completed', 'pending')

                        UNION ALL

                        SELECT start_at, end_at
                          FROM studio_unavailability
                         WHERE studio_id = p_studio_id
                           AND start_at::date <= p_date
                           AND end_at::date   >= p_date
                    ) busy
                    ORDER BY busy_start
                )
                LOOP
                    IF rec.busy_start > v_cursor
                       AND (rec.busy_start - v_cursor) >= v_duration THEN
                        slot_start := v_cursor;
                        slot_end   := v_cursor + v_duration;
                        RETURN NEXT;
                        RETURN;
                    END IF;

                    IF rec.busy_end > v_cursor THEN
                        v_cursor := rec.busy_end;
                    END IF;
                END LOOP;

                IF (v_day_end - v_cursor) >= v_duration THEN
                    slot_start := v_cursor;
                    slot_end   := v_cursor + v_duration;
                    RETURN NEXT;
                END IF;

                RETURN;
            END;
            $$ LANGUAGE plpgsql STABLE;
        SQL);

        // ── TRIGGER: borrowings.is_overdue ────────────────────────
        DB::unprepared('ALTER TABLE borrowings ADD COLUMN IF NOT EXISTS is_overdue BOOLEAN NOT NULL DEFAULT FALSE');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_set_borrowing_overdue()
            RETURNS TRIGGER AS
            $$
            BEGIN
                NEW.is_overdue := (
                    NEW.borrow_status = 'collected'
                    AND NEW.return_date IS NOT NULL
                    AND NEW.return_date < CURRENT_DATE
                );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS trg_borrowings_set_overdue ON borrowings');

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_borrowings_set_overdue
                BEFORE INSERT OR UPDATE ON borrowings
                FOR EACH ROW
                EXECUTE FUNCTION fn_set_borrowing_overdue();
        SQL);

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

        DB::unprepared('SELECT fn_refresh_overdue_borrowings()');

        // ── CHECK CONSTRAINTS ──────────────────────────────────────
        DB::unprepared('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS chk_bookings_booking_status');
        DB::unprepared("ALTER TABLE bookings ADD CONSTRAINT chk_bookings_booking_status CHECK (booking_status IN ('pending', 'confirmed', 'completed', 'cancelled'))");

        DB::unprepared('ALTER TABLE borrowings DROP CONSTRAINT IF EXISTS chk_borrowings_borrow_status');
        DB::unprepared("ALTER TABLE borrowings ADD CONSTRAINT chk_borrowings_borrow_status CHECK (borrow_status IN ('pending', 'reserved', 'collected', 'returned', 'rejected', 'cancelled'))");

        DB::unprepared('ALTER TABLE items DROP CONSTRAINT IF EXISTS chk_items_item_status');
        DB::unprepared("ALTER TABLE items ADD CONSTRAINT chk_items_item_status CHECK (item_status IN ('available', 'unavailable'))");

        DB::unprepared('ALTER TABLE maintenances DROP CONSTRAINT IF EXISTS chk_maintenances_maintenance_status');
        DB::unprepared("ALTER TABLE maintenances ADD CONSTRAINT chk_maintenances_maintenance_status CHECK (maintenance_status IN ('pending', 'resolved'))");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('ALTER TABLE maintenances DROP CONSTRAINT IF EXISTS chk_maintenances_maintenance_status');
        DB::unprepared('ALTER TABLE items DROP CONSTRAINT IF EXISTS chk_items_item_status');
        DB::unprepared('ALTER TABLE borrowings DROP CONSTRAINT IF EXISTS chk_borrowings_borrow_status');
        DB::unprepared('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS chk_bookings_booking_status');

        DB::unprepared('DROP TRIGGER IF EXISTS trg_borrowings_set_overdue ON borrowings');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_set_borrowing_overdue()');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_refresh_overdue_borrowings()');

        DB::unprepared('ALTER TABLE borrowings DROP COLUMN IF EXISTS is_overdue');

        DB::unprepared('DROP FUNCTION IF EXISTS fn_studio_next_available_slot(INTEGER, DATE, INTEGER, TIME, TIME)');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_item_available_quantity(INTEGER, DATE, DATE, INTEGER)');

        DB::unprepared('DROP VIEW IF EXISTS v_studio_utilization');
        DB::unprepared('DROP VIEW IF EXISTS v_monthly_booking_summary');
        DB::unprepared('DROP VIEW IF EXISTS v_inventory_low_stock');
    }
};
