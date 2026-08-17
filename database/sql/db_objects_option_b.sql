-- =====================================================================
-- Music Studio Management System
-- PostgreSQL Database Objects — "Option B" (Balanced Architecture)
--
-- This script converts selected application-level logic into native
-- PostgreSQL database objects:
--   1. VIEWS      — reporting (inventory low stock, monthly booking
--                    summary, studio utilization)
--   2. FUNCTIONS   — item availability calculation, studio next
--                    available slot calculation
--   3. TRIGGER     — auto-maintain borrowings.is_overdue
--   4. CHECK CONSTRAINTS — booking_status, borrow_status, item_status,
--                    maintenance_status
--
-- Target: PostgreSQL 13+ (tested against the schema produced by the
-- Laravel migrations in database/migrations/).
-- Safe to run multiple times (uses CREATE OR REPLACE / IF NOT EXISTS /
-- DROP ... IF EXISTS where applicable).
-- =====================================================================


-- =====================================================================
-- SECTION 1: VIEWS
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1.1 v_inventory_low_stock
-- Purpose : Lists available items whose stock has dropped to/below 20%
--           of their total quantity (low-stock alert list).
-- Tables  : items, categories
-- ---------------------------------------------------------------------
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


-- ---------------------------------------------------------------------
-- 1.2 v_monthly_booking_summary
-- Purpose : Monthly booking counts per studio, broken down by status.
--           Backs the Admin Report "Booking Report" and dashboard
--           monthly trends.
-- Tables  : bookings, studios
-- ---------------------------------------------------------------------
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


-- ---------------------------------------------------------------------
-- 1.3 v_studio_utilization
-- Purpose : Share of confirmed/completed bookings per studio relative
--           to all confirmed/completed bookings system-wide. Backs the
--           dashboard "Studio Utilization" pie/doughnut chart.
-- Tables  : studios, bookings
-- ---------------------------------------------------------------------
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
-- SECTION 2: FUNCTIONS
-- =====================================================================

-- ---------------------------------------------------------------------
-- 2.1 fn_item_available_quantity
-- Purpose : Returns how many units of an item are free for a given
--           pickup/return date range, accounting for overlapping
--           pending/reserved/collected borrowings.
-- Params  : p_item_id            - item to check
--           p_pickup_date        - requested pickup date
--           p_return_date        - requested return date
--           p_exclude_borrow_id  - optional borrow_id to exclude
--                                  (used when re-checking an existing
--                                  request, e.g. on approval)
-- Returns : INTEGER (never negative)
-- Tables  : items, borrowings, borrowing_details
-- ---------------------------------------------------------------------
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

-- Example usage:
-- SELECT fn_item_available_quantity(5, '2026-06-25', '2026-06-27');
-- SELECT fn_item_available_quantity(5, '2026-06-25', '2026-06-27', 42);


-- ---------------------------------------------------------------------
-- 2.2 fn_studio_next_available_slot
-- Purpose : Finds the next free time slot of the requested duration for
--           a studio on a given date, taking into account existing
--           pending/confirmed/completed bookings and any
--           studio_unavailability (maintenance/blocked) windows.
-- Params  : p_studio_id         - studio to check
--           p_date              - date to search within
--           p_duration_minutes  - required slot length (default 60)
--           p_operating_start   - studio opening time (default 08:00)
--           p_operating_end     - studio closing time (default 22:00)
-- Returns : TABLE(slot_start TIMESTAMP, slot_end TIMESTAMP)
--           Zero rows if no slot of the requested length is available
--           on that date.
-- Tables  : bookings, studio_unavailability
-- ---------------------------------------------------------------------
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
            -- Existing bookings (pending bookings also block the slot,
            -- mirroring StudioService::getNextAvailableSlot())
            SELECT (booking_date + start_time)::TIMESTAMP AS busy_start,
                   (booking_date + end_time)::TIMESTAMP   AS busy_end
              FROM bookings
             WHERE studio_id = p_studio_id
               AND booking_date = p_date
               AND booking_status IN ('confirmed', 'completed', 'pending')

            UNION ALL

            -- Maintenance / blocked windows overlapping this date
            SELECT start_at, end_at
              FROM studio_unavailability
             WHERE studio_id = p_studio_id
               AND start_at::date <= p_date
               AND end_at::date   >= p_date
        ) busy
        ORDER BY busy_start
    )
    LOOP
        -- Gap between current cursor and the next busy window
        IF rec.busy_start > v_cursor
           AND (rec.busy_start - v_cursor) >= v_duration THEN
            slot_start := v_cursor;
            slot_end   := v_cursor + v_duration;
            RETURN NEXT;
            RETURN;
        END IF;

        -- Push the cursor past this busy window if needed
        IF rec.busy_end > v_cursor THEN
            v_cursor := rec.busy_end;
        END IF;
    END LOOP;

    -- Remaining time after the last busy window until closing time
    IF (v_day_end - v_cursor) >= v_duration THEN
        slot_start := v_cursor;
        slot_end   := v_cursor + v_duration;
        RETURN NEXT;
    END IF;

    RETURN;
