-- =====================================================================
-- UTeM Music Studio Management System
-- FULL DATABASE OBJECT SCRIPT (Views, Functions, Procedures, Trigger)
--
-- Matches the 23-object inventory table in the FYP report:
--   1-6   Views
--   7-17  Functions (3 structural + 8 reporting)
--   18-22 Procedures
--   23    Trigger
--
-- Target: PostgreSQL 13+ (tested on PostgreSQL 18).
-- Safe to run multiple times (CREATE OR REPLACE / DROP ... IF EXISTS).
-- =====================================================================


-- =====================================================================
-- 1. v_inventory_low_stock (VIEW)
-- Tables: items, categories
-- =====================================================================
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


-- =====================================================================
-- 2. v_monthly_booking_summary (VIEW)
-- Tables: bookings, studios
-- =====================================================================
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


-- =====================================================================
-- 3. v_studio_utilization (VIEW)
-- Tables: studios, bookings
-- =====================================================================
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


-- =====================================================================
-- 4. vw_booking_overview (VIEW)
-- Tables: bookings, students, studios, staff
-- =====================================================================
CREATE OR REPLACE VIEW vw_booking_overview AS
SELECT
    b.booking_id,
    b.booking_date,
    b.start_time,
    b.end_time,
    b.booking_status,
    b.purpose,
    s.student_id,
    s.full_name  AS student_name,
    s.matric_no,
    st.studio_id,
    st.studio_name,
    st.studio_type,
    st.location,
    sf.staff_id,
    sf.full_name AS handled_by
FROM bookings b
JOIN students s   ON s.student_id = b.student_id
JOIN studios  st  ON st.studio_id = b.studio_id
LEFT JOIN staff sf ON sf.staff_id = b.staff_id;


-- =====================================================================
-- 5. vw_borrowing_items (VIEW)
-- Tables: borrowings, borrowing_details (bridge), items, categories, students
-- =====================================================================
CREATE OR REPLACE VIEW vw_borrowing_items AS
SELECT
    br.borrow_id,
    br.student_id,
    s.full_name AS student_name,
    br.borrow_status,
    br.pickup_date,
    br.return_date,
    bd.item_id,
    i.item_name,
    c.category_name,
    bd.quantity_borrowed,
    i.condition_status,
    i.available_quantity
FROM borrowings br
JOIN borrowing_details bd ON bd.borrow_id   = br.borrow_id   -- bridge table
JOIN items i              ON i.item_id      = bd.item_id
JOIN categories c         ON c.category_id  = i.category_id
JOIN students s           ON s.student_id   = br.student_id;


-- =====================================================================
-- 6. vw_maintenance_tracking (VIEW)
-- Tables: maintenances, items, categories, return_records, staff
-- =====================================================================
CREATE OR REPLACE VIEW vw_maintenance_tracking AS
SELECT
    m.maintenance_id,
    m.report_date,
    m.issue_type,
    m.maintenance_status,
    m.description,
    i.item_id,
    i.item_name,
    c.category_name,
    rr.return_id,
    rr.item_condition,
    rr.quantity_returned,
    rr.return_date,
    sf.staff_id,
    sf.full_name AS reported_by
FROM maintenances m
JOIN items i               ON i.item_id     = m.item_id
JOIN categories c          ON c.category_id = i.category_id
LEFT JOIN return_records rr ON rr.return_id = m.return_id
LEFT JOIN staff sf          ON sf.staff_id  = m.staff_id;


-- =====================================================================
-- 7. fn_item_available_quantity (FUNCTION - scalar)
-- Tables: items, borrowing_details, borrowings
-- =====================================================================
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


-- =====================================================================
-- 8. fn_studio_next_available_slot (FUNCTION - table)
-- Tables: studios, bookings, studio_unavailability
-- =====================================================================
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


-- =====================================================================
-- 9. fn_set_borrowing_overdue (FUNCTION - trigger)
-- Tables: borrowings
-- =====================================================================
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


