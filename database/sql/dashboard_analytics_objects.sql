-- =====================================================================
-- Music Studio Management System
-- PostgreSQL Dashboard & Reporting Objects
--
-- Adds reporting-level stored procedures (PL/pgSQL functions), views,
-- and complex multi-table JOIN queries for dashboard analytics.
--
-- These objects are ADDITIVE ONLY:
--   - No existing tables, columns, or constraints are modified.
--   - No Laravel models/controllers need to change to add these.
--   - Complements (does not duplicate) the objects already created in
--     database/sql/db_objects_option_b.sql (v_inventory_low_stock,
--     v_monthly_booking_summary, v_studio_utilization,
--     fn_item_available_quantity, fn_studio_next_available_slot,
--     trg_borrowings_set_overdue).
--
-- Naming convention:
--   sp_*  -> PL/pgSQL "stored procedures" (implemented as functions
--            returning TABLE, queried via SELECT * FROM sp_xxx(...))
--            PostgreSQL's native PROCEDURE/CALL syntax cannot return a
--            result set directly, so reporting "procedures" are
--            implemented as set-returning FUNCTIONS — the standard
--            PostgreSQL pattern for dashboard queries.
--   vw_*  -> Reporting views
--
-- Target: PostgreSQL 13+. Safe to run multiple times
-- (CREATE OR REPLACE everywhere).
-- =====================================================================


-- =====================================================================
-- SECTION 1: STORED PROCEDURES (REPORTING FUNCTIONS)
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1.1 sp_monthly_active_bookings
-- Purpose : Monthly booking totals per studio, broken down by status.
-- Tables  : bookings, studios, students
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM sp_monthly_active_bookings(2026);


-- ---------------------------------------------------------------------
-- 1.2 sp_studio_utilization_rate
-- Purpose : Booked hours vs. available hours per studio over a date
--           range, expressed as a utilization percentage.
-- Tables  : studios, bookings
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM sp_studio_utilization_rate('2026-01-01', '2026-06-30');


-- ---------------------------------------------------------------------
-- 1.3 sp_maintenance_instruments_report
-- Purpose : Lists instruments currently flagged for maintenance/damage,
--           with category and reporting staff details.
-- Tables  : maintenances, items, categories, staff
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM sp_maintenance_instruments_report();


-- ---------------------------------------------------------------------
-- 1.4 sp_active_users_summary
-- Purpose : Active student/staff counts, plus how many of them have
--           made a booking or borrowing in the last p_days days.
-- Tables  : students, staff, bookings, borrowings
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM sp_active_users_summary(30);


-- ---------------------------------------------------------------------
-- 1.5 sp_pending_reservations
-- Purpose : List of pending/reserved equipment borrowings awaiting staff
--           action. Bookings have no pending approval workflow — they are
--           auto-confirmed on creation — so only borrowings are included.
-- Tables  : borrowings, students
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM sp_pending_reservations();


-- ---------------------------------------------------------------------
-- 1.6 sp_upcoming_pickups
-- Purpose : Equipment reservations approved and awaiting pickup within
--           the next p_days days (bridge table usage via
--           borrowing_details).
-- Tables  : borrowings, students, borrowing_details (bridge), items
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM sp_upcoming_pickups(7);


-- ---------------------------------------------------------------------
-- 1.7 sp_items_collected_report
-- Purpose : Items collected (currently out, or already returned) within
--           a date range, with category and handling staff.
-- Tables  : borrowings, students, borrowing_details (bridge), items,
--           categories, staff
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM sp_items_collected_report('2026-01-01', '2026-06-30');


-- ---------------------------------------------------------------------
-- 1.8 sp_maintenance_alerts
-- Purpose : Maintenance issues still pending, flagged CRITICAL once open
--           longer than p_overdue_days.
-- Tables  : maintenances, items, categories, staff
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM sp_maintenance_alerts(7);


-- =====================================================================
-- SECTION 2: VIEWS
-- =====================================================================

-- ---------------------------------------------------------------------
-- 2.1 vw_booking_overview
-- Purpose : Combined booking + student + studio + handling-staff view
--           for the booking management dashboard.
-- Tables  : bookings, students, studios, staff
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM vw_booking_overview WHERE booking_status = 'confirmed';


