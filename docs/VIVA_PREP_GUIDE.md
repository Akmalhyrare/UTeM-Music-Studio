# UTeM Music Studio Management System — Complete Viva Preparation Guide

This guide explains the entire system from A to Z, based on the actual source code and database of the project (Laravel 12, PHP 8.2, PostgreSQL, Blade, AJAX, RBAC).

---

# PART 1 — BIG PICTURE

## 1. What is the purpose of this system?

The system is a **web-based management platform for the UTeM Music Studio**. It digitizes two main physical processes that used to be done manually (on paper / WhatsApp / spreadsheets):

1. **Booking music studios/rooms** for practice or recording sessions.
2. **Borrowing music equipment** (guitars, microphones, cables, stands, attire, etc.) for a date range.

On top of that, it gives staff and admins tools to **manage inventory, track maintenance, generate reports, and manage user accounts** — everything needed to run the studio day-to-day.

## 2. What problem does it solve?

| Old manual process | Problem | New system solution |
|---|---|---|
| Students ask staff in person/WhatsApp to book a studio | Double-booking, no record, hard to check availability | Online booking with conflict detection (`BookingService::hasConflict`) |
| Equipment borrowed using a paper logbook | No real-time stock visibility, items "disappear", no accountability | Digital borrowing with reservation-based stock tracking |
| No way to know which item is low in stock | Staff find out only when something is missing | `v_inventory_low_stock` view + dashboard alert |
| Damaged equipment not tracked properly | Equipment stays "available" even though it's broken | Maintenance module + `available_quantity` auto-adjustment |
| No reports for management | Manual counting for monthly reports | Report module with PDF/CSV export |
| Anyone could potentially access admin functions | No proper access control | RBAC middleware (Admin / Staff / Student) |

## 3. Who are the users of the system?

There are **3 roles**, all stored in two tables (`students`, `staff`) — note there is **no separate `users` table**; this project uses **custom session-based authentication**, not Laravel's default Auth/Breeze.

1. **Student** (`students` table, `session('user_type') = 'student'`)
   - Browse items/studios, make bookings, request borrowings, view their own history, manage their account settings.
2. **Staff** (`staff` table, `is_admin = false`)
   - Manage inventory, approve/reject/collect/return borrowings, manage studio bookings, manage studio calendar/unavailability, handle maintenance, use global search.
3. **Admin** (`staff` table, `is_admin = true`)
   - Everything Staff can do, **plus**: view the admin dashboard (system-wide KPIs), manage user accounts (create/edit/delete staff & students), generate reports (PDF/CSV), archive studios.

## 4. Overall system workflow

```
                ┌──────────────────────────┐
                │        STUDENT            │
                │ (Register / Login)         │
                └──────────────┬─────────────┘
                                │
              ┌─────────────────┴─────────────────┐
              │                                     │
      ┌───────▼────────┐                  ┌────────▼─────────┐
      │ Browse Studios   │                  │ Browse Items      │
      │ (BrowseController)│                │ (BrowseController)│
      └───────┬────────┘                  └────────┬─────────┘
              │                                     │
      ┌───────▼────────┐                  ┌────────▼─────────┐
      │  Make Booking    │                  │ Make Borrowing    │
      │ (BookingService) │                  │ (BorrowingService)│
      └───────┬────────┘                  └────────┬─────────┘
              │                                     │
              │            ┌────────────────────────┘
              │            │
       ┌──────▼────────────▼───────┐
       │     DATABASE (PostgreSQL)   │
       │ bookings / borrowings /     │
       │ borrowing_details / items   │
       │  + Triggers + Functions     │
       └──────────────┬──────────────┘
                       │
       ┌────────────────▼─────────────────┐
       │            STAFF                   │
       │  Approve / Reject / Collect /      │
       │  Process Return / Maintenance      │
       └────────────────┬─────────────────┘
                       │
       ┌────────────────▼─────────────────┐
       │            ADMIN                   │
       │ Dashboard KPIs, User Management,   │
       │ REPORTS (PDF / CSV export)         │
       └─────────────────────────────────────┘
```

## 5. What are the main modules?

1. **Authentication module** — login, logout, register (students only), session management.
2. **Inventory module** — items, categories, item images, stock levels.
3. **Studio management module** — studios, studio images, studio unavailability/calendar.
4. **Booking module** — students book studios (auto-confirmed instantly, no staff approval step); a booking auto-completes once its end time passes (checked opportunistically on page load and via a 5-minute scheduled job); staff's only remaining action on an active booking is cancelling it.
5. **Borrowing module** — students request equipment for a date range; staff approve/collect/return.
6. **Maintenance module** — track damaged/lost items reported during returns; resolve and restock.
7. **Reporting module** — admin generates booking/borrowing/inventory reports (view, PDF, CSV).
8. **Admin Dashboard module** — KPIs, charts, alerts (cached for performance).
9. **User Management module** — admin CRUD for staff and student accounts.
10. **Global Search module** — fuzzy search (pg_trgm) across items, students, staff, bookings, borrowings, maintenance.
11. **Settings module** — every logged-in user can update profile/password and log out other sessions.
12. **Backup module** — `spatie/laravel-backup` automated DB + file backups.

## 6. How do all modules connect?

```
         ┌───────────────┐
         │  AuthController │  ← entry point for everyone
         └────────┬────────┘
                  │ session('user_type'), session('is_admin')
   ┌──────────────┼────────────────────┐
   │              │                     │
┌──▼───┐     ┌────▼─────┐        ┌──────▼──────┐
│Student│     │  Staff    │        │   Admin       │
│routes │     │  routes   │        │   routes      │
└──┬────┘     └────┬─────┘        └──────┬───────┘
   │               │                      │
   │   Bookings    │   Inventory          │  Reports
   │   Borrowings  │   Studio Mgmt        │  User Mgmt
   │   Settings    │   Borrowing Approval │  Dashboard
   │   Search      │   Maintenance        │
   │               │   Settings, Search   │
   └──────┬────────┴───────────┬──────────┘
          │                    │
   ┌──────▼────────────────────▼───────┐
   │   Services Layer (business logic)   │
   │ BookingService / BorrowingService /  │
   │ StudioService / ItemImageService     │
   └──────────────┬───────────────────────┘
                  │
   ┌──────────────▼───────────────────────┐
   │      Models (Eloquent ORM)             │
   │ Student, Staff, Item, Studio, Booking, │
   │ Borrowing, BorrowingDetail, etc.        │
   └──────────────┬───────────────────────┘
                  │
   ┌──────────────▼───────────────────────┐
   │  PostgreSQL Database                   │
   │  13 Tables + 3 Views + 4 Functions     │
   │  + 1 Trigger + GIN/trgm Indexes         │
   └────────────────────────────────────────┘
```

---

# PART 2 — PROJECT STRUCTURE

Every Laravel 12 project has these top-level folders. Here is what each one does **in this project specifically**.

### `app/`
**Function**: Contains all the PHP "brain" of the application — Controllers, Models, Services, Middleware, FormRequests, Exceptions.
**Why needed**: This is where your actual business logic lives. Laravel auto-loads classes from here via Composer's PSR-4 autoloading (`App\` namespace → `app/`).
**If missing**: The application has no logic at all — Laravel itself (the framework in `vendor/`) can still boot, but there would be nothing to route to, no models, nothing. The app would be 100% non-functional.

Sub-folders used in this project:
- `app/Http/Controllers/` — 19 controllers (AuthController, InventoryController, BookingController, BorrowingController, StaffBorrowingController, ReportController, AdminDashboardController, etc.)
- `app/Http/Middleware/` — `RequireAdmin`, `RequireStaff`, `RequireStudent` (custom RBAC gates)
- `app/Http/Requests/` — 13 FormRequest validation classes
- `app/Models/` — 13 Eloquent models, one per database table
- `app/Services/` — 4 service classes containing business logic (`BookingService`, `BorrowingService`, `ItemImageService`, `StudioService`)
- `app/Exceptions/` — custom exceptions like `InsufficientStockException`, `BookingConflictException`
- `app/Support/` — small reusable helpers, e.g. `Search.php` (fuzzy search helper used by Global Search and User Management)

### `bootstrap/`
**Function**: Bootstraps (starts up) the Laravel application. `bootstrap/app.php` is where the application object is created, routes are registered (`web.php`, `console.php`), and middleware aliases are defined (`auth.staff`, `auth.admin`, `auth.student`).
**Why needed**: It is the "ignition switch" — every HTTP request and every Artisan command passes through this file first.
**If missing**: Laravel cannot start at all. Every request would crash immediately (fatal error before any controller runs).

### `config/`
**Function**: Holds all configuration files as plain PHP arrays — `app.php` (app name, debug mode, timezone), `database.php` (PostgreSQL connection + `pg_dump` path for backups), `session.php` (session driver, cookie security), `backup.php` (spatie backup settings), `logging.php`, `cache.php`, etc.
**Why needed**: Centralizes all environment-dependent settings so code never hardcodes things like DB passwords — those come from `.env` via `env()` calls inside these config files.
**If missing**: Laravel falls back to framework defaults for everything (wrong DB connection, wrong session driver, etc.) — in practice, the app would fail to connect to PostgreSQL and crash on the first database query.

### `database/`
**Function**: Contains:
- `migrations/` — version-controlled "scripts" that build/alter the database schema over time (e.g., the migration that renamed `borrow_date`→`pickup_date` and added `collected_at`/`returned_by`, and the one that dropped the unused Breeze `users`/`password_reset_tokens` tables).
- `seeders/` — `DatabaseSeeder.php` populates the DB with sample data (categories, items, studios, demo staff/student accounts) for testing/demo.
- `factories/` — model factories used by seeders/tests to generate fake data.
- `sql/` — custom SQL artifacts created for this project: `current_schema_ddl.sql` (full DDL including views/triggers/functions) and `erd.puml` (PlantUML ERD diagram).

**Why needed**: Migrations make the database schema reproducible and shareable — anyone can run `php artisan migrate` and get the exact same table structure, instead of manually creating tables in pgAdmin.
**If missing**: The database schema would have to be created manually and could drift between developer machines / production — very error-prone, and `php artisan migrate` would have nothing to run.

### `public/`
**Function**: The **only folder exposed directly to the web server** (Apache/Nginx document root points here). Contains `index.php` (the single entry point for all HTTP requests — the "front controller"), compiled CSS/JS (`public/build/`), and `storage` (a **symlink** to `storage/app/public`) where uploaded item/studio images are served from (`public/storage/items/`, `public/storage/studios/`).
**Why needed**: Separates publicly-accessible files from the rest of the application code (which must NOT be web-accessible for security). All requests funnel through `index.php`, which boots Laravel.
**If missing**: The web server would have nothing to serve — there's no entry point, so every URL would return a 404/403. Even if other folders were exposed directly, that would be a massive security hole (exposing `.env`, source code, etc.).

### `resources/`
**Function**: Contains front-end source files: `resources/views/` (all `.blade.php` templates — 40+ files for landing page, dashboards, forms, reports), `resources/js/app.js` (JavaScript/AJAX for things like the booking availability checker), `resources/css/` (Tailwind/CSS source).
**Why needed**: This is where the **View** layer of MVC lives — what the user actually sees and interacts with. Blade templates let PHP variables be embedded safely into HTML (`{{ $variable }}` auto-escapes against XSS).
**If missing**: Controllers would have no templates to render — every `return view(...)` call would throw a "View not found" error. The site would be 100% broken visually (though the backend/DB logic could still technically run).

### `routes/`
**Function**: `routes/web.php` defines all 91 URL routes and maps them to Controller methods, with middleware applied per group. `routes/console.php` defines scheduled tasks (Artisan commands run automatically, like `backup:run` and the daily overdue-borrowing refresh).
**Why needed**: This is the **traffic director** — it's the map Laravel uses to decide "when someone visits `/borrowings`, which Controller method should handle it, and what security checks (middleware) must pass first?"
**If missing**: No URL would resolve to anything — visiting any page (even `/`) would return a 404 Not Found, because Laravel wouldn't know what to do with the request.

### `storage/`
**Function**: Writable runtime storage — `storage/app/public/` (uploaded images for items/studios), `storage/framework/` (cached views, sessions if file-based, compiled framework files), `storage/logs/laravel.log` (application logs — this is where `Log::error()`/`Log::warning()` calls from `AuthController` write to).
**Why needed**: Laravel needs a writable location for temporary/generated files and logs that should NOT be committed to Git or exposed publicly. It's also the source for the `public/storage` symlink (`php artisan storage:link`).
**If missing**: File uploads (item/studio images) would fail (no destination to write to), logging would fail, and view caching/compilation would fail — many parts of the app would throw "permission denied" or "directory not found" errors.

### `vendor/`
**Function**: Contains **all third-party packages** installed via Composer — the entire Laravel framework itself, plus packages like `barryvdh/laravel-dompdf` (PDF report generation), `spatie/laravel-backup` (backups), `spatie/laravel-permission`-style helpers if used, etc. Also contains `vendor/autoload.php`, the file that makes PSR-4 autoloading work.
**Why needed**: Laravel itself is just a Composer package — without `vendor/`, there is no framework at all. `composer.json` lists *what* is needed; `vendor/` is the actual downloaded code.
**If missing**: Nothing runs. `php artisan` commands fail immediately, `index.php` fails on its first `require __DIR__.'/../vendor/autoload.php'` line. Running `composer install` regenerates this folder from `composer.lock`.

---

# PART 3 — DATABASE EXPLANATION

The database is **PostgreSQL**, with **13 tables**, **3 views**, **4 functions**, **1 trigger**, and many indexes (including fuzzy-search GIN indexes using `pg_trgm`).

## Quick concepts (explained simply)

- **Primary Key (PK)**: A column (or set of columns) that uniquely identifies each row in a table — like an IC number for a person. No two rows can have the same PK.
- **Foreign Key (FK)**: A column in one table that points to the PK of another table — this is how tables are "linked" together. E.g., `items.category_id` points to `categories.category_id`.
- **One-to-One**: One row in Table A relates to exactly one row in Table B. Example: each `return_records` row can have **at most one** `maintenances` row (`maintenances.return_id` → `return_records.return_id`, modeled as `hasOne`/`belongsTo`).
- **One-to-Many**: One row in Table A relates to many rows in Table B. Example: one `categories` row → many `items` rows (one category like "Guitars" has many guitar items).
- **Many-to-Many**: Many rows in Table A relate to many rows in Table B, via a **junction/associative table** in the middle. Example: `borrowings` ↔ `items` via `borrowing_details` — one borrowing request can contain many items, and one item can appear in many borrowing requests.