-- =====================================================================
-- 10. sp_monthly_active_bookings (FUNCTION - table)
-- Tables: bookings, studios, students
-- =====================================================================
CREATE OR REPLACE FUNCTION sp_monthly_active_bookings(
    p_year INT DEFAULT EXTRACT(YEAR FROM CURRENT_DATE)::INT
)
RETURNS TABLE (
    booking_month   TEXT,
    studio_id       BIGINT,
    studio_name     VARCHAR,
    total_bookings  BIGINT,
    confirmed_count BIGINT,
    completed_count BIGINT,
    cancelled_count BIGINT
) AS
$$
BEGIN
    RETURN QUERY
    SELECT
        TO_CHAR(b.booking_date, 'YYYY-MM')                          AS booking_month,
        st.studio_id,
        st.studio_name,
        COUNT(*)                                                     AS total_bookings,
        COUNT(*) FILTER (WHERE b.booking_status = 'confirmed')       AS confirmed_count,
        COUNT(*) FILTER (WHERE b.booking_status = 'completed')       AS completed_count,
        COUNT(*) FILTER (WHERE b.booking_status = 'cancelled')       AS cancelled_count
    FROM bookings b
    JOIN studios  st ON st.studio_id  = b.studio_id
    JOIN students s  ON s.student_id  = b.student_id
    WHERE EXTRACT(YEAR FROM b.booking_date) = p_year
    GROUP BY booking_month, st.studio_id, st.studio_name
    ORDER BY booking_month, st.studio_name;
END;
$$ LANGUAGE plpgsql STABLE;


-- =====================================================================
-- 11. sp_studio_utilization_rate (FUNCTION - table)
-- Tables: studios, bookings
-- =====================================================================
CREATE OR REPLACE FUNCTION sp_studio_utilization_rate(
    p_start       DATE,
    p_end         DATE,
    p_daily_hours NUMERIC DEFAULT 12
)
RETURNS TABLE (
    studio_id        BIGINT,
    studio_name      VARCHAR,
    studio_type      VARCHAR,
    total_bookings   BIGINT,
    booked_hours     NUMERIC,
    available_hours  NUMERIC,
    utilization_rate NUMERIC
) AS
$$
BEGIN
    RETURN QUERY
    SELECT
        st.studio_id,
        st.studio_name,
        st.studio_type,
        COUNT(b.booking_id)                                                              AS total_bookings,
        COALESCE(SUM(EXTRACT(EPOCH FROM (b.end_time - b.start_time)) / 3600.0), 0)       AS booked_hours,
        ((p_end - p_start) + 1) * p_daily_hours                                          AS available_hours,
        ROUND(
            COALESCE(SUM(EXTRACT(EPOCH FROM (b.end_time - b.start_time)) / 3600.0), 0)
            / (((p_end - p_start) + 1) * p_daily_hours) * 100, 2
        )                                                                                AS utilization_rate
    FROM studios st
    LEFT JOIN bookings b
           ON b.studio_id = st.studio_id
          AND b.booking_date BETWEEN p_start AND p_end
          AND b.booking_status IN ('confirmed', 'completed')
    GROUP BY st.studio_id, st.studio_name, st.studio_type
    ORDER BY utilization_rate DESC;
END;
$$ LANGUAGE plpgsql STABLE;


-- =====================================================================
-- 12. sp_maintenance_instruments_report (FUNCTION - table)
-- Tables: maintenances, items, categories, staff
-- =====================================================================
CREATE OR REPLACE FUNCTION sp_maintenance_instruments_report()
RETURNS TABLE (
    maintenance_id     BIGINT,
    item_id            BIGINT,
    item_name          VARCHAR,
    category_name      VARCHAR,
    issue_type         VARCHAR,
    maintenance_status VARCHAR,
    report_date        DATE,
    reported_by        VARCHAR
) AS
$$
BEGIN
    RETURN QUERY
    SELECT
        m.maintenance_id,
        i.item_id,
        i.item_name,
        c.category_name,
        m.issue_type,
        m.maintenance_status,
        m.report_date,
        sf.full_name AS reported_by
    FROM maintenances m
    JOIN items i      ON i.item_id     = m.item_id
    JOIN categories c ON c.category_id = i.category_id
    LEFT JOIN staff sf ON sf.staff_id  = m.staff_id
    WHERE m.maintenance_status = 'pending'
    ORDER BY m.report_date DESC;
