-- =====================================================================
-- UTeM Music Studio — Inventory & Management System
-- Physical Database Design (DDL) — current implemented schema
-- PostgreSQL 18
-- =====================================================================

CREATE EXTENSION IF NOT EXISTS pg_trgm;

-- ---------------------------------------------------------------------
-- Table: categories
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    category_id   BIGSERIAL       PRIMARY KEY,
    category_name VARCHAR(100)    NOT NULL,
    type          VARCHAR(50)     NOT NULL,           -- 'equipment' / 'attire'
    description   TEXT,
    created_at    TIMESTAMP(0),
    updated_at    TIMESTAMP(0)
);
-- Figure: Table Category

-- ---------------------------------------------------------------------
-- Table: items
-- ---------------------------------------------------------------------
CREATE TABLE items (
    item_id            BIGSERIAL       PRIMARY KEY,
    category_id        BIGINT          NOT NULL,
    item_name          VARCHAR(100)    NOT NULL,
    item_description   TEXT,
    quantity           INTEGER         NOT NULL DEFAULT 0,
    available_quantity INTEGER         NOT NULL DEFAULT 0,
    condition_status   VARCHAR(50)     NOT NULL DEFAULT 'good',
    item_status        VARCHAR(50)     NOT NULL DEFAULT 'available',
    date_added         DATE            NOT NULL DEFAULT CURRENT_DATE,
    image              VARCHAR(255),                  -- legacy single-image path
    created_at         TIMESTAMP(0),
    updated_at         TIMESTAMP(0),
    CONSTRAINT items_category_id_foreign
        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    CONSTRAINT chk_items_item_status
        CHECK (item_status IN ('available', 'unavailable'))
);
-- Figure: Table Item

-- ---------------------------------------------------------------------
-- Table: item_images (gallery — multiple images per item)
-- ---------------------------------------------------------------------
CREATE TABLE item_images (
    image_id    BIGSERIAL       PRIMARY KEY,
    item_id     BIGINT          NOT NULL,
    image_path  VARCHAR(255)    NOT NULL,
    "position"  INTEGER         NOT NULL DEFAULT 0,
    is_primary  BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0),
    CONSTRAINT item_images_item_id_foreign
        FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE
);
-- Figure: Table Item_Image

-- ---------------------------------------------------------------------
-- Table: students
-- ---------------------------------------------------------------------
CREATE TABLE students (
    student_id          BIGSERIAL       PRIMARY KEY,
    full_name           VARCHAR(100)    NOT NULL,
    email               VARCHAR(100)    NOT NULL UNIQUE,
    password            VARCHAR(255)    NOT NULL,
    phone_no            VARCHAR(20),
    matric_no           VARCHAR(20)     NOT NULL UNIQUE,
    status              VARCHAR(20)     NOT NULL DEFAULT 'active',
    last_login_at       TIMESTAMP(0),
    password_changed_at TIMESTAMP(0),
    created_at          TIMESTAMP(0),
    updated_at          TIMESTAMP(0)
);
-- Figure: Table Student

-- ---------------------------------------------------------------------
-- Table: staff
-- ---------------------------------------------------------------------
CREATE TABLE staff (
    staff_id            BIGSERIAL       PRIMARY KEY,
    full_name           VARCHAR(100)    NOT NULL,
    email               VARCHAR(100)    NOT NULL UNIQUE,
    password            VARCHAR(255)    NOT NULL,
    phone_no            VARCHAR(20),
    "position"          VARCHAR(50),
    status              VARCHAR(20)     NOT NULL DEFAULT 'active',
    is_admin            BOOLEAN         NOT NULL DEFAULT FALSE,
    last_login_at       TIMESTAMP(0),
    password_changed_at TIMESTAMP(0),
    created_at          TIMESTAMP(0),
    updated_at          TIMESTAMP(0)
);
-- Figure: Table Staff