## Table-by-table explanation

### 1. `students`
- **Purpose**: Stores student accounts (the "customers" of the studio).
- **PK**: `student_id`
- **FK**: none (top-level entity)
- **Relationships**: `students` 1 → many `bookings`; `students` 1 → many `borrowings`.
- **Why needed**: Every booking/borrowing must belong to *someone* — this table is the source of truth for "who is this student" (name, email, matric number, password, status).

### 2. `staff`
- **Purpose**: Stores staff AND admin accounts (an admin is just a staff row with `is_admin = true`).
- **PK**: `staff_id`
- **FK**: none
- **Relationships**: `staff` 1 → many `bookings` (as approver), 1 → many `borrowings` (as approver/collector/returner via `staff_id`/`collected_by`/`returned_by`), 1 → many `return_records`, 1 → many `maintenances`, 1 → many `studio_unavailability`.
- **Why needed**: Distinguishes "who is allowed to approve things" from students, and the `is_admin` flag drives the RBAC middleware (`RequireAdmin`).

### 3. `categories`
- **Purpose**: Groups items into categories (e.g., "Guitars", "Microphones", "Attire") and a `type` field separating `equipment` vs `attire`.
- **PK**: `category_id`
- **FK**: none
- **Relationships**: `categories` 1 → many `items`.
- **Why needed**: Lets the inventory page filter/group items, and lets reports break down inventory by category.

### 4. `items`
- **Purpose**: The actual physical equipment/attire that can be borrowed (e.g., "Yamaha Acoustic Guitar", quantity 10).
- **PK**: `item_id`
- **FK**: `category_id` → `categories.category_id`
- **Relationships**: belongs to one `categories`; has many `item_images`, `borrowing_details`, `return_records`, `maintenances`.
- **Why needed**: This is the core "stock" entity — `quantity` (total owned) and `available_quantity` (not permanently removed due to damage/loss) drive every borrowing-availability calculation.

### 5. `item_images`
- **Purpose**: Stores multiple photos per item (gallery), with a `position` (ordering) and `is_primary` flag (which photo shows on the card/thumbnail).
- **PK**: `image_id`
- **FK**: `item_id` → `items.item_id` (ON DELETE CASCADE — if an item is deleted, its images are deleted too)
- **Relationships**: belongs to one `items`.
- **Why needed**: One item often needs several photos (different angles); a single `image` column (legacy) wasn't enough.

### 6. `studios`
- **Purpose**: The physical rooms/studios that can be booked (name, type, capacity, equipment list, location, status like `available`/`maintenance`/`blocked`).
- **PK**: `studio_id`
- **FK**: none
- **Relationships**: 1 → many `studio_images`, 1 → many `studio_unavailability`, 1 → many `bookings`.
- **Why needed**: Core entity for the Booking module — every booking must reference a real studio.

### 7. `studio_images`
- **Purpose**: Gallery photos for studios, same pattern as `item_images`.
- **PK**: `image_id`
- **FK**: `studio_id` → `studios.studio_id` (CASCADE)
- **Why needed**: Lets students see what a studio looks like before booking it.

### 8. `studio_unavailability`
- **Purpose**: Records periods when a studio is closed/under maintenance and cannot be booked (e.g., "Studio A closed 1–3 June for AC repair").
- **PK**: `unavailability_id`
- **FK**: `studio_id` → `studios.studio_id` (CASCADE), `staff_id` → `staff.staff_id` (SET NULL — if the staff account is deleted, the record stays but loses the "who created it" link)
- **Relationships**: belongs to one `studios`, belongs to one `staff` (the staff who created the block).
- **Why needed**: `BookingService::assertStudioBookable()` checks this table to reject bookings that fall inside a maintenance/closure window — without it, students could book a studio that's physically unusable.