END;
$$ LANGUAGE plpgsql STABLE;


-- =====================================================================
-- 13. sp_active_users_summary (FUNCTION - table)
-- Tables: students, staff, bookings, borrowings
-- =====================================================================
CREATE OR REPLACE FUNCTION sp_active_users_summary(
    p_days INT DEFAULT 30
)
RETURNS TABLE (
    user_type        TEXT,
    total_active     BIGINT,
    active_in_period BIGINT
) AS
$$
BEGIN
    RETURN QUERY
    SELECT
        'student'::TEXT,
        (SELECT COUNT(*) FROM students WHERE status = 'active'),
        COUNT(DISTINCT s.student_id)
    FROM students s
    LEFT JOIN bookings   b  ON b.student_id  = s.student_id AND b.created_at  >= NOW() - (p_days || ' days')::INTERVAL
    LEFT JOIN borrowings br ON br.student_id = s.student_id AND br.created_at >= NOW() - (p_days || ' days')::INTERVAL
    WHERE s.status = 'active'
      AND (b.booking_id IS NOT NULL OR br.borrow_id IS NOT NULL)

    UNION ALL

    SELECT
        'staff'::TEXT,
        (SELECT COUNT(*) FROM staff WHERE status = 'active'),
        COUNT(DISTINCT sf.staff_id)
    FROM staff sf
    LEFT JOIN bookings   b  ON b.staff_id  = sf.staff_id AND b.created_at  >= NOW() - (p_days || ' days')::INTERVAL
    LEFT JOIN borrowings br ON br.staff_id = sf.staff_id AND br.created_at >= NOW() - (p_days || ' days')::INTERVAL
    WHERE sf.status = 'active'
      AND (b.booking_id IS NOT NULL OR br.borrow_id IS NOT NULL);
END;
$$ LANGUAGE plpgsql STABLE;


-- =====================================================================
-- 14. sp_pending_reservations (FUNCTION - table)
-- Tables: borrowings, students
-- Note: bookings have no pending approval workflow (auto-confirmed on
-- creation), so only borrowings appear in pending counts.
-- =====================================================================
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


-- =====================================================================
-- 15. sp_upcoming_pickups (FUNCTION - table)
-- Tables: borrowings, students, borrowing_details (bridge), items
-- =====================================================================
CREATE OR REPLACE FUNCTION sp_upcoming_pickups(
    p_days INT DEFAULT 7
)
RETURNS TABLE (
    borrow_id     BIGINT,
    student_name  VARCHAR,
    matric_no     VARCHAR,
    pickup_date   DATE,
    return_date   DATE,
    item_name     VARCHAR,
    quantity      INTEGER,
    borrow_status VARCHAR
) AS
$$
BEGIN
    RETURN QUERY
    SELECT
        br.borrow_id,
        s.full_name,
        s.matric_no,
        br.pickup_date,
        br.return_date,
        i.item_name,
        bd.quantity_borrowed,
        br.borrow_status
    FROM borrowings br
    JOIN students s            ON s.student_id  = br.student_id
    JOIN borrowing_details bd  ON bd.borrow_id  = br.borrow_id   -- bridge table
    JOIN items i               ON i.item_id     = bd.item_id
    WHERE br.borrow_status = 'reserved'
      AND br.pickup_date BETWEEN CURRENT_DATE AND CURRENT_DATE + p_days
    ORDER BY br.pickup_date, br.borrow_id;
END;
$$ LANGUAGE plpgsql STABLE;