-- ---------------------------------------------------------------------
-- Table: studios
-- ---------------------------------------------------------------------
CREATE TABLE studios (
    studio_id   BIGSERIAL       PRIMARY KEY,
    studio_name VARCHAR(100)    NOT NULL,
    studio_type VARCHAR(50)     NOT NULL,
    description TEXT,
    status      VARCHAR(20)     NOT NULL DEFAULT 'available',
    capacity    INTEGER,
    equipment   TEXT,
    location    VARCHAR(150),
    size        VARCHAR(50),
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0)
);
-- Figure: Table Studio

-- ---------------------------------------------------------------------
-- Table: studio_images (gallery — multiple images per studio)
-- ---------------------------------------------------------------------
CREATE TABLE studio_images (
    image_id    BIGSERIAL       PRIMARY KEY,
    studio_id   BIGINT          NOT NULL,
    image_path  VARCHAR(255)    NOT NULL,
    "position"  INTEGER         NOT NULL DEFAULT 0,
    is_primary  BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP(0),
    updated_at  TIMESTAMP(0),
    CONSTRAINT studio_images_studio_id_foreign
        FOREIGN KEY (studio_id) REFERENCES studios(studio_id) ON DELETE CASCADE
);
-- Figure: Table Studio_Image

-- ---------------------------------------------------------------------
-- Table: studio_unavailability (maintenance/closure blocks per studio)
-- ---------------------------------------------------------------------
CREATE TABLE studio_unavailability (
    unavailability_id BIGSERIAL       PRIMARY KEY,
    studio_id         BIGINT          NOT NULL,
    type              VARCHAR(20)     NOT NULL,        -- e.g. 'maintenance' / 'closed'
    start_at          TIMESTAMP(0)    NOT NULL,
    end_at            TIMESTAMP(0)    NOT NULL,
    reason            TEXT,
    staff_id          BIGINT,
    created_at        TIMESTAMP(0),
    updated_at        TIMESTAMP(0),
    CONSTRAINT studio_unavailability_studio_id_foreign
        FOREIGN KEY (studio_id) REFERENCES studios(studio_id) ON DELETE CASCADE,
    CONSTRAINT studio_unavailability_staff_id_foreign
        FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL
);
-- Figure: Table Studio_Unavailability

-- ---------------------------------------------------------------------
-- Table: borrowings (reservation-based borrowing header)
-- ---------------------------------------------------------------------
CREATE TABLE borrowings (
    borrow_id     BIGSERIAL       PRIMARY KEY,
    student_id    BIGINT          NOT NULL,
    staff_id      BIGINT,                              -- staff who approved/rejected
    pickup_date   DATE            NOT NULL,
    return_date   DATE            NOT NULL,
    borrow_status VARCHAR(50)     NOT NULL DEFAULT 'pending',
    purpose       TEXT,
    collected_at  TIMESTAMP(0),
    collected_by  BIGINT,                              -- staff who handed items over
    returned_at   TIMESTAMP(0),
    returned_by   BIGINT,                              -- staff who processed the return
    is_overdue    BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMP(0),
    updated_at    TIMESTAMP(0),
    CONSTRAINT borrowings_student_id_foreign
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE RESTRICT,
    CONSTRAINT borrowings_staff_id_foreign
        FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL,
    CONSTRAINT borrowings_collected_by_foreign
        FOREIGN KEY (collected_by) REFERENCES staff(staff_id) ON DELETE SET NULL,
    CONSTRAINT borrowings_returned_by_foreign
        FOREIGN KEY (returned_by) REFERENCES staff(staff_id) ON DELETE SET NULL,
    CONSTRAINT chk_borrowings_borrow_status
        CHECK (borrow_status IN ('pending', 'reserved', 'collected', 'returned', 'rejected', 'cancelled')),
    CONSTRAINT chk_borrowings_dates
        CHECK (return_date >= pickup_date)
);
-- Figure: Table Borrowing