### 9. `borrowings`
- **Purpose**: The "header" record for an equipment borrowing **reservation** — who, when (pickup/return date range), current status, and who handled collection/return.
- **PK**: `borrow_id`
- **FK**: `student_id` → `students.student_id` (RESTRICT — can't delete a student with borrowing history), `staff_id`/`collected_by`/`returned_by` → `staff.staff_id` (all SET NULL)
- **Relationships**: belongs to one `students`; 1 → many `borrowing_details`; 1 → many `return_records`.
- **Why needed**: Represents "one borrowing request/transaction" — but a request can contain *multiple different items*, which is why the actual items live in a separate table (see next).

### 10. `borrowing_details` — **the Many-to-Many junction table**
- **Purpose**: Links `borrowings` ↔ `items`, recording **how many units** of each item are part of a borrowing request.
- **PK**: `borrow_detail_id`
- **FK**: `borrow_id` → `borrowings.borrow_id` (CASCADE), `item_id` → `items.item_id` (RESTRICT)
- **Why needed**: This is the textbook example of **Many-to-Many with an extra attribute** (`quantity_borrowed`). One `borrowings` row (e.g., "Borrow request #5") can have rows for "2x Guitar" + "1x Microphone Stand" — both linking back to `items`, but each `items` row can also appear in many different `borrowings` (different students borrowing the same guitar model at different times). A plain many-to-many table without `quantity_borrowed` wouldn't be enough — we need to know *how many* of each item.

### 11. `return_records`
- **Purpose**: Records the actual physical return of items — date returned, quantity returned, condition (`good`/`damaged`/`lost`), damage notes.
- **PK**: `return_id`
- **FK**: `borrow_id` → `borrowings.borrow_id` (RESTRICT), `item_id` → `items.item_id` (RESTRICT), `staff_id` → `staff.staff_id` (SET NULL)
- **Relationships**: belongs to `borrowings` and `items`; **has one** `maintenances` (one-to-one — `maintenances.return_id` → `return_records.return_id`).
- **Why needed**: A borrowing can be returned in multiple batches (partial returns), and each return needs its own condition assessment — this can't just be a status flag on `borrowings`.

### 12. `maintenances`
- **Purpose**: Tracks damaged/lost items that need repair or write-off.
- **PK**: `maintenance_id`
- **FK**: `item_id` → `items.item_id` (RESTRICT), `staff_id` → `staff.staff_id` (SET NULL), `return_id` → `return_records.return_id` (SET NULL, **optional** — `maintenance_status` can be `pending`/`resolved`)
- **Relationships**: belongs to `items`, `staff`, optionally one `return_records` (one-to-one).
- **Why needed**: Separates "this item is damaged and needs action" from the borrowing/return workflow — staff have a dedicated maintenance queue, and resolving it (`BorrowingService::resolveMaintenance`) restores `items.available_quantity`.

### 13. `bookings`
- **Purpose**: A studio reservation by a student for a specific date + time range.
- **PK**: `booking_id`
- **FK**: `student_id` → `students.student_id` (RESTRICT), `staff_id` → `staff.staff_id` (SET NULL), `studio_id` → `studios.studio_id` (RESTRICT)
- **Relationships**: belongs to `students`, `staff` (optional), `studios`.
- **Why needed**: Core record for the Booking module — `booking_status` (`pending`/`confirmed`/`completed`/`cancelled`) plus `start_time`/`end_time` drive the conflict-detection logic in `BookingService`.

## Relationship type examples from THIS project

- **One-to-One**: `return_records` ←→ `maintenances` (via `return_id`). One return record produces at most one maintenance ticket.
- **One-to-Many**: `categories` → `items` (one category has many items). Also `students` → `borrowings`, `studios` → `bookings`, `items` → `item_images`.
- **Many-to-Many**: `borrowings` ↔ `items` through `borrowing_details`. Also conceptually `bookings` ↔ `studios` is many-to-one really (one booking = one studio), but a *studio* can have many bookings over time — that's one-to-many, not many-to-many. The clearest many-to-many in this schema is the borrowing/item relationship.

## Views, Triggers, Functions, Indexes, Constraints — why they exist

### Views (virtual "saved queries")
A **view** is like a saved SELECT query that behaves like a read-only table. Useful because complex reporting SQL doesn't need to be rewritten everywhere — Laravel can just `SELECT * FROM v_inventory_low_stock`.

1. **`v_inventory_low_stock`** — lists items where `available_quantity <= 20%` of `quantity`. Used for the dashboard's low-stock alert and inventory reports.
2. **`v_monthly_booking_summary`** — groups bookings by month + studio + status, for monthly reporting.
3. **`v_studio_utilization`** — calculates each studio's percentage share of all confirmed/completed bookings (which studio is most popular).

### Triggers (automatic actions on data change)
A **trigger** is code that PostgreSQL runs *automatically* when a row is inserted/updated/deleted — you don't have to remember to call it from PHP.

- **`trg_borrowings_set_overdue`** (BEFORE INSERT OR UPDATE on `borrowings`) — calls `fn_set_borrowing_overdue()`, which sets `is_overdue = true` automatically whenever a row is saved with `borrow_status = 'collected'` AND `return_date < today`. This means **even if the PHP code forgets to check**, the database guarantees `is_overdue` stays correct at the moment of any write.

### Functions (reusable SQL logic)
A **function** is a named, reusable block of SQL/PL-pgSQL logic that can be called from queries (or from a trigger).

1. **`fn_item_available_quantity(item_id, pickup_date, return_date, exclude_borrow_id)`** — calculates how many units of an item are free for a given date range (stock minus overlapping pending/reserved/collected reservations). This mirrors the PHP logic in `BorrowingService::getAvailableQuantity()` — having it in SQL too means ad-hoc reports/queries can reuse the same rule.
2. **`fn_set_borrowing_overdue()`** — the trigger function described above.
3. **`fn_refresh_overdue_borrowings()`** — run daily by the Laravel scheduler; re-evaluates `is_overdue` for all `collected` borrowings (catches cases where time passes *without* any row being written — the trigger alone can't catch "midnight ticked over").
4. **`fn_studio_next_available_slot(studio_id, date, duration, operating_start, operating_end)`** — returns the next free time slot for a studio on a given day, considering both bookings and `studio_unavailability`.

### Indexes (speed up searches)
An index is like a book's index page — instead of PostgreSQL scanning every row, it can jump straight to matching rows.

- Regular B-tree indexes on commonly filtered columns: `(booking_date, start_time, booking_status)`, `(studio_id, booking_date)`, `(pickup_date, created_at, borrow_status)`, etc. — speeds up the Booking/Borrowing index pages and reports.
- **GIN + `pg_trgm` indexes** on text columns (`item_name`, `full_name`, `email`, `matric_no`, `purpose`, etc.) — these enable **fuzzy/partial text search** (e.g., searching "gita" still finds "Guitar") used by the Global Search feature, which would otherwise require slow `LIKE '%...%'` full table scans.

### Constraints (data integrity rules)
- **FK constraints** — prevent orphaned rows (e.g., you can't insert a `borrowing_details` row pointing to an `item_id` that doesn't exist).
- **CHECK constraints** — e.g., `chk_borrowings_borrow_status` only allows `('pending','reserved','collected','returned','rejected','cancelled')`; `chk_borrowings_dates` requires `return_date >= pickup_date`; `chk_bookings_time` requires `end_time > start_time`. These guarantee **bad data can never exist**, even if a bug in PHP tries to insert it.
- **UNIQUE constraints** — `students.email`, `students.matric_no`, `staff.email` — prevents duplicate accounts.

---

# PART 4 — DATABASE FLOW (Step-by-Step)

## A. Student makes a Studio Booking

**Tables involved**: `bookings`, `studios`, `studio_unavailability`

1. Student opens `GET /bookings/create` -> `BookingController::create()` loads the list of bookable studios (`Studio::where('status','!=','inactive')->get()`).
2. Student picks a studio and date -> AJAX call to `GET /studios/{studio}/availability` -> `BookingController::availability()` -> `BookingService::getAvailability()`.
   - This builds an hour-by-hour grid for that day, checking:
     - `bookings` table for existing `pending/confirmed/completed` bookings on that studio+date,
     - `studio_unavailability` table for maintenance/blocked periods overlapping that day.
   - Returns JSON like `{slots: [{start:'08:00', end:'09:00', status:'available'}, ...]}`.
3. Student submits the form -> `POST /bookings` -> `StoreBookingRequest` validates input (date format, time order, required fields) -> `BookingController::store()` -> `BookingService::createBooking()`.
4. **Inside `createBooking()` (DB transaction)**:
   - `Studio::lockForUpdate()` - locks the studio row so two students can't book the same slot at the exact same millisecond (race condition protection).
   - `assertStudioBookable()` - checks `studios.status` is `'available'` and queries `studio_unavailability` for overlaps (uses `scopeOverlapping`).
   - `hasConflict()` - queries `bookings` for any existing active (`confirmed`/`completed`) booking on the same studio/date where `start_time < new.end_time AND end_time > new.start_time` (classic interval overlap check).
   - If all checks pass -> `INSERT INTO bookings (... booking_status='confirmed')`.
5. **Data changed**: One new row in `bookings` with `booking_status = 'confirmed'`.
6. **No trigger fires** for bookings (the only trigger in the schema is on `borrowings`).
7. **No staff approval step exists for bookings** — confirmation is automatic at creation time (step 4). Staff's only remaining action on a `confirmed` booking is to cancel it (`PUT /staff/bookings/{id}` with `booking_status=cancelled`).
8. **Auto-completion**: once a booking's `booking_date + end_time` is in the past, `BookingService::autoCompletePastBookings()` flips `booking_status` from `confirmed` to `completed`. This runs opportunistically every time `BookingController`/`StaffBookingController` `index()`/`show()` is loaded, and unconditionally via `Schedule::call(...)->everyFiveMinutes()` in `routes/console.php` — so a booking completes itself without anyone needing to view a page.

## B. Student makes a Borrowing (equipment reservation)

**Tables involved**: `borrowings`, `borrowing_details`, `items`

1. `GET /borrowings/create` -> `BorrowingController::create()` loads available items (`Item::where('item_status','available')->where('available_quantity','>',0)`).
2. Student picks pickup date, return date, items + quantities. AJAX calls `GET /borrowings/availability` -> `BorrowingController::availability()` -> `BorrowingService::getAvailableQuantity($itemId, $pickupDate, $returnDate)`.
   - **Query**: sums `quantity_borrowed` from `borrowing_details` JOIN `borrowings` WHERE `item_id = X` AND `borrow_status IN ('pending','reserved','collected')` AND the date ranges **overlap** (`pickup_date <= return_date AND return_date >= pickup_date`).
   - **Result**: `items.available_quantity - reserved_sum` = units free for that window.
   - (This exact formula also exists as the SQL function `fn_item_available_quantity()` - same logic, two places: PHP for app validation, SQL for ad-hoc reporting.)
3. Student submits -> `POST /borrowings` -> `StoreBorrowingRequest` (validates `pickup_date >= today`, `return_date >= pickup_date`, and via `withValidator()` re-checks `getAvailableQuantity()` per item) -> `BorrowingController::store()` -> `BorrowingService::createBorrowing()`.
4. **Inside `createBorrowing()` (DB transaction)**:
   - For each requested item: `Item::lockForUpdate()` then `getAvailableQuantity()` again (race-safe re-check). If `requested > available` -> throw `InsufficientStockException`.
   - `INSERT INTO borrowings (student_id, pickup_date, return_date, borrow_status='pending', purpose)`.
   - For each item: `INSERT INTO borrowing_details (borrow_id, item_id, quantity_borrowed)`.
5. **Trigger that fires**: `trg_borrowings_set_overdue` (BEFORE INSERT) runs `fn_set_borrowing_overdue()` - but since `borrow_status = 'pending'` (not `'collected'`), `is_overdue` is set to `false`. No visible effect yet, but it always runs.
6. **Data changed**: 1 new row in `borrowings` (status `pending`), N new rows in `borrowing_details`. **`items.available_quantity` is NOT changed yet** - stock is only "soft reserved" via the overlap query, not physically decremented.

## C. Staff approves a Borrowing

**Tables involved**: `borrowings`, `borrowing_details`, `items`

1. Staff opens `GET /staff/borrowings` -> `StaffBorrowingController::index()` lists `borrow_status='pending'` requests (with `with(['student','borrowingDetails.item'])` to avoid N+1).
2. Staff clicks Approve -> `PUT /staff/borrowings/{id}/approve` -> `StaffBorrowingController::approve()` -> `BorrowingService::approveBorrowing($borrowing, $staffId)`.
3. **Inside `approveBorrowing()` (DB transaction)**:
   - For each `borrowing_details` row: `Item::lockForUpdate()`, then **re-run** `getAvailableQuantity()` excluding this borrowing's own reservation (`$excludeBorrowId`). This is the **race-safe final check** - between the time the student submitted and now, another request might have consumed the stock.
   - If still insufficient -> throw `InsufficientStockException` -> controller catches it, flashes an error, the borrowing **stays `pending`**.
   - If OK -> `UPDATE borrowings SET borrow_status='reserved', staff_id=<approver>`.
4. **Trigger fires**: `trg_borrowings_set_overdue` runs again on UPDATE - `borrow_status` is now `'reserved'` (not `'collected'`), so `is_overdue` stays `false`.
5. **Data changed**: `borrowings.borrow_status` -> `'reserved'`, `borrowings.staff_id` set. **`items.available_quantity` still unchanged** - approval doesn't remove stock, it's still just a date-range reservation.

*(Later: Staff clicks "Collect" -> `PUT /staff/borrowings/{id}/collect` -> `collectBorrowing()` sets `borrow_status='collected'`, `collected_at=now()`, `collected_by=staffId`. The trigger now evaluates `is_overdue = (return_date < CURRENT_DATE)` - if the student already kept it past the return date at the moment of collection, it would even be flagged then, though normally collection happens on/around the pickup date.)*

## D. Student / Staff processes a Return

**Tables involved**: `borrowings`, `borrowing_details`, `return_records`, `items`, `maintenances`

1. Staff opens a `collected` borrowing -> `GET /staff/borrowings/{id}` -> `StaffBorrowingController::show()`.
2. Staff fills the return form (per item: quantity returned + condition: `good`/`damaged`/`lost`) -> `POST /staff/borrowings/{id}/return` -> `ProcessReturnRequest` validates -> `StaffBorrowingController::processReturn()` -> `BorrowingService::processReturn()`.
3. **Inside `processReturn()` (DB transaction)**, for each returned item:
   - `INSERT INTO return_records (borrow_id, item_id, staff_id, return_date, quantity_returned, item_condition, return_status='completed')`.
   - **If `item_condition != 'good'`** (i.e. `damaged` or `lost`):
     - `UPDATE items SET available_quantity = available_quantity - quantity_returned` (permanently remove from circulation).
     - `INSERT INTO maintenances (item_id, staff_id, return_id, issue_type='damage'|'lost', maintenance_status='pending')`.
   - **If `item_condition == 'good'`**: nothing else happens - the item was never decremented at approval time, so it's automatically "free" again for new reservations once the date range passes (the overlap query naturally stops counting it).
4. After processing all items: compare `SUM(borrowing_details.quantity_borrowed)` vs `SUM(return_records.quantity_returned)`. If returned total >= borrowed total -> `UPDATE borrowings SET borrow_status='returned', returned_at=now(), returned_by=staffId`.
5. **Trigger fires**: on the `borrowings` UPDATE, `is_overdue` recalculated - since `borrow_status` is now `'returned'` (not `'collected'`), `is_overdue` becomes `false` again (even if it was overdue while collected).
6. **Data changed**: new `return_records` row(s), possibly `items.available_quantity` decremented, possibly new `maintenances` row, `borrowings.borrow_status/returned_at/returned_by` updated.

## E. Maintenance of equipment

**Tables involved**: `maintenances`, `items`, `return_records`

1. A `maintenances` row was created automatically during Return (step D) with `maintenance_status='pending'`.
2. Staff opens `GET /staff/maintenance` -> `MaintenanceController::index()` lists pending maintenance (`with(['item','staff','returnRecord'])`).
3. Once the item is physically repaired/replaced, staff clicks Resolve -> `PUT /staff/maintenance/{id}/resolve` -> `MaintenanceController::resolve()` -> `BorrowingService::resolveMaintenance()`.
4. **Inside `resolveMaintenance()` (DB transaction)**:
   - Reads `quantity = maintenance->returnRecord->quantity_returned` (how many units were taken out).
   - `UPDATE items SET available_quantity = available_quantity + quantity` (restores stock - reverses the decrement from step D).
   - `UPDATE maintenances SET maintenance_status='resolved'`.
5. **Data changed**: `items.available_quantity` increased back, `maintenances.maintenance_status='resolved'`. The item is now fully available for new borrowings again.

---

# PART 5 — LARAVEL MVC ARCHITECTURE

## What is Model / View / Controller?

- **Model** = a PHP class that represents **one database table** and how to talk to it (Eloquent ORM). E.g., `app/Models/Borrowing.php` represents the `borrowings` table, and defines relationships like `borrowingDetails()`.
- **View** = the **Blade template** (`.blade.php` file) that turns data into HTML the browser shows.
- **Controller** = the "traffic cop" - receives the HTTP request, calls Models/Services to get/change data, then picks a View to render (or returns JSON for AJAX).

**Real example - viewing the borrowing list as a student**:
1. Browser requests `GET /borrowings`.
2. Route (`routes/web.php`) maps this to `BorrowingController::index()`.
3. Controller calls `Borrowing::with('borrowingDetails.item')->where('student_id', session('user_id'))->get()` - this is the **Model** layer doing the DB query.
4. Controller passes the result to `return view('borrowings.index', compact('borrowings'))` - this is the **View** layer.
5. `resources/views/borrowings/index.blade.php` loops over `$borrowings` and renders an HTML table.

## Controllers - purpose, route, model, view

| Controller | Purpose | Example Route | Models Used | View(s) |
|---|---|---|---|---|
| `AuthController` | Login, logout, register | `POST /login`, `POST /logout`, `GET/POST /register` | `Staff`, `Student` | `auth.login`, `auth.register` |
| `LandingController` | Public homepage with featured items/studios | `GET /` | `Item`, `Studio` | `landing` |
| `BrowseController` | Public browsing of items & studios (no login) | `GET /items`, `GET /studios`, `GET /studios/{studio}` | `Item`, `Studio` | `student.items`, `student.studios`, `student.studio-show` |
| `BookingController` | Student: create/view/edit/cancel studio bookings | `GET/POST /bookings`, `GET /bookings/{id}/edit` | `Booking`, `Studio` (+ `BookingService`) | `bookings.index/create/show/edit` |
| `BorrowingController` | Student: create/view/cancel equipment borrowings | `GET/POST /borrowings`, `GET /borrowings/{id}` | `Borrowing`, `BorrowingDetail`, `Item` (+ `BorrowingService`) | `borrowings.index/create/show` |
| `StaffController` | Staff dashboard (quick stats) | `GET /staff/dashboard` | `Borrowing`, `Booking` | `staff.dashboard` |
| `StaffBookingController` | Staff: view/update/cancel all bookings | `GET /staff/bookings`, `PUT /staff/bookings/{id}` | `Booking` (+ `BookingService`) | `staff.bookings.index/show` |
| `StaffBorrowingController` | Staff: approve/reject/collect/return borrowings | `PUT /staff/borrowings/{id}/approve` etc. | `Borrowing`, `BorrowingDetail` (+ `BorrowingService`) | `staff.borrowings.index/show` |
| `MaintenanceController` | Staff: view & resolve maintenance issues | `GET /staff/maintenance`, `PUT /staff/maintenance/{id}/resolve` | `Maintenance` (+ `BorrowingService`) | `staff.maintenance.index` |
| `InventoryController` | Staff: CRUD items & categories | `GET/POST /inventory`, `/inventory/categories` | `Item`, `Category` | `inventory.index/create/edit/categories` |
| `ItemImageController` | Staff: upload/reorder/delete item images | `POST /inventory/{id}/images` | `Item`, `ItemImage` (+ `ItemImageService`) | (returns JSON / re-renders inventory edit) |
| `StudioManagementController` | Staff/Admin: CRUD studios | `GET/POST /staff/studios` | `Studio` (+ `StudioService`) | `staff.studios.index/create/edit` |
| `StudioImageController` | Staff: upload/reorder/delete studio images | `POST /staff/studios/{id}/images` | `Studio`, `StudioImage` (+ `StudioService`) | (JSON / re-render) |
| `StudioUnavailabilityController` | Staff: manage studio calendar/closures | `GET /staff/studios/{id}/calendar` | `StudioUnavailability`, `Booking` | `staff.studios.calendar` |
| `GlobalSearchController` | Search across items/students/staff/bookings/etc. | `GET /staff/search`, `GET /search` | many (uses `Search::apply()`) | `search.results` |
| `AdminDashboardController` | Admin KPI dashboard (cached) | `GET /admin/dashboard` | `Booking`, `Borrowing`, `Item`, `Studio`, `Maintenance` | `admin.dashboard` |
| `UserManagementController` | Admin: CRUD staff & student accounts | `GET /admin/users`, `POST /admin/users/staff` | `Staff`, `Student` | `admin.users.index/create-staff/edit-staff/edit-student` |
| `ReportController` | Admin: generate/view/export reports | `GET /admin/reports`, `/export/pdf`, `/export/csv` | `Booking`, `Borrowing`, `Item`, `BorrowingDetail` | `admin.reports.index`, `admin.reports.pdf` |
| `SettingsController` | Any logged-in user: profile/password/sessions | `GET/PUT /staff/settings`, `/student/settings` | `Staff` or `Student` (based on session) | `settings.index` (+ `staff/student.settings.index`) |

## Models - table represented, relationships used

| Model | Table | Key relationships |
|---|---|---|
| `Student` | `students` | `hasMany(Booking)`, `hasMany(Borrowing)` |
| `Staff` | `staff` | `hasMany(Borrowing)`, `hasMany(ReturnRecord)`, `hasMany(Maintenance)`, `hasMany(Booking)` |
| `Category` | `categories` | `hasMany(Item)` |
| `Item` | `items` | `belongsTo(Category)`, `hasMany(ItemImage)`, `hasOne(ItemImage)` as `primaryImage`, `hasMany(BorrowingDetail)`, `hasMany(ReturnRecord)`, `hasMany(Maintenance)` |
| `ItemImage` | `item_images` | `belongsTo(Item)` |
| `Studio` | `studios` | `hasMany(StudioImage)`, `hasOne(StudioImage)` as `primaryImage`, `hasMany(StudioUnavailability)`, `hasMany(Booking)` |
| `StudioImage` | `studio_images` | `belongsTo(Studio)` |
| `StudioUnavailability` | `studio_unavailability` | `belongsTo(Studio)`, `belongsTo(Staff)`, scopes `active()`/`overlapping()` |
| `Booking` | `bookings` | `belongsTo(Student)`, `belongsTo(Staff)`, `belongsTo(Studio)` |
| `Borrowing` | `borrowings` | `belongsTo(Student)`, `belongsTo(Staff)`, `belongsTo(Staff)` as `collectedByStaff`/`returnedByStaff`, `hasMany(BorrowingDetail)`, `hasMany(ReturnRecord)` |
| `BorrowingDetail` | `borrowing_details` | `belongsTo(Borrowing)`, `belongsTo(Item)` |
| `ReturnRecord` | `return_records` | `belongsTo(Borrowing)`, `belongsTo(Item)`, `belongsTo(Staff)`, `hasOne(Maintenance)` |
| `Maintenance` | `maintenances` | `belongsTo(Item)`, `belongsTo(Staff)`, `belongsTo(ReturnRecord)` |

## Views - what data, from which controller

- **`borrowings/index.blade.php`** - receives `$borrowings` (a collection with eager-loaded `borrowingDetails.item`) from `BorrowingController::index()`; shows the student's own borrowing history with status badges.
- **`staff/borrowings/show.blade.php`** - receives `$borrowing` (with `student`, `staff`, `borrowingDetails.item`, `returnRecords.item` eager-loaded) from `StaffBorrowingController::show()`; shows full details + Approve/Reject/Collect/Return action buttons depending on `borrow_status`.
- **`admin/dashboard.blade.php`** - receives multiple cached KPI arrays (`pendingBookings`, `pendingBorrowings`, `lowStockItems`, `studioUtilization`, etc.) from `AdminDashboardController::index()`.
- **`admin/reports/index.blade.php`** - receives `$results` (paginated), `$summary` (aggregate counts), `$filters`, `$studios`, `$categories` from `ReportController::index()`.
- **`admin/reports/pdf.blade.php`** - same data shape as above but unpaginated (`$results->get()`), rendered by `barryvdh/laravel-dompdf` into a downloadable PDF.

---

# PART 6 — ROUTING (`routes/web.php`)

The file has **91 routes**, grouped by middleware.

### Public routes (no login required)

| URL | Method | Controller@Method | Middleware | Purpose |
|---|---|---|---|---|
| `/` | GET | `LandingController@index` | - | Homepage with featured items/studios |
| `/items` | GET | `BrowseController@items` | - | Public item catalogue |
| `/studios` | GET | `BrowseController@studios` | - | Public studio catalogue |
| `/studios/{studio}` | GET | `BrowseController@studio` | - | Public studio detail page |

### Auth routes (guest)

| URL | Method | Controller@Method | Middleware | Purpose |
|---|---|---|---|---|
| `/login` | GET | `AuthController@showLogin` | - | Show login form |
| `/login` | POST | `AuthController@login` | `throttle:5,1` | Process login (max 5/min - brute-force protection) |
| `/logout` | POST | `AuthController@logout` | - | Destroy session |
| `/register` | GET | `AuthController@showRegister` | - | Show registration form |
| `/register` | POST | `AuthController@register` | `throttle:10,1` | Create new student account (max 10/min) |

### Staff routes (`middleware('auth.staff')`)

| URL | Method | Controller@Method | Purpose |
|---|---|---|---|
| `/staff/dashboard` | GET | `StaffController@dashboard` | Staff home with quick stats |
| `/staff/settings` | GET | `SettingsController@index` | Account settings page |
| `/staff/settings/profile` | PUT | `SettingsController@updateProfile` | Update name/email/phone |
| `/staff/settings/password` | PUT | `SettingsController@updatePassword` | Change password |
| `/staff/settings/logout-other-sessions` | POST | `SettingsController@logoutOtherSessions` | Kill other active sessions |
| `/inventory/categories` | GET/POST | `InventoryController@categories/storeCategory` | List/create categories |
| `/inventory/categories/{id}` | DELETE | `InventoryController@destroyCategory` | Delete category |
| `/inventory` | GET/POST | `InventoryController@index/store` | List/create items |
| `/inventory/create` | GET | `InventoryController@create` | New item form |
| `/inventory/{id}/edit` | GET | `InventoryController@edit` | Edit item form |
| `/inventory/{id}` | PUT/DELETE | `InventoryController@update/destroy` | Update/delete item |
| `/inventory/{id}/images` | POST | `ItemImageController@store` | Upload item images |
| `/inventory/{id}/images/reorder` | POST | `ItemImageController@reorder` | Reorder gallery |
| `/inventory/{id}/images/{imageId}/primary` | PUT | `ItemImageController@setPrimary` | Set primary image |
| `/staff/bookings` | GET | `StaffBookingController@index` | All bookings list |
| `/staff/bookings/{id}` | GET/PUT/DELETE | `StaffBookingController@show/update/destroy` | View/update/cancel booking |
| `/staff/borrowings` | GET | `StaffBorrowingController@index` | All borrowing requests |
| `/staff/borrowings/{id}` | GET | `StaffBorrowingController@show` | Borrowing detail |
| `/staff/borrowings/{id}/approve` | PUT | `StaffBorrowingController@approve` | Approve -> `reserved` |
| `/staff/borrowings/{id}/reject` | PUT | `StaffBorrowingController@reject` | Reject |
| `/staff/borrowings/{id}/collect` | PUT | `StaffBorrowingController@collect` | Mark collected |
| `/staff/borrowings/{id}/return` | POST | `StaffBorrowingController@processReturn` | Process return |
| `/staff/maintenance` | GET | `MaintenanceController@index` | Maintenance queue |
| `/staff/maintenance/{id}/resolve` | PUT | `MaintenanceController@resolve` | Resolve maintenance |
| `/staff/search` | GET | `GlobalSearchController@index` | Staff global search |
| `/staff/studios` | GET/POST | `StudioManagementController@index/store` | List/create studios |
| `/staff/studios/create` | GET | `StudioManagementController@create` | New studio form |
| `/staff/studios/{id}/edit` | GET | `StudioManagementController@edit` | Edit studio form |
| `/staff/studios/{id}` | PUT/GET | `StudioManagementController@update/show` | Update/view studio |
| `/staff/studios/{id}/images...` | POST | `StudioImageController@store/reorder/setPrimary` | Studio gallery management |
| `/staff/studios/{id}/calendar` | GET | `StudioUnavailabilityController@calendar` | Calendar view |
| `/staff/studios/{id}/calendar-data` | GET | `StudioUnavailabilityController@calendarData` | AJAX calendar events JSON |
| `/staff/studios/{id}/unavailability` | POST/DELETE | `StudioUnavailabilityController@store/destroy` | Add/remove closure period |

### Student routes (`middleware('auth.student')`)

| URL | Method | Controller@Method | Purpose |
|---|---|---|---|
| `/student/dashboard` | GET | `StudentController@dashboard` | Student home |
| `/student/settings...` | GET/PUT/POST | `SettingsController@*` | Account settings |
| `/bookings/create` | GET | `BookingController@create` | New booking form |
| `/studios/{studio}/availability` | GET | `BookingController@availability` | AJAX time-slot availability |
| `/bookings` | GET/POST | `BookingController@index/store` | List / submit booking |
| `/bookings/{id}/edit` | GET | `BookingController@edit` | Edit booking form |
| `/bookings/{id}` | GET/PUT/DELETE | `BookingController@show/update/destroy` | View/edit/cancel booking |
| `/borrowings/create` | GET | `BorrowingController@create` | New borrowing form |
| `/borrowings/availability` | GET | `BorrowingController@availability` | AJAX item availability |
| `/borrowings` | GET/POST | `BorrowingController@index/store` | List / submit borrowing |
| `/borrowings/{id}` | GET/DELETE | `BorrowingController@show/destroy` | View/cancel borrowing |
| `/search` | GET | `GlobalSearchController@index` | Student global search |

### Admin-only routes (`middleware('auth.admin')`)

| URL | Method | Controller@Method | Purpose |
|---|---|---|---|
| `/admin/dashboard` | GET | `AdminDashboardController@index` | KPI dashboard |
| `/admin/reports` | GET | `ReportController@index` | View reports |
| `/admin/reports/export/pdf` | GET | `ReportController@exportPdf` | Download PDF |
| `/admin/reports/export/csv` | GET | `ReportController@exportCsv` | Download CSV |
| `/admin/users` | GET | `UserManagementController@index` | List staff + students |
| `/admin/users/staff/...` | GET/POST/PUT/DELETE | `UserManagementController@*Staff` | CRUD staff accounts |
| `/admin/users/student/...` | GET/PUT/DELETE | `UserManagementController@*Student` | Edit/delete student accounts |
| `/staff/studios/{id}` | DELETE | `StudioManagementController@archive` | Archive studio |
| `/staff/studios/{id}/images/{imageId}` | DELETE | `StudioImageController@destroy` | Delete studio image |
| `/inventory/{id}/images/{imageId}` | DELETE | `ItemImageController@destroy` | Delete item image |

## Full request lifecycle (Browser -> Database -> Browser)

Using "Student submits a borrowing request" as the example:

1. **Browser**: Student fills the form on `/borrowings/create` and clicks Submit -> browser sends `POST /borrowings` with form data + CSRF token (from `@csrf` directive) + the session cookie.
2. **`public/index.php`** - the single entry point - boots Laravel via `vendor/autoload.php` and `bootstrap/app.php`.
3. **Global middleware** runs first: session middleware loads the session from the `sessions` DB table using the cookie; `ValidateCsrfToken` checks the CSRF token matches.
4. **Route middleware**: `auth.student` (`RequireStudent`) checks `session('user_type') === 'student'` - if not, redirect to `/login`.
5. **Routing**: `routes/web.php` matches `POST /borrowings` -> `BorrowingController::store(StoreBorrowingRequest $request)`.
6. **FormRequest validation** (`StoreBorrowingRequest`) runs *before* the controller method body - checks dates, required fields, and via `withValidator()` calls `BorrowingService::getAvailableQuantity()` for each item. If invalid -> redirect back with `$errors` (no controller code runs).
7. **Controller** calls `BorrowingService::createBorrowing($data, session('user_id'))`.
8. **Service** opens a `DB::transaction()`, locks rows (`lockForUpdate`), re-checks availability, then uses **Eloquent Models** (`Borrowing::create()`, `BorrowingDetail::create()`) to run `INSERT` statements against **PostgreSQL**.
9. **PostgreSQL** executes the inserts; the `trg_borrowings_set_overdue` trigger fires automatically on the `borrowings` insert.
10. **Service** returns the new `Borrowing` model back to the **Controller**.
11. **Controller** returns `redirect()->route('borrowings.index')->with('success', '...')`.
12. **Browser** receives an HTTP redirect (302), follows it to `GET /borrowings`, which goes through the same middleware -> `BorrowingController::index()` -> queries the DB again -> renders `borrowings/index.blade.php` -> **HTML sent back to the browser**, showing the new "pending" request with a success flash message.

---

# PART 7 — SERVICES

A **Service class** holds business logic that is too complex/important to live inside a Controller. Controllers stay "thin" (just receive request -> call service -> return view/redirect); Services hold the "rules of the business".

## `BookingService`

- **Why it was created**: Booking a studio involves several rules that have nothing to do with HTTP (studio status checks, time-overlap math, maintenance-period checks). Putting this in the controller would make `BookingController` huge and hard to test.
- **Problem it solves**: Prevents **double-booking** of a studio and prevents booking a studio that's under maintenance/blocked.
- **Flow**:
  1. `createBooking()` - locks the studio row (`lockForUpdate`), checks `assertStudioBookable()` (studio status + `studio_unavailability` overlap), checks `hasConflict()` (time-overlap against existing bookings), then creates the booking as `confirmed`.
  2. `updateBooking()` - same checks again, but excludes the booking's own ID from the conflict check (so editing your own booking to the same time doesn't conflict with itself).
  3. `cancelBooking()` - simple status update (student self-cancel or staff cancel).
  4. `autoCompletePastBookings()` - bulk-flips every `confirmed` booking whose `booking_date + end_time` has already passed to `completed`. No staff action involved; called on every booking index/show page load (student and staff) and by a 5-minute scheduled job in `routes/console.php` as a freshness safety net.
  5. `getAvailability()` - builds the hour-by-hour grid shown in the AJAX availability widget.
- **Used by**: `BookingController` (student create/edit/store/update/index/show), `StaffBookingController` (cancel + index/show).

**Real example**: Two students both try to book Studio A, 2pm-3pm, on the same day, within the same second. Both pass the FormRequest validation. But `createBooking()` does `Studio::lockForUpdate()` first - PostgreSQL makes the second transaction **wait** until the first one commits. By the time the second transaction's `hasConflict()` runs, the first booking is already saved, so it correctly detects the conflict and throws `BookingConflictException`.

## `BorrowingService`

- **Why it was created**: This is the most complex business logic in the whole system - calculating "how many units of an item are free for a given date range" considering overlapping reservations, and managing the full lifecycle (`pending -> reserved -> collected -> returned`, or `rejected`/`cancelled`).
- **Problem it solves**: Without it, two students could both reserve the last 5 guitars for overlapping dates, or staff could approve more than physically exists.
- **Flow**:
  1. `getAvailableQuantity($itemId, $pickupDate, $returnDate, $excludeBorrowId)` - the core formula: `items.available_quantity - SUM(quantity_borrowed from overlapping pending/reserved/collected borrowings)`.
  2. `createBorrowing()` - transaction + lock + re-check availability per item -> creates `borrowings` (status `pending`) + `borrowing_details` rows.
  3. `approveBorrowing()` - transaction + lock + **re-check availability again** (race-safe) -> sets status `reserved`.
  4. `rejectBorrowing()` / `cancelBorrowing()` - simple status changes, no stock math (nothing was ever decremented).
  5. `collectBorrowing()` - sets status `collected`, records who/when.
  6. `processReturn()` - records `return_records`; if condition != `good`, decrements `items.available_quantity` and creates a `maintenances` ticket; if all items returned, sets status `returned`.
  7. `resolveMaintenance()` - increments `items.available_quantity` back when a damaged/lost item is fixed/replaced.
- **Used by**: `BorrowingController` (student create/store/availability), `StaffBorrowingController` (approve/reject/collect/return), `MaintenanceController` (resolve).

**Real example**: Item "Microphone Stand" has `available_quantity = 5`. Student A reserves 3 units for 10-12 June (status `pending`). Student B tries to reserve 3 units for 11-13 June. `getAvailableQuantity()` sees A's reservation overlaps (11-12 overlap) and counts its 3 units as "reserved", so it returns `5 - 3 = 2` available. Student B's request for 3 fails validation with "Only 2 unit(s) available...".

## `StudioService`

- **Why it was created**: Handles studio image gallery management (upload, compress, reorder, set primary) - similar but separate from items.
- **Problem it solves**: Keeps the "which image is primary" and "ordering" logic consistent and out of the controller; handles file storage (`$file->store('studios', 'public')`).
- **Flow**: `storeImages()` validates count limits, compresses/stores each uploaded file with a generated hashed filename, assigns `position` and `is_primary` (first upload = primary if none exists yet); `reorderImages()` updates `position` values; `setPrimaryImage()` flips `is_primary` flags so only one is true at a time; `deleteImage()` removes the file from disk and the DB row, and promotes another image to primary if the deleted one was primary.
- **Used by**: `StudioImageController`, `StudioManagementController`.

## `ItemImageService`

- **Why it was created**: Exactly the same pattern as `StudioService` but for `items`/`item_images` - avoids duplicating the upload/compress/reorder/primary logic between items and studios (though as separate classes - a possible future improvement is a shared trait/base class).
- **Problem it solves**: Same as above - consistent gallery management + image compression for inventory items.
- **Flow**: `storeImages()` (validate, compress, store, set primary if first), `reorderImages()`, `setPrimaryImage()`, `deleteImage()`.
- **Used by**: `ItemImageController`, `InventoryController`.

---

# PART 8 — AUTHENTICATION & SECURITY

## Important: this project does NOT use Laravel's built-in Auth/Breeze

There is **no `users` table** (it was dropped in a migration) and **no `Auth::login()`/`Auth::user()`** calls. Instead, authentication is **fully custom**, built directly on top of Laravel's **Session** facade. Two tables can log in: `students` and `staff`.

## Login flow - step by step

1. User visits `GET /login` -> `AuthController::showLogin()` -> renders `auth/login.blade.php` (a simple email+password form with `@csrf`).
2. User submits `POST /login` (rate-limited to **5 attempts per minute** via `throttle:5,1` to slow down brute-force attacks).
3. `AuthController::login()`:
   - `$request->validate(['email'=>'required|email','password'=>'required'])`.
   - **Try staff first**: `Staff::where('email', $request->email)->where('status','active')->first()`.
     - If found AND `Hash::check($request->password, $staff->password)` is true:
       - `Session::regenerate()` - generates a brand new session ID (prevents **session fixation** attacks - an attacker can't pre-set a session ID and hijack it after login).
       - Store `user_id`, `user_name`, `user_role` (`admin` or `staff`), `user_type='staff'`, `is_admin` in the session.
       - Update `last_login_at`.
       - Redirect to `admin.dashboard` (if `is_admin`) or `staff.dashboard`.
   - **If no staff match**, try `Student::where('email',...)->where('status','active')->first()` with the same `Hash::check()` -> set `user_type='student'`, redirect to `student.dashboard`.
   - **If neither matches**: run `Hash::check($request->password, BLIND_TIMING_HASH)` against a dummy bcrypt hash - this makes a failed login take roughly the same amount of time whether the email exists or not (**timing-attack mitigation**), then logs the failed attempt (email + IP + timestamp, **never the password**) and returns a generic error: *"These credentials do not match our records."* - the same message is used whether the email doesn't exist or the password is wrong, so attackers can't enumerate valid emails.
4. **Error handling**: `QueryException` (DB problem) and generic `\Throwable` are caught separately, logged with context, and shown as friendly generic messages - no stack traces leak to the user.

## Logout flow

1. `POST /logout` -> `AuthController::logout()`.
2. `Session::invalidate()` - destroys all session data and removes the session row from the `sessions` table.
3. `Session::regenerateToken()` - rotates the CSRF token (so a stolen CSRF token from before logout becomes useless).
4. Redirect to `/login`.

## Session

- **Driver**: `database` (`SESSION_DRIVER=database`) - sessions are stored as rows in a `sessions` table, not in files or cookies directly. The browser only holds a session **ID** in a cookie.
- **Lifetime**: 120 minutes of inactivity before expiry.
- Every middleware-protected page reads `session('user_type')`, `session('user_id')`, `session('is_admin')` to know who's logged in and what role they have - this **is** the authentication state.

## Password hashing

- `Hash::make($password)` (bcrypt, `BCRYPT_ROUNDS=12` from `.env`) is used everywhere a password is set: registration (`AuthController::register`), admin creating staff/student accounts (`UserManagementController`), and self-service password change (`SettingsController::updatePassword`).
- `Hash::check($plain, $hashed)` is used to verify - the **plain password is never stored or compared directly**; bcrypt is a one-way function, so even the database admin cannot "read" anyone's password.

## CSRF (Cross-Site Request Forgery) protection

- Laravel's default `ValidateCsrfToken` middleware is active for all web routes (not removed from `bootstrap/app.php`).
- Every form that changes data (`POST`/`PUT`/`DELETE`) includes `@csrf`, which renders a hidden `<input type="hidden" name="_token" value="...">`.
- When the form is submitted, Laravel compares this token against the one stored in the session - if they don't match (e.g., a malicious site tried to auto-submit a form to your app), the request is **rejected with a 419 error**.

## Validation

- **FormRequest classes** (13 of them) handle validation for the "important" write operations - e.g., `StoreBorrowingRequest` validates dates and re-checks stock availability via `withValidator()`.
- Simpler operations (`AuthController::login/register`, `UserManagementController` staff/student CRUD) use inline `$request->validate([...])`.
- If validation fails, Laravel automatically redirects back with the old input and an `$errors` bag, which Blade templates display via `@error('field') ... @enderror`.

## Middleware (RBAC enforcement)

Three custom middleware classes, registered as aliases in `bootstrap/app.php`:

```php
'auth.staff'   => RequireStaff::class,
'auth.admin'   => RequireAdmin::class,
'auth.student' => RequireStudent::class,
```

- **`RequireStudent`**: `if (session('user_type') !== 'student') return redirect()->route('login');`
- **`RequireStaff`**: `if (session('user_type') !== 'staff') return redirect()->route('login');` (covers both staff and admin, since admins are staff with `is_admin=true`)
- **`RequireAdmin`**: first checks `session('user_type') === 'staff'` (else -> login), **then** checks `session('is_admin')` - if false, redirects to `staff.dashboard` with an error ("You do not have permission..."). So admin routes require **both** being staff AND `is_admin = true`.

These middleware are applied as **route groups** in `routes/web.php` (`Route::middleware('auth.admin')->group(function () {...})`), so every route inside that closure is automatically protected - there's no way to "forget" the check on an individual route.

## RBAC summary table

| Role | `user_type` | `is_admin` | Can access |
|---|---|---|---|
| Student | `student` | n/a | `/student/*`, `/bookings/*`, `/borrowings/*`, `/search` |
| Staff | `staff` | `false` | `/staff/*`, `/inventory/*` |
| Admin | `staff` | `true` | Everything Staff can, **plus** `/admin/*` |

---

# PART 9 — REPORTING MODULE

`ReportController` (admin-only, behind `auth.admin`) generates **3 report types**: Bookings, Borrowings, Inventory. All three follow the same pattern.

## Where the data comes from

- **Bookings report** (`bookingsQuery`): `Booking::with(['student','studio'])`, filtered by date range (`booking_date`), `booking_status`, and `studio_id`. Summary counts (`confirmed`/`completed`/`cancelled`/`total`) computed via `groupBy('booking_status')`.
- **Borrowings report** (`borrowingsQuery`): `Borrowing::with(['student','borrowingDetails.item'])`, filtered by date range (`pickup_date`) and `borrow_status`. Summary includes counts per status (`pending`/`reserved`/`collected`/`returned`/`rejected`/`cancelled`) and total items borrowed (`BorrowingDetail::whereIn('borrow_id', ...)->sum('quantity_borrowed')`).
- **Inventory report** (`inventoryQuery`): `Item::with('category')`, filtered by date range (`date_added`), `item_status`, `category_id`. Summary includes `total_items`, `total_quantity`, `total_available`, and `low_stock` count (using the same `whereRaw('available_quantity <= quantity * 0.2')` rule as `v_inventory_low_stock`).

## How the on-screen report is generated

1. Admin visits `GET /admin/reports?type=borrowings&date_from=...&date_to=...` -> `ReportFilterRequest` validates filter inputs.
2. `ReportController::index()` calls the matching private method (`bookingsReport`/`borrowingsReport`/`inventoryReport`) with `$paginate = true` -> results are `->paginate(15)->withQueryString()` (so pagination links keep the filters).
3. Returns `view('admin.reports.index', [...])` - shows a filter form, summary cards, and a paginated table.

## How the PDF is generated

1. Admin clicks "Export PDF" -> `GET /admin/reports/export/pdf?type=...&...` -> `ReportController::exportPdf()`.
2. Same report method is called but with `$paginate = false` -> gets **all** matching rows (`->get()`), not just one page - because a PDF report needs the complete dataset.
3. `Pdf::loadView('admin.reports.pdf', [...])->setPaper('a4','portrait')` - this uses the **`barryvdh/laravel-dompdf`** package, which renders the `admin/reports/pdf.blade.php` Blade template (plain HTML/CSS, no JS) into a PDF document **server-side**.
4. `$pdf->download($filename)` streams the PDF back to the browser as a file download (`borrowings-report-20260615_143000.pdf`).

## How the CSV is generated

1. Admin clicks "Export CSV" -> `GET /admin/reports/export/csv?...` -> `ReportController::exportCsv()`.
2. Same unpaginated report data is fetched.
3. `response()->streamDownload($callback, $filename, ['Content-Type'=>'text/csv'])` - Laravel streams the response instead of building the whole file in memory first (more memory-efficient for large exports).
4. Inside the callback: writes a **UTF-8 BOM** (`\xEF\xBB\xBF`) first (so Excel opens the CSV with correct special characters), then `fputcsv()` writes a header row followed by one row per record, using the dedicated `writeBookingsCsv`/`writeBorrowingsCsv`/`writeInventoryCsv` helper methods (each maps model fields to human-readable columns, e.g. `ucfirst($booking->booking_status)`).

## Full flow diagram

```
Admin (browser)
   |
   |  GET /admin/reports/export/pdf?type=borrowings&date_from=...
   v
ReportFilterRequest  (validates filters)
   v
ReportController::exportPdf()
   v
borrowingsReport($filters, paginate=false)
   v
Borrowing::with(['student','borrowingDetails.item'])
   -> WHERE pickup_date BETWEEN ... AND borrow_status = ...
   -> PostgreSQL
   v
Collection of Borrowing models
   v
Pdf::loadView('admin.reports.pdf', [...])  (DomPDF renders HTML -> PDF)
   v
$pdf->download(...)  -> Browser downloads "borrowings-report-....pdf"
```

---

# PART 10 — VIVA PREPARATION (100 Questions & Answers)

Each answer gives a **technical** explanation first, then a **simple** one-line summary you can say out loud in the viva.

## A. Database (1-10)

**1. Why did you choose PostgreSQL instead of MySQL?**
PostgreSQL supports advanced features used in this project: PL/pgSQL functions/triggers, `pg_trgm` fuzzy-search indexes, and `CHECK` constraints with complex expressions.
*Simple: "PostgreSQL lets the database itself enforce rules and do smart text search, which MySQL does less well."*

**2. What is a Primary Key, and give an example from your schema?**
A column that uniquely identifies each row, e.g. `borrowings.borrow_id` (BIGSERIAL, auto-incrementing).
*Simple: "It's like an IC number for each row - no duplicates allowed."*

**3. What is a Foreign Key, and give an example?**
A column referencing another table's PK, e.g. `borrowing_details.item_id` references `items.item_id`, enforcing that you can't borrow an item that doesn't exist.
*Simple: "It links two tables together and stops invalid links."*

**4. Explain the difference between `quantity` and `available_quantity` in the `items` table.**
`quantity` is the total units owned; `available_quantity` is units **not permanently removed** due to damage/loss. It is NOT decremented for normal reservations - "free for a date range" is calculated dynamically by `BorrowingService::getAvailableQuantity()`.
*Simple: "Quantity = how many we own. Available = how many aren't broken/lost. 'Free right now for these dates' is calculated on the fly."*

**5. Why is `borrowing_details` a separate table instead of columns on `borrowings`?**
Because one borrowing request can include multiple different items with different quantities - this is a many-to-many relationship between `borrowings` and `items`, requiring a junction table.
*Simple: "One borrow request can have many items, so we need a separate table to list them."*

**6. What does `ON DELETE CASCADE` mean, and where is it used?**
When the parent row is deleted, child rows are automatically deleted too. Used for `item_images`/`studio_images` (delete an item -> its photos go too) and `borrowing_details` (delete a borrowing -> its line items go too).
*Simple: "Delete the parent, and its 'children' records get deleted automatically."*

**7. What does `ON DELETE RESTRICT` mean, and where is it used?**
Prevents deleting a parent row if child rows still reference it. Used for `students` (can't delete a student with borrowing history) and `items` referenced by `borrowing_details`.
*Simple: "You can't delete something if other records still depend on it."*

**8. What does `ON DELETE SET NULL` mean, and where is it used?**
When the parent is deleted, the FK column in the child becomes NULL instead of blocking or cascading. Used for `borrowings.staff_id/collected_by/returned_by` -> if a staff account is deleted, the borrowing record stays but loses the "who approved it" link.
*Simple: "If the staff account is removed, the record stays - it just forgets who did it."*

**9. Is the database normalized? To what level?**
Yes, 3NF (Third Normal Form) - no repeating groups, every non-key column depends only on its table's primary key, no transitive dependencies.
*Simple: "No duplicate/redundant data; every piece of information is stored in exactly one place."*

**10. What is the `CHECK` constraint and give two examples?**
A rule enforced by the database on every insert/update. Examples: `chk_borrowings_dates` requires `return_date >= pickup_date`; `chk_bookings_time` requires `end_time > start_time`.
*Simple: "The database itself refuses to save bad data, even if the app has a bug."*

## B. Laravel (11-20)

**11. What version of Laravel are you using, and why?**
Laravel 12 (PHP 8.2+). Chosen for its mature ORM (Eloquent), Blade templating, built-in validation, migrations, and scheduler - all used heavily in this project.
*Simple: "It's the latest stable Laravel, and it gives us everything (DB, views, validation) in one framework."*

**12. What is Eloquent ORM?**
An Object-Relational Mapper - lets you work with database rows as PHP objects/models (`Item::find(1)`) instead of writing raw SQL, while still allowing relationships (`$item->category`).
*Simple: "It lets PHP code talk to the database using objects instead of writing SQL by hand."*

**13. What is a migration, and why use them instead of creating tables in pgAdmin directly?**
A migration is a PHP class describing a schema change (`Schema::create`/`Schema::table`), version-controlled in Git. Running `php artisan migrate` applies them in order, so every developer/environment ends up with an identical schema.
*Simple: "It's a script for building the database, so everyone's database looks the same."*

**14. What is a Service class, and why did you introduce them?**
A plain PHP class holding business logic (`BookingService`, `BorrowingService`) that's reused across controllers, keeping controllers thin and the logic testable/centralized.
*Simple: "It's where the 'real rules' of the app live, separate from the web request handling."*

**15. What is a FormRequest, and how is it different from validating inside the controller?**
A dedicated class (e.g. `StoreBorrowingRequest`) that handles authorization + validation **before** the controller method runs - keeps controllers clean and validation reusable/testable.
*Simple: "It's a separate file that checks the form data is correct before the controller even sees it."*

**16. What is Eloquent eager loading (`with()`), and why is it important?**
`with('relation')` pre-loads related models in a single extra query, avoiding the **N+1 query problem** (1 query for the list + N queries for each row's relation). E.g. `Borrowing::with('borrowingDetails.item')`.
*Simple: "It fetches related data in one go instead of one-by-one, making pages load faster."*

**17. What is `Cache::remember()` used for in this project?**
`AdminDashboardController` wraps its 5 expensive aggregate queries (counts, sums, groupings for KPIs) in `Cache::remember('key', $ttl, fn() => ...)` with 10-15 minute TTLs, so the dashboard doesn't re-run heavy queries on every page load.
*Simple: "The dashboard's numbers are saved for a few minutes so we don't recalculate them every time someone opens the page."*

**18. What is the Laravel Scheduler, and what scheduled tasks does this project have?**
`routes/console.php` defines tasks run by `php artisan schedule:run` (triggered by an OS cron every minute): refreshing overdue borrowings daily (`fn_refresh_overdue_borrowings`), and running/cleaning/monitoring backups (`backup:run`, `backup:clean`, `backup:monitor`).
*Simple: "It's like an alarm clock for the app - runs certain tasks automatically every day."*

**19. What package is used for PDF generation, and how does it work?**
`barryvdh/laravel-dompdf` - it takes a Blade view (plain HTML/CSS) and renders it server-side into a PDF file using the DomPDF library.
*Simple: "It turns an HTML page into a downloadable PDF on the server."*

**20. What package is used for backups, and what does it back up?**
`spatie/laravel-backup` - backs up `storage/app/public` (uploaded images), the `.env` file, and a `pg_dump` of the PostgreSQL database into a single zip archive.
*Simple: "One package that zips up our files and database into a backup automatically."*

## C. Security (21-32)

**21. How does this system authenticate users, since there's no `users` table?**
Custom session-based auth: `AuthController::login()` checks `students`/`staff` tables directly with `Hash::check()`, then stores `user_id`, `user_type`, `user_role`, `is_admin` in the session (`SESSION_DRIVER=database`).
*Simple: "We check the password against our own student/staff tables and remember who's logged in using sessions."*

**22. How are passwords stored?**
Hashed with **bcrypt** via `Hash::make()` (12 rounds, `BCRYPT_ROUNDS=12`). Never stored or logged in plain text. `Hash::check()` verifies without ever decrypting.
*Simple: "Passwords are scrambled one-way - even we can't see the original password."*

**23. What is session fixation, and how is it prevented here?**
An attack where an attacker sets a victim's session ID before login, then uses that same ID after the victim logs in. Prevented by calling `Session::regenerate()` immediately on successful login, issuing a brand-new session ID.
*Simple: "We give the user a fresh session ID the moment they log in, so an old/stolen ID becomes useless."*

**24. What is CSRF, and how is it prevented?**
Cross-Site Request Forgery - a malicious site tricks a logged-in user's browser into submitting a request to your app. Prevented by Laravel's `ValidateCsrfToken` middleware + `@csrf` hidden token field in every form; mismatched/missing tokens get a 419 error.
*Simple: "Every form has a secret token that proves the request really came from our own page."*

**25. How is SQL Injection prevented?**
Eloquent/Query Builder use **parameter binding** automatically (`Item::where('item_id', $id)` becomes a prepared statement). The only `whereRaw()` calls in the codebase use hardcoded literals (`available_quantity <= quantity * 0.2`), never user input.
*Simple: "We let Laravel build the SQL safely - user input never gets pasted directly into a query."*

**26. How is XSS (Cross-Site Scripting) prevented?**
Blade's `{{ $variable }}` syntax **auto-escapes** HTML special characters. The codebase contains **zero** uses of the unescaped `{!! !!}` syntax.
*Simple: "Anything a user types is automatically shown as plain text, not run as code."*

**27. What is RBAC, and how is it implemented here?**
Role-Based Access Control - users get permissions based on their role (Student/Staff/Admin). Implemented via three custom middleware (`RequireStudent`, `RequireStaff`, `RequireAdmin`) applied to route groups in `routes/web.php`.
*Simple: "Different users see different menus and pages depending on whether they're a student, staff, or admin."*

**28. Explain the login throttle - what does `throttle:5,1` mean?**
Rate-limits the `/login` POST route to **5 attempts per 1 minute** per IP/session, slowing brute-force password guessing.
*Simple: "If someone tries to log in too many times too fast, they get temporarily blocked."*

**29. How does the system avoid revealing whether an email exists during login?**
On failed login, it returns the **same generic message** ("These credentials do not match our records") whether the email doesn't exist or the password is wrong, and runs `Hash::check()` against a dummy bcrypt hash (`BLIND_TIMING_HASH`) even when no account matches, so the response time doesn't leak information either.
*Simple: "Wrong email and wrong password look exactly the same to an attacker - same message, same timing."*

**30. How are uploaded files (images) kept safe?**
`StoreItemImagesRequest`/`StoreStudioImagesRequest` validate `mimes:jpg,jpeg,png,webp` and `max:5120` (5MB), max 10 files. Files are stored via `$file->store('items','public')`, which generates a **random hashed filename** - the original filename (which could contain path-traversal characters) is never used.
*Simple: "Only image files under 5MB are accepted, and they're saved with random new names for safety."*

**31. Is `is_admin` exploitable by a normal user (privilege escalation)?**
No - `is_admin` is never mass-assigned from `$request->all()`. `UserManagementController` explicitly sets it via `$request->has('is_admin')` (a boolean check on a checkbox only an admin's form contains), and only admins can reach that route at all.
*Simple: "Only an admin, on an admin-only page, can ever set someone as admin."*

**32. What happens if `APP_DEBUG=true` in production?**
Laravel shows detailed error pages (Whoops/Ignition) including stack traces, `.env` values, and SQL queries on any unhandled exception - a serious information-disclosure risk. This project's `.env` currently has `APP_DEBUG=true` with a comment warning it must be `false` in production.
*Simple: "Debug mode shows everything (including secrets) when something crashes - must be turned off for real use."*

## D. MVC (33-40)

**33. What does MVC stand for and what does each part do?**
Model (data/DB), View (UI/templates), Controller (request handling/glue). Model = `app/Models/*.php`, View = `resources/views/*.blade.php`, Controller = `app/Http/Controllers/*.php`.
*Simple: "Model = data, View = what you see, Controller = the middleman."*

**34. Give a concrete example of one full MVC cycle in this project.**
`GET /staff/borrowings` -> `StaffBorrowingController::index()` (Controller) -> `Borrowing::with(['student','borrowingDetails.item'])->where('borrow_status','pending')->get()` (Model) -> `view('staff.borrowings.index', compact('borrowings'))` (View).
*Simple: "Controller asks the Model for pending borrowings, then hands them to the View to display as a table."*

**35. Why is it bad practice to put database queries directly inside a Blade view?**
It breaks separation of concerns - views become hard to test, reuse, and cache; logic gets duplicated; and it can cause N+1 query problems if queries run inside a loop. This project keeps all queries in Controllers/Services, passing already-loaded data to views.
*Simple: "Views should just display data, not go fetch it themselves - that's the Controller's job."*

**36. What is a "thin controller, fat service" design, and where do you see it?**
Controllers only orchestrate (validate -> call service -> return response); the actual rules live in Services. `BorrowingController::store()` is short - it delegates almost everything to `BorrowingService::createBorrowing()`.
*Simple: "The controller just passes the ball; the service does the real work."*

**37. How does a Model define a relationship, e.g., `Booking belongsTo Student`?**
```php
public function student() {
    return $this->belongsTo(Student::class, 'student_id', 'student_id');
}
```
This lets you write `$booking->student->full_name` and Eloquent automatically joins via the FK.
*Simple: "It tells Laravel 'this booking belongs to one student, linked by student_id'."*

**38. What is the difference between `hasMany` and `belongsTo`?**
`hasMany` is on the "one" side (e.g., `Category::items()` - one category has many items); `belongsTo` is on the "many" side (e.g., `Item::category()` - many items belong to one category). They're two sides of the same FK relationship.
*Simple: "hasMany = 'I have many of these'. belongsTo = 'I belong to one of those'."*

**39. What is `hasOne` and where is it used?**
Like `hasMany` but expects/returns only one related row. Example: `Item::primaryImage()` -> `hasOne(ItemImage::class)->where('is_primary', true)`, and `ReturnRecord::maintenance()` -> `hasOne(Maintenance::class, 'return_id')`.
*Simple: "It's hasMany but limited to exactly one matching row."*

**40. How does Blade help separate logic from presentation?**
Blade directives (`@if`, `@foreach`, `@include`, `{{ }}`) let you write near-plain-HTML templates with minimal embedded logic, while components (`resources/views/components/status-badge.blade.php`) let you reuse small UI pieces (e.g., colored status badges) across many pages.
*Simple: "Blade lets HTML and a little bit of PHP mix safely and cleanly, with reusable pieces."*

## E. Routing (41-50)

**41. What file defines all the URLs in this app?**
`routes/web.php` - 91 routes total, each mapped to a Controller method.
*Simple: "It's the map that tells Laravel which page/controller handles which URL."*

**42. What is route middleware, and how is it grouped here?**
Code that runs before/after a request reaches the controller. Routes are grouped: `Route::middleware('auth.student')->group(function () {...})` so every route inside automatically requires student login.
*Simple: "It's a security checkpoint that every route in the group must pass through."*

**43. Why must specific routes (like `/inventory/categories`) be declared before wildcard routes (like `/inventory/{id}`)?**
Laravel matches routes top-to-bottom; if `/inventory/{id}` came first, a request to `/inventory/categories` would match it with `id='categories'`, never reaching the categories route.
*Simple: "Laravel checks routes in order, so exact paths must come before 'catch-all' patterns."*

**44. What HTTP method is used for deleting a record, and why not just use GET?**
`DELETE` (via `Route::delete()`, and `@method('DELETE')` in forms since browsers don't natively send DELETE). GET requests should never change data (they can be triggered by prefetching, crawlers, or cached) - that's why destructive actions use DELETE/POST/PUT.
*Simple: "GET is for 'just looking'; DELETE/POST/PUT are for 'changing something' - browsers and crawlers won't accidentally trigger those."*

**45. What is route naming (`->name('borrowings.store')`) used for?**
Lets code reference routes by name instead of hardcoded URLs - `route('borrowings.store')` or `redirect()->route('borrowings.index')` - so if the URL ever changes, only `web.php` needs updating.
*Simple: "It gives each route a nickname so the rest of the code doesn't need to know the actual URL."*

**46. What does `Route::resource` style vs explicit routes mean here - which did you use?**
This project uses **explicit** route definitions (`Route::get/post/put/delete` individually) rather than `Route::resource()`, giving fine-grained control over which CRUD actions exist and exactly which middleware/role applies to each (e.g., `archive` is admin-only while `update` is staff-level).
*Simple: "We wrote each route by hand instead of using Laravel's auto-generated CRUD routes, for more control."*

**47. How does the AJAX availability check (e.g., `/borrowings/availability`) fit into routing?**
It's a normal `GET` route returning JSON (not a Blade view) - `BorrowingController::availability()` returns `response()->json(['available' => $n])`, called via `fetch()` from JavaScript while the user is filling the form, before submission.
*Simple: "It's a route that returns just a number (in JSON) so the page can update live without reloading."*

**48. What happens if a student tries to access `/admin/dashboard` directly via URL?**
The route is inside `Route::middleware('auth.admin')->group()`. `RequireAdmin` checks `session('user_type') === 'staff'` first - a student fails this and is redirected to `/login` (never even reaching the `is_admin` check).
*Simple: "The security checkpoint blocks them and sends them back to the login page."*

**49. What happens if a (non-admin) staff member tries to access `/admin/reports`?**
`RequireAdmin` passes the first check (`user_type === 'staff'`) but fails `session('is_admin')` - redirected to `staff.dashboard` with a flash error "You do not have permission to access that page."
*Simple: "They're staff, so they're not sent to login - but they're bounced back to their own dashboard with a 'no permission' message."*

**50. How are route parameters like `{id}` vs `{studio}` (route model binding) different?**
`{id}` is a plain string/int passed to the controller (e.g., `InventoryController::edit($id)` then manually `Item::findOrFail($id)`). `{studio}` uses **implicit route model binding** - Laravel automatically resolves it to a `Studio` model instance (e.g., `BookingController::availability(Request $request, Studio $studio)`), throwing a 404 automatically if not found.
*Simple: "`{studio}` automatically becomes a real Studio object; `{id}` is just a number you look up yourself."*

## F. PostgreSQL (51-60)

**51. Why use `BIGSERIAL` for primary keys?**
Auto-incrementing 64-bit integer - guarantees unique, ever-increasing IDs without manual management, and 64-bit avoids ever running out of IDs even with millions of rows.
*Simple: "It's an auto-numbering column that never repeats."*

**52. What is `pg_trgm`, and why is it used here?**
A PostgreSQL extension enabling **trigram-based fuzzy text matching** - lets searches find partial/misspelled matches efficiently using GIN indexes (e.g., searching "gita" can match "Guitar"). Used for the Global Search feature across items, students, staff, bookings, etc.
*Simple: "It lets the search bar find things even if you type only part of the word or misspell it - and stay fast."*

**53. What is a GIN index, and how is it different from a normal (B-tree) index?**
GIN (Generalized Inverted Index) is optimized for indexing composite/complex values like arrays, full-text, and trigram sets - good for "contains" type searches. B-tree indexes are optimized for equality/range/sorting (`=`, `<`, `>`, `ORDER BY`). This project uses B-tree for date/status filters and GIN+trgm for fuzzy text search.
*Simple: "B-tree is for exact/range lookups and sorting; GIN is for 'search inside text' type queries."*

**54. What is `DB::transaction()` and why is it critical in `BorrowingService`?**
Wraps multiple queries so they all succeed or all roll back together (atomicity) - e.g., creating a `borrowings` row AND its `borrowing_details` rows must either both happen or neither, otherwise you'd get an orphaned/incomplete borrowing record.
*Simple: "It's an 'all or nothing' wrapper - if anything fails halfway, everything gets undone."*

**55. What is `lockForUpdate()`, and what problem does it solve?**
Locks the selected row(s) until the transaction commits, so concurrent transactions trying to read/lock the same row must wait - prevents race conditions like two students both reserving the last unit of stock simultaneously.
*Simple: "It puts a temporary 'do not touch' sign on a row while we're checking/updating it."*

**56. Give an example of a CHECK constraint enforcing an enum-like rule.**
`chk_borrowings_borrow_status CHECK (borrow_status IN ('pending','reserved','collected','returned','rejected','cancelled'))` - the database rejects any other value, even if application code has a bug.
*Simple: "The database has its own list of 'allowed words' for status and won't accept anything else."*

**57. What's the difference between `TIMESTAMP` and `DATE` types, and where is each used?**
`DATE` stores only a calendar date (used for `pickup_date`, `return_date`, `booking_date` - no time-of-day needed). `TIMESTAMP` stores date+time (used for `collected_at`, `returned_at`, `created_at`/`updated_at` - exact moments matter).
*Simple: "DATE = just a day. TIMESTAMP = a day AND a time."*

**58. How does PostgreSQL help detect double-booking at the database level (besides app code)?**
The `chk_bookings_time` CHECK ensures `end_time > start_time` for every row (basic sanity), while the actual overlap detection (`hasConflict()`) is application-level SQL using `start_time < :end AND end_time > :start` - a classic interval-overlap query, executed inside a locked transaction.
*Simple: "The database guarantees each booking's own times make sense; the app checks that bookings don't overlap each other."*

**59. What is `pg_dump`, and how is it used in this project?**
A PostgreSQL command-line tool that exports the entire database (schema + data) to a file. `spatie/laravel-backup` calls it automatically (configured via `PG_DUMP_PATH` in `.env`) as part of the daily backup.
*Simple: "It's the tool that creates a full copy/export of the database for backups."*

**60. What does `EXTENSION pg_trgm` being declared at the top of the DDL mean?**
`CREATE EXTENSION IF NOT EXISTS pg_trgm;` installs the trigram module into the database - it must run once before any `gin_trgm_ops` index can be created; without it, all the fuzzy-search indexes would fail to create.
*Simple: "It's a one-time 'install this add-on' step required before fuzzy search can work."*

## G. Triggers (61-68)

**61. What is a database trigger?**
A function that PostgreSQL runs automatically when a specified event (INSERT/UPDATE/DELETE) happens on a table - no application code needs to call it.
*Simple: "It's an automatic action that fires by itself when data changes."*

**62. What trigger exists in this project, and on which table/event?**
`trg_borrowings_set_overdue` - fires `BEFORE INSERT OR UPDATE` on `borrowings`.
*Simple: "Every time a borrowing record is created or changed, this trigger runs first."*

**63. What does `trg_borrowings_set_overdue` actually do?**
Calls `fn_set_borrowing_overdue()`, which sets `NEW.is_overdue := (borrow_status = 'collected' AND return_date IS NOT NULL AND return_date < CURRENT_DATE)` before the row is saved.
*Simple: "It automatically marks a borrowing as 'overdue' if it's still collected past its return date."*

**64. Why use `BEFORE` instead of `AFTER` for this trigger?**
`BEFORE` triggers can modify the row being inserted/updated (via `NEW.column := value`) before it's written - so `is_overdue` is calculated and saved in the *same* write, with no extra UPDATE needed. `AFTER` triggers can't change the row that just fired them (it's already saved).
*Simple: "BEFORE lets us fix the value before it's saved - so it's correct from the start, in one step."*

**65. Why is a daily scheduled job (`fn_refresh_overdue_borrowings`) still needed if there's already a trigger?**
The trigger only fires when a row is **written**. If a borrowing is sitting untouched as `collected` and its `return_date` passes (just because a day went by), no write happens, so the trigger never runs and `is_overdue` would stay stale. The daily job re-evaluates all `collected` rows regardless of whether they were written to.
*Simple: "The trigger only reacts to changes; but 'becoming overdue' can happen just because time passes - so we also check daily."*

**66. What would happen if you forgot to add the trigger, and only relied on PHP to set `is_overdue`?**
Any direct SQL (manual fix, seeder, another script, a future feature) that inserts/updates `borrowings` without going through `BorrowingService` would leave `is_overdue` incorrect/stale - the trigger guarantees correctness **regardless of which code path writes to the table**.
*Simple: "If only PHP set it, anything that bypasses PHP (like a manual SQL fix) would break it. The trigger protects against that."*

**67. Can a trigger call a regular SQL function? Show the relationship.**
Yes - `CREATE TRIGGER trg_borrowings_set_overdue ... EXECUTE FUNCTION fn_set_borrowing_overdue();` - the trigger is just the "wiring" that says *when* to run; `fn_set_borrowing_overdue()` is the actual logic (a PL/pgSQL function returning `TRIGGER`).
*Simple: "The trigger is the switch; the function is the action it runs."*

**68. What language are the trigger/functions written in?**
PL/pgSQL (`LANGUAGE plpgsql`) - PostgreSQL's procedural extension to SQL, supporting variables, IF/LOOP, etc.
*Simple: "It's SQL with extra programming features like variables and loops, built into PostgreSQL."*

## H. Views (69-76)

**69. What is a database view?**
A stored SELECT query that can be queried like a virtual table (`SELECT * FROM v_inventory_low_stock`), without storing duplicate data - it always reflects the live underlying tables.
*Simple: "It's a saved 'question' to the database that you can re-ask anytime, like a virtual table."*

**70. What three views exist in this project, and what does each show?**
`v_inventory_low_stock` (items at or below 20% stock), `v_monthly_booking_summary` (bookings grouped by month/studio/status), `v_studio_utilization` (each studio's % share of all active bookings).
*Simple: "Low stock list, monthly booking stats, and which studio is most popular."*

**71. How does `v_inventory_low_stock` calculate "low stock"?**
`WHERE item_status = 'available' AND quantity > 0 AND available_quantity <= quantity * 0.2` - i.e., 20% or less of total stock remains available.
*Simple: "If less than 20% of an item's stock is left, it shows up here."*

**72. Is `v_inventory_low_stock`'s 20% rule duplicated anywhere else? Why might that be a concern?**
Yes - the exact same `whereRaw('available_quantity <= quantity * 0.2')` condition is duplicated in `InventoryController` and `ReportController` (for the dashboard alert and inventory report). It's a known duplication - if the threshold ever changes, it must be updated in 3 places (or the view should be the single source of truth and PHP should query it instead).
*Simple: "Yes, the same '20%' rule is written in three places - ideally it should only be written once."*

**73. What does `availability_pct` represent in `v_inventory_low_stock`?**
`ROUND((available_quantity::numeric / quantity::numeric) * 100, 2)` - the percentage of total stock that's currently available, e.g., 15.50 means 15.5% remains.
*Simple: "It's the percentage of stock still available, as a number like 15.5."*

**74. How does `v_studio_utilization` calculate `utilization_pct`?**
For each studio: `100.0 * (this studio's confirmed/completed bookings) / (total confirmed/completed bookings across ALL studios)`, rounded to 2 decimals - a relative "popularity share".
*Simple: "What percentage of all real bookings happened in this particular studio."*

**75. Why use `to_char(b.booking_date, 'YYYY-MM')` in `v_monthly_booking_summary`?**
Converts a date into a "Year-Month" string (e.g., "2026-06") so bookings can be grouped per calendar month regardless of the day, ordered with `ORDER BY report_month DESC`.
*Simple: "It turns a full date into just 'year-month' so we can group bookings by month."*

**76. Are these views currently used by the Laravel app, or only by SQL tools?**
They are defined in the schema as reporting/reference views (documented in `current_schema_ddl.sql`) - some of their logic is mirrored in PHP (e.g., `ReportController`'s low-stock calculation), useful for direct DB inspection/ad-hoc reporting and as a documented "single source of truth" for these formulas even if the app computes them in PHP too.
*Simple: "They're part of the database design - useful for reports and for anyone querying the DB directly, even outside the app."*

## I. Services (77-84)

**77. Name all 4 service classes and their one-line purpose.**
`BookingService` (studio booking logic + conflict checks), `BorrowingService` (equipment reservation lifecycle + availability math), `ItemImageService` (item image gallery management), `StudioService` (studio image gallery management).
*Simple: "Booking rules, borrowing rules, item photos, studio photos."*

**78. Why not just put `BorrowingService`'s logic directly inside `BorrowingController`?**
Because the same logic (`getAvailableQuantity`, `approveBorrowing`, etc.) is needed by **multiple controllers** (`BorrowingController` for students, `StaffBorrowingController` for staff, `MaintenanceController` for resolving issues) - putting it in one shared class avoids duplicating the same complex transaction logic three times.
*Simple: "Multiple controllers need the same rules, so we wrote them once in a shared place."*

**79. What custom exceptions are used, and why?**
`InsufficientStockException` (thrown when a borrowing request/approval exceeds available stock) and `BookingConflictException` (thrown when a booking overlaps an existing one or the studio is unavailable). Custom exceptions let controllers `catch` specific business errors and show friendly messages, separate from generic system errors.
*Simple: "Special 'error types' for business rule violations, so the app can show a nice specific message instead of crashing."*

**80. How does `BorrowingService::approveBorrowing()` prevent over-approval (race condition)?**
It locks each item row (`lockForUpdate()`) and **re-runs** `getAvailableQuantity()` (excluding the borrowing's own reservation) at approval time - even though it was checked at creation time, stock could have been consumed by another approval in between. If still insufficient, it throws and the transaction rolls back.
*Simple: "It double-checks stock right before approving, just in case something changed since the request was made."*

**81. Walk through what happens, step by step, when `processReturn()` is called with one item marked 'damaged'.**
1) Create a `return_records` row (`item_condition='damaged'`). 2) `Item::decrement('available_quantity', qty)`. 3) Create a `maintenances` row (`issue_type='damage'`, `maintenance_status='pending'`). 4) Check if total returned >= total borrowed; if yes, mark `borrowings.borrow_status='returned'`.
*Simple: "Record the return, remove that quantity from available stock, open a maintenance ticket, and close the borrowing if everything's been returned."*

**82. How does `resolveMaintenance()` "undo" the stock decrement from a damaged return?**
It reads `quantity = maintenance->returnRecord->quantity_returned` and does `Item::increment('available_quantity', quantity)`, then sets `maintenance_status='resolved'` - mirroring the exact decrement that happened during the return.
*Simple: "It adds back exactly the amount that was removed when the item was marked damaged."*

**83. In `BookingService::getAvailability()`, how is "fully booked" determined?**
After building the hour-by-hour slot grid for the day, `$fullyBooked = collect($slots)->every(fn($slot) => $slot['status'] !== 'available')` - true only if **every** slot is booked/pending/maintenance/blocked.
*Simple: "If every single time slot that day has something blocking it, the studio is 'fully booked'."*

**84. What's the difference between `BookingService::hasConflict()` and `assertStudioBookable()`?**
`hasConflict()` checks for **time overlap with other bookings** on the same studio/date. `assertStudioBookable()` checks the **studio's own status** (`available`/`maintenance`/`blocked`) and any `studio_unavailability` periods overlapping the requested time - two independent checks, both must pass.
*Simple: "One checks 'is this time slot already taken by someone else'; the other checks 'is the studio itself even usable right now'."*

## J. Reporting (85-92)

**85. What 3 report types does the system support?**
Bookings, Borrowings, and Inventory reports - all accessible via `GET /admin/reports?type=...`.
*Simple: "Reports about studio bookings, equipment borrowings, and current inventory."*

**86. What export formats are available, and which package generates each?**
On-screen (paginated HTML view), PDF (via `barryvdh/laravel-dompdf`), and CSV (via PHP's built-in `fputcsv()` + `streamDownload()` - no external package needed).
*Simple: "View it on screen, download as PDF, or download as CSV (Excel-friendly)."*

**87. Why does the PDF/CSV export use `$paginate = false` while the on-screen report uses `paginate(15)`?**
The on-screen report only needs 15 rows per page for usability; a PDF/CSV export must contain the **entire filtered dataset**, so it calls `->get()` instead of `->paginate(15)`.
*Simple: "The screen shows a page at a time, but a downloaded report needs everything in one file."*

**88. How does the report apply date-range filters?**
`applyDateRange()` adds `whereDate($column, '>=', $date_from)` and `whereDate($column, '<=', $date_to)` if provided - using `booking_date` for bookings, `pickup_date` for borrowings, `date_added` for inventory.
*Simple: "If you give a 'from' and 'to' date, it only shows records in that range."*

**89. Why does the CSV writer add a UTF-8 BOM (`\xEF\xBB\xBF`) at the start of the file?**
Without it, Microsoft Excel may misinterpret UTF-8 encoded special characters (like accented names) as a different encoding and display garbled text. The BOM tells Excel "this file is UTF-8".
*Simple: "It's a small marker that tells Excel to read special characters correctly."*

**90. What FormRequest validates report filters, and what might it check?**
`ReportFilterRequest` - validates `type` (must be `bookings`/`borrowings`/`inventory`), `date_from`/`date_to` (valid dates, `date_to >= date_from`), `status`, `studio_id`/`category_id` (must exist).
*Simple: "It checks the filter values you typed/selected make sense before running the report."*

**91. How are "summary" statistics (e.g., total/confirmed/cancelled counts) computed efficiently?**
By cloning the filtered query (`clone $query`) and running a separate aggregate query with `select(..., DB::raw('count(*) as total'))->groupBy('status')->pluck('total','status')` - one extra query gives counts per status, rather than looping through all results in PHP.
*Simple: "One extra database query counts everything grouped by status, instead of counting in PHP afterward."*

**92. Why is the Inventory report's "low stock" count computed with `whereRaw()` instead of querying the `v_inventory_low_stock` view?**
Architecturally it *could* query the view, but the current implementation re-applies the same `available_quantity <= quantity * 0.2` condition directly in PHP on the already-filtered `Item` query (so it respects the report's category/date filters too) - a known duplication discussed in Q72.
*Simple: "It re-applies the same '20%' rule directly so it works together with the report's other filters."*

## K. Deployment (93-100)

**93. What must change in `.env` before deploying to production?**
`APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=strict` (when served over HTTPS), and real `DB_*` credentials for the production database.
*Simple: "Turn off debug mode, turn on secure cookies, and point to the real production database."*

**94. Why must `.env` never be committed to Git?**
It contains secrets - database password, `APP_KEY` (used to encrypt sessions/cookies), mail credentials. This project's `.gitignore` correctly excludes `.env`, `.env.backup`, `.env.production`.
*Simple: "It has all our passwords and secret keys - if it leaks, attackers get everything."*

**95. What commands should run as part of a production deployment, and why?**
`composer install --no-dev --optimize-autoloader` (smaller, faster autoloader, no dev tools), `php artisan migrate --force` (apply schema changes), `php artisan config:cache`/`route:cache`/`view:cache` (pre-compile for speed).
*Simple: "Install only what's needed, update the database structure, and pre-build caches for speed."*

**96. How does the backup system protect against data loss, and what's its current limitation?**
`spatie/laravel-backup` runs daily (via the scheduler), zipping `storage/app/public` + `.env` + a `pg_dump`. **Limitation**: it currently saves to local disk only - if the server's disk fails entirely, the backup is lost too. An offsite destination (S3, etc.) should be added.
*Simple: "We have automatic daily backups, but right now they're stored on the same server - ideally they should also go somewhere else (cloud storage)."*

**97. What is `php artisan schedule:run`, and what external setup does it require?**
The command that checks `routes/console.php` and runs any tasks that are due. It must itself be triggered every minute by an OS-level **cron job** (Linux) or Task Scheduler (Windows) - Laravel's scheduler does nothing on its own without this external trigger.
*Simple: "Laravel's 'todo list checker' - but something outside Laravel (cron) has to run it every minute."*

**98. Why is `composer audit` useful, and what did it find for this project?**
It checks installed packages against known security advisories. This project showed 3 **low-severity** advisories, all in `symfony/yaml` (recursive YAML parsing issues) - not exploitable here since the app never parses untrusted YAML, but still worth a `composer update`.
*Simple: "It's a scanner for known vulnerabilities in our installed packages - we have a few low-risk ones, easily fixed with an update."*

**99. The project currently uses `->get()` instead of `->paginate()` on most list pages. Why is this a deployment concern?**
`->get()` loads **every matching row** into memory and renders it - fine with a handful of demo records, but as real usage accumulates (months of bookings/borrowings), these pages get slower and use more memory. `->paginate(15)` (already used in `ReportController`) should be applied to `InventoryController`, `BorrowingController`, `BookingController`, etc.
*Simple: "Right now list pages load 'everything at once' - that's fine for a demo but will slow down as real data grows."*

**100. If the examiner asks "is this system production-ready?", what's the honest answer?**
"It's **near production-ready**: the security fundamentals (hashing, CSRF, SQL injection/XSS protection, RBAC, session handling) are solid and the architecture (services, triggers, views, caching, automated backups) is more mature than a typical FYP. What remains before a real deployment are operational items: disable debug mode, add pagination to list pages, move backups offsite, and confirm the OS cron is wired up - none of these are architectural flaws, they're standard pre-launch checklist items."
*Simple: "The hard parts (security, design) are done well. What's left is the 'final checklist' every project does before going live."*

---

# PART 11 — SYSTEM WEAKNESSES (and how to defend them in a viva)

For each weakness: **What it is -> Why it happened -> How to fix -> Best answer if the examiner asks "why isn't this done?"**

**1. `APP_DEBUG=true` in `.env`**
- *What*: Detailed error pages (with stack traces, config values) are shown on crashes.
- *Why*: This is the **local development** setting - it's deliberately on so errors are easy to debug while building the system.
- *Fix*: Set `APP_DEBUG=false` and `APP_ENV=production` before deployment - a one-line config change, already documented with a comment in `.env`.
- *Best answer*: "This is a development convenience, not a design flaw - it's a documented one-line change in the pre-deployment checklist, already commented in `.env` to remind whoever deploys it."

**2. Two student-facing history pages still use `->get()` instead of `->paginate()`**
- *What*: Inventory, Borrowing (staff), Booking (staff), Maintenance, and User Management are all paginated at 15/page with `withQueryString()`. The two remaining exceptions are the student's own "My Bookings" and "My Borrowings" pages, which still load the student's full history at once.
- *Why*: Each student's own history is naturally small (their own requests only), so it was lower priority than the admin/staff-facing lists which can grow large across all users.
- *Fix*: Same one-line pattern already used everywhere else — `->paginate(15)->withQueryString()`.
- *Best answer*: "Pagination is applied consistently across every admin/staff-facing listing. The two remaining student self-service pages are scoped to one person's own records, so the dataset size is inherently bounded — applying the same pattern there is a trivial, low-priority follow-up."

**3. The "20% low stock" threshold is duplicated in 3 places**
- *What*: The same `available_quantity <= quantity * 0.2` rule appears in `v_inventory_low_stock` (SQL view), `InventoryController`, and `ReportController`.
- *Why*: Each was built to solve its own immediate need (dashboard alert, DB-level view, report) without first centralizing the constant.
- *Fix*: Define a constant (e.g., `Item::LOW_STOCK_THRESHOLD = 0.2`) or a model scope `Item::lowStock()` and reuse it everywhere; the SQL view remains as a DB-level reference.
- *Best answer*: "It's a 'magic number' duplication - a maintainability concern, not a bug, since all three currently agree. Centralizing it is a quick refactor."

**4. Backups are stored locally only**
- *What*: `spatie/laravel-backup` zips files+DB to local disk; if the server disk fails, backups are lost too.
- *Why*: Cloud storage (S3, etc.) requires AWS/cloud credentials and cost, which is out of scope for an academic FYP environment running on a local/Laragon setup.
- *Fix*: Add an S3 (or similar) disk to `config/filesystems.php` and add it to the backup destination list - Spatie supports multiple destinations out of the box.
- *Best answer*: "The automation (the hard part) is done - daily backups run on schedule. Offsite storage is an infrastructure/cost decision for the deployment environment, not a missing feature of the system design."

**5. No automated test suite (PHPUnit/Pest feature tests)**
- *What*: Correctness is currently verified manually (route testing, manual flows) rather than via `php artisan test`.
- *Why*: Time constraints of an FYP - prioritized building the full feature set (13 tables, 5 services-worth of business logic, 91 routes) over writing parallel test coverage.
- *Fix*: Add feature tests for the highest-risk logic first: `BorrowingService::getAvailableQuantity()` overlap math and `BookingService::hasConflict()` - both have precise, testable boundary conditions.
- *Best answer*: "Given the time budget, manual verification of each user flow was prioritized to ensure the *feature* works correctly; automated tests are the natural next layer to *prevent regressions* as the system evolves."

**6. No API / mobile support (web-only, session-based auth)**
- *What*: The system is a server-rendered Blade web app; there's no REST/JSON API for a future mobile app.
- *Why*: The scope was defined as a web-based management system for staff/students using desktop/lab browsers - this matches the stated user base (UTeM students/staff on campus).
- *Fix*: If needed, add Laravel Sanctum for token-based API auth alongside the existing session auth, and expose JSON endpoints.
- *Best answer*: "The requirements specify a web management system, not a mobile app - session-based auth is the simplest, most secure choice for that scope. The architecture (services layer) already separates business logic from the web layer, so an API could be added later without rewriting the core logic."

**7. Email notifications not implemented (`MAIL_MAILER=log`)**
- *What*: Approvals/rejections/reminders don't send real emails - mail is just written to the log file.
- *Why*: Requires an SMTP server/credentials; not essential to demonstrate the core borrowing/booking workflow for a viva.
- *Fix*: Configure a real `MAIL_MAILER` (e.g., SMTP or a service like Mailtrap for testing) and create `Notification`/`Mailable` classes for key events (approval, rejection, overdue reminder).
- *Best answer*: "The system already tracks all the state needed to trigger notifications (status changes, dates) - sending the actual email is a configuration + a Notification class, not a structural change."

**8. No rate limiting beyond login (`throttle:5,1`, `throttle:10,1`)**
- *What*: Other forms (borrowing requests, booking requests) aren't rate-limited.
- *Why*: Rate limiting was applied where abuse risk is highest (login = brute force target); other forms require authentication, reducing anonymous abuse risk.
- *Fix*: Add `throttle:X,1` middleware to any form-submitting routes if abuse becomes a concern.
- *Best answer*: "Rate limiting was applied risk-proportionally - the login form is the highest-value target for automated attacks. Authenticated routes are lower-risk and can have throttling added the same way if needed."

**9. `composer audit` shows 3 low-severity advisories (symfony/yaml)**
- *What*: A transitive dependency has known, low-severity issues (recursive YAML parsing).
- *Why*: Comes from a sub-dependency of a Laravel package, not code written for this project; not exploitable here because the app never parses untrusted YAML input.
- *Fix*: Run `composer update` periodically to pick up patched versions as they're released.
- *Best answer*: "This is normal dependency hygiene, not a vulnerability *in this system* - the affected code path (untrusted YAML parsing) is never used here. Regular `composer update` is part of standard maintenance."

**10. Single shared "studio" availability granularity (1-hour slots, 08:00-23:30)**
- *What*: Booking slots are fixed at 1-hour increments; can't book 30 or 90 minutes.
- *Why*: A fixed slot grid (`OPERATING_START`/`OPERATING_END`/`SLOT_MINUTES` constants in `BookingService`) is simpler to display and avoids fragment-overlap edge cases for a first version.
- *Fix*: Reduce `SLOT_MINUTES` to 30 (the conflict-detection logic - interval overlap - already supports arbitrary times; only the *displayed grid* uses fixed slots).
- *Best answer*: "The underlying conflict-detection already works with arbitrary start/end times - the 1-hour grid is a UI/UX choice for simplicity, and is a one-constant change if finer granularity is needed."

---

# PART 12 — SYSTEM STRENGTHS

**1. Service Layer Architecture (Service Pattern)**
Business logic (`BookingService`, `BorrowingService`, `ItemImageService`, `StudioService`) is separated from Controllers - this is the same principle used in professional Laravel codebases ("thin controllers, fat services"). It means logic is reusable (multiple controllers call the same service), centrally testable, and controllers stay readable.

**2. Database-Enforced Integrity (not just app-level)**
`CHECK` constraints (`chk_borrowings_dates`, `chk_bookings_time`, `chk_borrowings_borrow_status`), `FOREIGN KEY` actions (`CASCADE`/`RESTRICT`/`SET NULL`), and a **trigger** (`trg_borrowings_set_overdue`) all mean the database protects data correctness *even if application code has a bug or someone runs raw SQL*. Most student FYPs rely on PHP validation alone.

**3. Race-Condition-Safe Concurrency Control**
`DB::transaction()` + `lockForUpdate()` in `BorrowingService::approveBorrowing()` and `BookingService::createBooking()` correctly handle the classic "two users act on the same resource at the same time" problem - a concept many FYPs never address.

**4. Real Business-Logic Algorithms (not just CRUD)**
- Date-range overlap availability calculation (`getAvailableQuantity`) - genuine interval-overlap math.
- Studio booking conflict detection + hourly availability grid generation (`getAvailability`).
These go well beyond simple "insert/select/update/delete" CRUD scaffolding.

**5. Defense-in-Depth Security**
Bcrypt password hashing, CSRF protection on all forms, Eloquent parameter binding (SQL injection-safe), Blade auto-escaping (XSS-safe), session regeneration on login (session fixation-safe), generic error messages + dummy-hash timing defense (account enumeration-safe), and role-based middleware (RBAC) - this is a genuinely comprehensive security posture for a student project.

**6. Reporting Module with Multiple Export Formats**
On-screen, PDF (DomPDF), and CSV (with UTF-8 BOM for Excel compatibility) exports, with shared filtering logic (`applyDateRange`) and summary aggregation - demonstrates understanding of real-world reporting needs.

**7. PostgreSQL-Specific Features Used Meaningfully**
Views (`v_inventory_low_stock`, `v_monthly_booking_summary`, `v_studio_utilization`), a PL/pgSQL trigger + function for `is_overdue`, and `pg_trgm`/GIN indexes for fuzzy global search - shows the database isn't just "a place to dump rows," it's an active part of the system design.

**8. Automated Backups + Scheduled Maintenance**
`spatie/laravel-backup` (daily DB+file backups) and the Laravel Scheduler (`fn_refresh_overdue_borrowings` daily) show awareness of operational concerns (DR/maintenance) that most FYPs ignore entirely.

**9. Consistent, Reusable UI Components**
Blade components (e.g., `status-badge.blade.php`) for status pills, AJAX-driven live availability widgets on booking/borrowing forms - shows attention to UX consistency and modern interactive patterns without a heavy JS framework.

**10. Clean, Documented Database Design**
A full ERD (`database/sql/erd.puml`), a complete DDL reference (`current_schema_ddl.sql`), 3NF normalization, and sensible naming conventions throughout - the kind of documentation that makes the system maintainable by someone other than the original author.

---

# PART 13 — MASTER SUMMARY (10-Minute Viva Presentation Script)

Use this as a spoken outline. Keep it simple - the examiner wants to see you **understand** the system, not recite jargon.

**1. What is this system? (1 minute)**
"This is a Music Studio Management System for UTeM. It lets students book practice studios and borrow music equipment online, while staff approve those requests and manage inventory, and admins oversee everything with reports and user management. Before this system, these processes were likely manual - paper forms, physical sign-out sheets - which are slow and easy to lose track of."

**2. Who uses it, and what's the basic flow? (1.5 minutes)**
"There are three types of users: Students, Staff, and Admins (a special staff flag). A student logs in, browses available studios or equipment, and submits a request - either a studio booking (date + time slot) or an equipment borrowing (pickup date to return date, with quantities). That request goes to staff, who review and approve or reject it. For equipment, once approved it becomes 'reserved'; when the student picks it up, staff mark it 'collected'; when they bring it back, staff process the 'return' and check the item's condition. If something's damaged, a maintenance ticket is automatically created."

**3. How is the data organized? (1.5 minutes)**
"The database has 13 tables in PostgreSQL. The core ones are `students`, `staff`, `items` (equipment), `studios`, `bookings` (studio reservations), and `borrowings` + `borrowing_details` (equipment requests, where one request can have many items - that's a many-to-many relationship through a junction table). Everything is connected with foreign keys, and the database itself enforces rules - for example, it physically cannot save a booking where the end time is before the start time, because of a CHECK constraint."

**4. What makes the system 'smart'? (2 minutes)**
"Two pieces of logic I'm proud of: First, **availability calculation** - when a student wants to borrow, say, 10 microphone stands from the 25th to the 27th, the system looks at all *other* pending or active requests that overlap those dates and subtracts their quantities from the total stock, so it tells the student exactly how many are free for *their specific dates* - not just 'how many are in the building right now'. Second, **studio conflict detection** - it checks if a requested time slot overlaps any existing booking for that studio, using simple interval-overlap logic, so double-booking is impossible. Both of these are also re-checked at the database level using row-locking, so even if two students click 'submit' at the exact same millisecond, only one can succeed - the other gets a clear error instead of an inconsistent database."

**5. How is it built (architecture)? (1.5 minutes)**
"It's built with Laravel 12, following the MVC pattern - Models represent database tables, Views are the HTML pages (using Blade templates), and Controllers handle the web requests and tie everything together. On top of that, I added a **Service layer** - classes like `BookingService` and `BorrowingService` that hold all the business rules, so controllers stay simple and the same logic can be reused wherever it's needed - for example, both the student-facing and staff-facing borrowing controllers use the same `BorrowingService`."

**6. How is it secured? (1.5 minutes)**
"Passwords are hashed with bcrypt and never stored in plain text. Every form has CSRF protection so other websites can't submit forms on a user's behalf. All database queries go through Laravel's query builder, which automatically prevents SQL injection. Output is automatically escaped to prevent XSS. And access is controlled by role - middleware checks make sure students can't reach staff pages and staff can't reach admin-only pages like reports and user management, even if they type the URL directly."

**7. What's left to improve, and closing (1 minute)**
"The core system is functionally complete and secure. What remains are deployment-readiness items - turning off debug mode for production, adding pagination as data grows, and moving backups offsite - all standard pre-launch tasks, not design flaws. Overall, this project demonstrates a full-stack system with real business logic, a properly normalized database with triggers and views, and a defense-in-depth security model - thank you."