-- =====================================================================
-- 16. sp_items_collected_report (FUNCTION - table)
-- Tables: borrowings, students, borrowing_details (bridge), items, categories, staff
-- =====================================================================
CREATE OR REPLACE FUNCTION sp_items_collected_report(
    p_start DATE,
    p_end   DATE
)
RETURNS TABLE (
    borrow_id     BIGINT,
    student_name  VARCHAR,
    item_name     VARCHAR,
    category_name VARCHAR,
    quantity      INTEGER,
    collected_at  TIMESTAMP,
    collected_by  VARCHAR
) AS
$$
BEGIN
    RETURN QUERY
    SELECT
        br.borrow_id,
        s.full_name,
        i.item_name,
        c.category_name,
        bd.quantity_borrowed,
        br.collected_at,
        sf.full_name
    FROM borrowings br
    JOIN students s           ON s.student_id  = br.student_id
    JOIN borrowing_details bd ON bd.borrow_id  = br.borrow_id    -- bridge table
    JOIN items i              ON i.item_id     = bd.item_id
    JOIN categories c         ON c.category_id = i.category_id
    LEFT JOIN staff sf         ON sf.staff_id  = br.collected_by
    WHERE br.borrow_status IN ('collected', 'returned')
      AND br.collected_at::DATE BETWEEN p_start AND p_end
    ORDER BY br.collected_at DESC;
END;
$$ LANGUAGE plpgsql STABLE;


-- =====================================================================
-- 17. sp_maintenance_alerts (FUNCTION - table)
-- Tables: maintenances, items, categories, staff
-- =====================================================================
CREATE OR REPLACE FUNCTION sp_maintenance_alerts(
    p_overdue_days INT DEFAULT 7
)
RETURNS TABLE (
    maintenance_id BIGINT,
    item_name      VARCHAR,
    category_name  VARCHAR,
    issue_type     VARCHAR,
    report_date    DATE,
    days_open      INT,
    alert_level    TEXT,
    reported_by    VARCHAR
) AS
$$
BEGIN
    RETURN QUERY
    SELECT
        m.maintenance_id,
        i.item_name,
        c.category_name,
        m.issue_type,
        m.report_date,
        (CURRENT_DATE - m.report_date)::INT AS days_open,
        CASE
            WHEN (CURRENT_DATE - m.report_date) > p_overdue_days THEN 'CRITICAL'
            ELSE 'NORMAL'
        END AS alert_level,
        sf.full_name
    FROM maintenances m
    JOIN items i      ON i.item_id     = m.item_id
    JOIN categories c ON c.category_id = i.category_id
    LEFT JOIN staff sf ON sf.staff_id  = m.staff_id
    WHERE m.maintenance_status = 'pending'
    ORDER BY days_open DESC;
END;
$$ LANGUAGE plpgsql STABLE;


-- =====================================================================
-- 18. sp_refresh_overdue_borrowings (PROCEDURE)
-- Tables: borrowings
-- =====================================================================
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


-- =====================================================================
-- 19. sp_collect_borrowing (PROCEDURE)
-- Tables: borrowings
-- =====================================================================
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


-- =====================================================================
-- 20. sp_approve_borrowing (PROCEDURE)
-- Tables: borrowings, borrowing_details, items
-- =====================================================================
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


-- =====================================================================
-- 21. sp_process_borrowing_return (PROCEDURE)
-- Tables: borrowings, borrowing_details, return_records, items, maintenances
-- =====================================================================
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


-- =====================================================================
-- 22. sp_display_studio_utilization (PROCEDURE - REFCURSOR demo)
-- Tables: studios, bookings
-- =====================================================================
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


-- =====================================================================
-- 23. trg_borrowings_set_overdue (TRIGGER)
-- Tables: borrowings
-- Fires fn_set_borrowing_overdue() (object #9) BEFORE INSERT OR UPDATE
-- =====================================================================
DROP TRIGGER IF EXISTS trg_borrowings_set_overdue ON borrowings;

CREATE TRIGGER trg_borrowings_set_overdue
    BEFORE INSERT OR UPDATE ON borrowings
    FOR EACH ROW
    EXECUTE FUNCTION fn_set_borrowing_overdue();


-- =====================================================================
-- END OF SCRIPT
-- =====================================================================