-- ---------------------------------------------------------------------
-- Table: borrowing_details (associative entity — items per borrowing)
-- ---------------------------------------------------------------------
CREATE TABLE borrowing_details (
    borrow_detail_id  BIGSERIAL       PRIMARY KEY,
    borrow_id         BIGINT          NOT NULL,
    item_id           BIGINT          NOT NULL,
    quantity_borrowed INTEGER         NOT NULL DEFAULT 1 CHECK (quantity_borrowed >= 1),
    created_at        TIMESTAMP(0),
    updated_at        TIMESTAMP(0),
    CONSTRAINT borrowing_details_borrow_id_foreign
        FOREIGN KEY (borrow_id) REFERENCES borrowings(borrow_id) ON DELETE CASCADE,
    CONSTRAINT borrowing_details_item_id_foreign
        FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE RESTRICT
);
-- Figure: Table Borrowing_Detail

-- ---------------------------------------------------------------------
-- Table: return_records
-- ---------------------------------------------------------------------
CREATE TABLE return_records (
    return_id         BIGSERIAL       PRIMARY KEY,
    borrow_id         BIGINT          NOT NULL,
    item_id           BIGINT          NOT NULL,
    staff_id          BIGINT,
    return_date       DATE            NOT NULL,
    quantity_returned INTEGER         NOT NULL DEFAULT 1 CHECK (quantity_returned >= 1),
    item_condition    VARCHAR(50)     NOT NULL DEFAULT 'good',
    damage_note       TEXT,
    return_status     VARCHAR(50)     NOT NULL DEFAULT 'pending',
    remarks           TEXT,
    created_at        TIMESTAMP(0),
    updated_at        TIMESTAMP(0),
    CONSTRAINT return_records_borrow_id_foreign
        FOREIGN KEY (borrow_id) REFERENCES borrowings(borrow_id) ON DELETE RESTRICT,
    CONSTRAINT return_records_item_id_foreign
        FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE RESTRICT,
    CONSTRAINT return_records_staff_id_foreign
        FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL
);
-- Figure: Table Return_Record

-- ---------------------------------------------------------------------
-- Table: maintenances
-- ---------------------------------------------------------------------
CREATE TABLE maintenances (
    maintenance_id     BIGSERIAL       PRIMARY KEY,
    item_id            BIGINT          NOT NULL,
    staff_id           BIGINT,
    return_id          BIGINT,
    report_date        DATE            NOT NULL DEFAULT CURRENT_DATE,
    issue_type         VARCHAR(100),
    description        TEXT,
    maintenance_status VARCHAR(50)     NOT NULL DEFAULT 'pending',
    created_at         TIMESTAMP(0),
    updated_at         TIMESTAMP(0),
    CONSTRAINT maintenances_item_id_foreign
        FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE RESTRICT,
    CONSTRAINT maintenances_staff_id_foreign
        FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL,
    CONSTRAINT maintenances_return_id_foreign
        FOREIGN KEY (return_id) REFERENCES return_records(return_id) ON DELETE SET NULL,
    CONSTRAINT chk_maintenances_maintenance_status
        CHECK (maintenance_status IN ('pending', 'resolved'))
);
-- Figure: Table Maintenance

-- ---------------------------------------------------------------------
-- Table: bookings (self-service studio reservations)
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
    booking_id     BIGSERIAL       PRIMARY KEY,
    student_id     BIGINT          NOT NULL,
    staff_id       BIGINT,                             -- staff who cancelled/managed (nullable)
    studio_id      BIGINT          NOT NULL,
    booking_date   DATE            NOT NULL,
    start_time     TIME(0)         NOT NULL,
    end_time       TIME(0)         NOT NULL,
    booking_status VARCHAR(50)     NOT NULL DEFAULT 'confirmed',
    purpose        TEXT,
    created_at     TIMESTAMP(0),
    updated_at     TIMESTAMP(0),
    CONSTRAINT bookings_student_id_foreign
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE RESTRICT,
    CONSTRAINT bookings_staff_id_foreign
        FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL,
    CONSTRAINT bookings_studio_id_foreign
        FOREIGN KEY (studio_id) REFERENCES studios(studio_id) ON DELETE RESTRICT,
    CONSTRAINT chk_bookings_booking_status
        CHECK (booking_status IN ('pending', 'confirmed', 'completed', 'cancelled')),
    CONSTRAINT chk_bookings_time
        CHECK (end_time > start_time)
);
-- Figure: Table Booking