-- ---------------------------------------------------------------------
-- 2.2 vw_borrowing_items
-- Purpose : Flattened borrowing + item view via the borrowing_details
--           bridge table, for borrowing/inventory reports.
-- Tables  : borrowings, borrowing_details (bridge), items, categories,
--           students
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM vw_borrowing_items WHERE borrow_status = 'collected';


-- ---------------------------------------------------------------------
-- 2.3 vw_maintenance_tracking
-- Purpose : Maintenance issues joined with the item/category and the
--           return record that triggered the issue (if any).
-- Tables  : maintenances, items, categories, return_records, staff
-- ---------------------------------------------------------------------
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

-- Example: SELECT * FROM vw_maintenance_tracking WHERE maintenance_status = 'pending';


-- =====================================================================
-- SECTION 3: COMPLEX JOIN QUERIES (for dashboard charts)
-- =====================================================================

-- ---------------------------------------------------------------------
-- 3.1 Top borrowed items by category (bridge table aggregation)
-- ---------------------------------------------------------------------
SELECT
    c.category_name,
    i.item_name,
    SUM(bd.quantity_borrowed)   AS total_borrowed,
    COUNT(DISTINCT br.borrow_id) AS borrow_count
FROM borrowing_details bd
JOIN items i        ON i.item_id      = bd.item_id
JOIN categories c   ON c.category_id  = i.category_id
JOIN borrowings br  ON br.borrow_id   = bd.borrow_id
GROUP BY c.category_name, i.item_name
ORDER BY total_borrowed DESC
LIMIT 10;


-- ---------------------------------------------------------------------
-- 3.2 Student borrowing activity within a date range
--     (many-to-many via borrowing_details bridge table)
-- ---------------------------------------------------------------------
SELECT
    s.student_id,
    s.full_name,
    s.matric_no,
    i.item_name,
    bd.quantity_borrowed,
    br.borrow_status,
    br.pickup_date
FROM students s
JOIN borrowings br        ON br.student_id = s.student_id
JOIN borrowing_details bd ON bd.borrow_id  = br.borrow_id      -- bridge table
JOIN items i               ON i.item_id     = bd.item_id
WHERE br.pickup_date BETWEEN '2026-01-01' AND '2026-12-31'
ORDER BY s.full_name, br.pickup_date;


-- ---------------------------------------------------------------------
-- 3.3 Upcoming studio bookings dashboard (multi-table join)
-- ---------------------------------------------------------------------
SELECT
    st.studio_name,
    st.studio_type,
    b.booking_date,
    b.start_time,
    b.end_time,
    b.booking_status,
    s.full_name  AS booked_by,
    sf.full_name AS handled_by
FROM bookings b
JOIN studios st    ON st.studio_id  = b.studio_id
JOIN students s    ON s.student_id  = b.student_id
LEFT JOIN staff sf ON sf.staff_id   = b.staff_id
WHERE b.booking_date >= CURRENT_DATE
ORDER BY b.booking_date, b.start_time;


-- ---------------------------------------------------------------------
-- 3.4 Overdue borrowings with student contact info
--     (multi-table join + bridge table)
-- ---------------------------------------------------------------------
SELECT
    br.borrow_id,
    s.full_name  AS student_name,
    s.phone_no,
    s.email,
    i.item_name,
    bd.quantity_borrowed,
    br.pickup_date,
    br.return_date,
    (CURRENT_DATE - br.return_date) AS days_overdue,
    sf.full_name AS handled_by
FROM borrowings br
JOIN students s            ON s.student_id  = br.student_id
JOIN borrowing_details bd  ON bd.borrow_id  = br.borrow_id     -- bridge table
JOIN items i                ON i.item_id     = bd.item_id
LEFT JOIN staff sf          ON sf.staff_id  = br.staff_id
WHERE br.borrow_status = 'collected'
  AND br.return_date < CURRENT_DATE
ORDER BY days_overdue DESC;


-- =====================================================================
-- END OF SCRIPT
-- =====================================================================