END;
$$ LANGUAGE plpgsql STABLE;

-- Example usage:
-- SELECT * FROM fn_studio_next_available_slot(2, '2026-06-20');
-- SELECT * FROM fn_studio_next_available_slot(2, '2026-06-20', 90, '09:00', '21:00');


-- =====================================================================
-- SECTION 3: TRIGGER — borrowings.is_overdue
-- =====================================================================

-- ---------------------------------------------------------------------
-- 3.1 Add the is_overdue column (idempotent)
-- ---------------------------------------------------------------------
ALTER TABLE borrowings
    ADD COLUMN IF NOT EXISTS is_overdue BOOLEAN NOT NULL DEFAULT FALSE;

-- ---------------------------------------------------------------------
-- 3.2 fn_set_borrowing_overdue (trigger function)
-- Purpose : Recomputes is_overdue whenever a borrowing row is inserted
--           or updated: TRUE when the items were collected but the
--           return_date has already passed and they have not yet been
--           returned.
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- 3.3 trg_borrowings_set_overdue
-- Event   : BEFORE INSERT OR UPDATE ON borrowings
-- ---------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_borrowings_set_overdue ON borrowings;

CREATE TRIGGER trg_borrowings_set_overdue
    BEFORE INSERT OR UPDATE ON borrowings
    FOR EACH ROW
    EXECUTE FUNCTION fn_set_borrowing_overdue();

-- ---------------------------------------------------------------------
-- 3.4 fn_refresh_overdue_borrowings (daily refresh helper)
-- Purpose : The trigger above only fires when a row is written, but a
--           collected borrowing becomes "overdue" purely because a day
--           has passed — with no row change. Call this function once a
--           day (e.g. via pg_cron or an external scheduler) to refresh
--           is_overdue for all currently-collected borrowings:
--               SELECT fn_refresh_overdue_borrowings();
-- ---------------------------------------------------------------------
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

-- Backfill existing rows immediately:
SELECT fn_refresh_overdue_borrowings();


-- =====================================================================
-- SECTION 4: CHECK CONSTRAINTS
-- =====================================================================

-- ---------------------------------------------------------------------
-- 4.1 bookings.booking_status
-- Allowed values used across BookingService / StudioService / reports:
--   pending, confirmed, completed, cancelled
-- ---------------------------------------------------------------------
ALTER TABLE bookings
    DROP CONSTRAINT IF EXISTS chk_bookings_booking_status;

ALTER TABLE bookings
    ADD CONSTRAINT chk_bookings_booking_status
    CHECK (booking_status IN ('pending', 'confirmed', 'completed', 'cancelled'));


-- ---------------------------------------------------------------------
-- 4.2 borrowings.borrow_status
-- Allowed values used across BorrowingService / reports:
--   pending, reserved, collected, returned, rejected, cancelled
-- ---------------------------------------------------------------------
ALTER TABLE borrowings
    DROP CONSTRAINT IF EXISTS chk_borrowings_borrow_status;

ALTER TABLE borrowings
    ADD CONSTRAINT chk_borrowings_borrow_status
    CHECK (borrow_status IN ('pending', 'reserved', 'collected', 'returned', 'rejected', 'cancelled'));


-- ---------------------------------------------------------------------
-- 4.3 items.item_status
-- Allowed values used by InventoryController validation:
--   available, unavailable
-- ---------------------------------------------------------------------
ALTER TABLE items
    DROP CONSTRAINT IF EXISTS chk_items_item_status;

ALTER TABLE items
    ADD CONSTRAINT chk_items_item_status
    CHECK (item_status IN ('available', 'unavailable'));


-- ---------------------------------------------------------------------
-- 4.4 maintenances.maintenance_status
-- Allowed values used by BorrowingService / MaintenanceController:
--   pending, resolved
-- ---------------------------------------------------------------------
ALTER TABLE maintenances
    DROP CONSTRAINT IF EXISTS chk_maintenances_maintenance_status;

ALTER TABLE maintenances
    ADD CONSTRAINT chk_maintenances_maintenance_status
    CHECK (maintenance_status IN ('pending', 'resolved'));


-- =====================================================================
-- END OF SCRIPT
-- =====================================================================