-- =====================================================================
-- Indexes (search / listing performance)
-- =====================================================================

-- Date-range / status lookups
CREATE INDEX bookings_date_time_status_index   ON bookings (booking_date, start_time, booking_status);
CREATE INDEX bookings_studio_date_index        ON bookings (studio_id, booking_date);
CREATE INDEX borrowings_pickup_created_status_index ON borrowings (pickup_date, created_at, borrow_status);
CREATE INDEX item_images_item_id_position_index    ON item_images (item_id, "position");
CREATE INDEX studio_images_studio_id_position_index ON studio_images (studio_id, "position");
CREATE INDEX studio_unavailability_studio_id_start_at_end_at_index
    ON studio_unavailability (studio_id, start_at, end_at);

-- Fuzzy text search (pg_trgm) for the global search bar
CREATE INDEX categories_category_name_trgm_idx  ON categories USING gin (category_name gin_trgm_ops);
CREATE INDEX items_item_name_trgm_idx           ON items USING gin (item_name gin_trgm_ops);
CREATE INDEX items_item_description_trgm_idx    ON items USING gin (item_description gin_trgm_ops);
CREATE INDEX staff_full_name_trgm_idx           ON staff USING gin (full_name gin_trgm_ops);
CREATE INDEX staff_email_trgm_idx               ON staff USING gin (email gin_trgm_ops);
CREATE INDEX staff_position_trgm_idx            ON staff USING gin ("position" gin_trgm_ops);
CREATE INDEX students_full_name_trgm_idx        ON students USING gin (full_name gin_trgm_ops);
CREATE INDEX students_email_trgm_idx            ON students USING gin (email gin_trgm_ops);
CREATE INDEX students_matric_no_trgm_idx        ON students USING gin (matric_no gin_trgm_ops);
CREATE INDEX studios_studio_name_trgm_idx       ON studios USING gin (studio_name gin_trgm_ops);
CREATE INDEX bookings_purpose_trgm_idx          ON bookings USING gin (purpose gin_trgm_ops);
CREATE INDEX borrowings_purpose_trgm_idx        ON borrowings USING gin (purpose gin_trgm_ops);
CREATE INDEX maintenances_issue_type_trgm_idx   ON maintenances USING gin (issue_type gin_trgm_ops);
CREATE INDEX maintenances_description_trgm_idx  ON maintenances USING gin (description gin_trgm_ops);


-- =====================================================================
-- Functions & Triggers
-- =====================================================================

-- ---------------------------------------------------------------------
-- fn_item_available_quantity
-- Purpose: real-time stock check for a date-range reservation —
--          stock minus units already reserved/collected for the same
--          pickup/return window (excluding a given borrowing, used
--          when re-checking on edit).
-- ---------------------------------------------------------------------
CREATE FUNCTION fn_item_available_quantity(
    p_item_id INTEGER,
    p_pickup_date DATE,
    p_return_date DATE,
    p_exclude_borrow_id INTEGER DEFAULT NULL
) RETURNS INTEGER
    LANGUAGE plpgsql STABLE
    AS $$
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
    $$;

-- ---------------------------------------------------------------------
-- fn_set_borrowing_overdue / trg_borrowings_set_overdue
-- Purpose: keep borrowings.is_overdue in sync whenever a row is
--          inserted/updated (e.g. collected, or return_date edited).
-- ---------------------------------------------------------------------
CREATE FUNCTION fn_set_borrowing_overdue() RETURNS TRIGGER
    LANGUAGE plpgsql
    AS $$
    BEGIN
        NEW.is_overdue := (
            NEW.borrow_status = 'collected'
            AND NEW.return_date IS NOT NULL
            AND NEW.return_date < CURRENT_DATE
        );
        RETURN NEW;
    END;
    $$;

