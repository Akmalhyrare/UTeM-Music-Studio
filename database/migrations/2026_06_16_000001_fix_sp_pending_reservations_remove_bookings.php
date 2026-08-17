<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Bookings have no pending approval workflow — they are auto-confirmed
        // on creation. Remove the booking UNION from sp_pending_reservations()
        // so Pending counts and the Pending Queue report only reflect
        // borrowings that genuinely require staff action.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION sp_pending_reservations()
            RETURNS TABLE (
                reservation_type TEXT,
                reference_id     BIGINT,
                requested_by     VARCHAR,
                resource_name    VARCHAR,
                request_date     DATE,
                status           VARCHAR
            ) AS
            $$
            BEGIN
                RETURN QUERY
                SELECT
                    'borrowing'::TEXT,
                    br.borrow_id,
                    s.full_name,
                    'Equipment Borrowing'::VARCHAR,
                    br.pickup_date,
                    br.borrow_status
                FROM borrowings br
                JOIN students s ON s.student_id = br.student_id
                WHERE br.borrow_status IN ('pending', 'reserved')
                ORDER BY br.pickup_date;
            END;
            $$ LANGUAGE plpgsql STABLE;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION sp_pending_reservations()
            RETURNS TABLE (
                reservation_type TEXT,
                reference_id     BIGINT,
                requested_by     VARCHAR,
                resource_name    VARCHAR,
                request_date     DATE,
                status           VARCHAR
            ) AS
            $$
            BEGIN
                RETURN QUERY
                SELECT * FROM (
                    SELECT
                        'booking'::TEXT       AS reservation_type,
                        b.booking_id          AS reference_id,
                        s.full_name           AS requested_by,
                        st.studio_name        AS resource_name,
                        b.booking_date        AS request_date,
                        b.booking_status      AS status
                    FROM bookings b
                    JOIN students s  ON s.student_id = b.student_id
                    JOIN studios  st ON st.studio_id  = b.studio_id
                    WHERE b.booking_status = 'pending'

                    UNION ALL

                    SELECT
                        'borrowing'::TEXT,
                        br.borrow_id,
                        s2.full_name,
                        'Equipment Borrowing'::VARCHAR,
                        br.pickup_date,
                        br.borrow_status
                    FROM borrowings br
                    JOIN students s2 ON s2.student_id = br.student_id
                    WHERE br.borrow_status IN ('pending', 'reserved')
                ) combined
                ORDER BY request_date;
            END;
            $$ LANGUAGE plpgsql STABLE;
        SQL);
    }
};