CREATE TRIGGER trg_borrowings_set_overdue
    BEFORE INSERT OR UPDATE ON borrowings
    FOR EACH ROW EXECUTE FUNCTION fn_set_borrowing_overdue();

-- ---------------------------------------------------------------------
-- fn_refresh_overdue_borrowings
-- Purpose: daily scheduled refresh (routes/console.php) — flags
--          borrowings that became overdue purely due to the passage
--          of time, without any write to the row.
-- ---------------------------------------------------------------------
CREATE FUNCTION fn_refresh_overdue_borrowings() RETURNS INTEGER
    LANGUAGE plpgsql
    AS $$
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
    $$;

-- ---------------------------------------------------------------------
-- fn_studio_next_available_slot
-- Purpose: returns the next free time slot for a studio on a given
--          date, accounting for existing bookings and
--          studio_unavailability blocks.
-- ---------------------------------------------------------------------
CREATE FUNCTION fn_studio_next_available_slot(
    p_studio_id INTEGER,
    p_date DATE,
    p_duration_minutes INTEGER DEFAULT 60,
    p_operating_start TIME DEFAULT '08:00:00',
    p_operating_end TIME DEFAULT '22:00:00'
) RETURNS TABLE(slot_start TIMESTAMP, slot_end TIMESTAMP)
    LANGUAGE plpgsql STABLE
    AS $$
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
    $$;


-- =====================================================================
-- Reporting Views
-- =====================================================================

-- ---------------------------------------------------------------------
-- v_inventory_low_stock
-- Purpose: available items whose stock has dropped to <= 20% of total
--          quantity (low-stock alert list / dashboard KPI).
-- ---------------------------------------------------------------------
CREATE VIEW v_inventory_low_stock AS
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
        WHEN i.quantity > 0 THEN ROUND((i.available_quantity::numeric / i.quantity::numeric) * 100, 2)
        ELSE 0
    END AS availability_pct
FROM items i
JOIN categories c ON c.category_id = i.category_id
WHERE i.item_status = 'available'
  AND i.quantity > 0
  AND i.available_quantity <= i.quantity * 0.2;

-- ---------------------------------------------------------------------
-- v_monthly_booking_summary
-- Purpose: per-studio, per-month booking counts by status — backs the
--          admin booking/utilization reports.
-- ---------------------------------------------------------------------
CREATE VIEW v_monthly_booking_summary AS
SELECT
    to_char(b.booking_date, 'YYYY-MM')                                          AS report_month,
    s.studio_id,
    s.studio_name,
    COUNT(*)                                                                    AS total_bookings,
    COUNT(*) FILTER (WHERE b.booking_status = 'pending')                        AS pending_count,
    COUNT(*) FILTER (WHERE b.booking_status = 'confirmed')                      AS confirmed_count,
    COUNT(*) FILTER (WHERE b.booking_status = 'completed')                      AS completed_count,
    COUNT(*) FILTER (WHERE b.booking_status = 'cancelled')                      AS cancelled_count
FROM bookings b
JOIN studios s ON s.studio_id = b.studio_id
GROUP BY report_month, s.studio_id, s.studio_name
ORDER BY report_month DESC, s.studio_name;

-- ---------------------------------------------------------------------
-- v_studio_utilization
-- Purpose: all-time share of confirmed/completed bookings per studio
--          relative to all confirmed/completed bookings system-wide.
-- ---------------------------------------------------------------------
CREATE VIEW v_studio_utilization AS
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
                / (SELECT COUNT(*) FROM bookings WHERE booking_status IN ('confirmed','completed')),
                2
            )
        ELSE 0
    END AS utilization_pct
FROM studios s
LEFT JOIN bookings b ON b.studio_id = s.studio_id
GROUP BY s.studio_id, s.studio_name, s.studio_type
ORDER BY utilization_pct DESC;
