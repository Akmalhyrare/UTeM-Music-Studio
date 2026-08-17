# UTeM Music Studio Inventory & Management System

A web-based booking, borrowing, and inventory management platform for the UTeM Music Studio, built as a final year project (FYP). It replaces manual, paper/WhatsApp-based studio booking and equipment sign-out with a single system that tracks studio bookings, equipment/attire borrowing, inventory stock levels, and maintenance — with role-based dashboards for students, staff, and administrators.

## Description

The Music Studio currently runs two core processes by hand: booking a rehearsal/recording room, and borrowing equipment (instruments, cables, microphones, attire) for a date range. Both are prone to double-booking, lost stock visibility, and no audit trail. This system digitizes both workflows end-to-end — request, approval, collection/check-in, and return — on top of a PostgreSQL database that enforces its own business rules (booking conflict prevention, automatic overdue detection, stock availability) via triggers and stored procedures, not just application code.

## Objectives

- Replace manual studio booking with an online system that prevents double-booking in real time.
- Give staff a live view of equipment stock, availability, and condition instead of a paper logbook.
- Track the full lifecycle of a borrowing request (pending → reserved → collected → returned) with accountability at every step.
- Surface maintenance issues and low-stock items before they become a problem.
- Provide management-level reporting (PDF/CSV export) for monthly/administrative review.
- Enforce role-based access control (Student / Staff / Admin) so each user only sees and does what their role permits.

## Main Features

- **Studio browsing & booking** — students browse studios, check real-time slot availability, and book; conflicting bookings are rejected server-side.
- **Equipment & attire borrowing** — students request items for a date range; availability is computed from live reservations, not just a static stock count.
- **Staff approval workflow** — staff approve/reject pending borrowings, mark items collected, and process returns (recording item condition per unit).
- **Inventory management** — categorized items (equipment & attire) with multi-image upload, stock/available-quantity tracking, and condition status.
- **Studio management** — studio CRUD, image galleries, and an unavailability calendar for blocking dates (maintenance/events).
- **Maintenance tracking** — damaged/lost items raised automatically on a bad-condition return, resolved by staff, restocking availability on resolution.
- **Admin dashboard & reports** — system-wide KPIs plus PDF/CSV report export (bookings, borrowings, inventory, maintenance, utilization).
- **User management** — admins create/edit/deactivate staff and student accounts.
- **Global search** — fuzzy (trigram-indexed) search across items, studios, bookings, and borrowings.
- **Account security** — session-based auth with login throttling, timing-safe failed-login responses, and self-service password/session management.

## Technology Stack

| Layer | Technology |
|---|---|
| Backend framework | Laravel 12 (PHP 8.2+) |
| Database | PostgreSQL, with native triggers, stored procedures/functions, and trigram (GIN) indexes for search |
| Templating | Blade |
| Frontend | Tailwind CSS, Bootstrap 5, Alpine.js, vanilla JS via Vite |
| Auth | Custom session-based authentication (no Laravel Breeze/Sanctum auth scaffolding — see [Authentication](#authentication) below) |
| PDF/reporting | barryvdh/laravel-dompdf |
| Image handling | intervention/image |
| Backups | spatie/laravel-backup (scheduled DB + storage backup, cleanup, and health monitoring) |
| Build tooling | Vite, npm |

### Authentication

This project does **not** use Laravel's default `User` model, Breeze's auth controllers, or Sanctum. Authentication is custom and session-based, against two separate tables — `staff` and `students` — with role and admin-flag stored in the session (see `app/Http/Controllers/AuthController.php`). Laravel Breeze remains a dev dependency only for its originally-scaffolded views/assets; the auth logic itself has been fully replaced.

## System Modules

| Module | Who | Description |
|---|---|---|
| Landing / Browse | Public | Landing page, browse items and studios without logging in |
| Auth | Public | Login, registration (students), logout — rate-limited |
| Student Dashboard | Student | Overview of the student's own bookings/borrowings |
| Studio Booking | Student | Create/list/edit/cancel bookings, check studio availability |
| Equipment Borrowing | Student | Create/list/cancel borrowing requests, check item availability |
| Account Settings | Student, Staff | Update profile/password, log out other sessions |
| Staff Dashboard | Staff | Operational overview |
| Inventory Management | Staff | Item & category CRUD, multi-image upload/reorder |
| Booking Management | Staff | View/update/cancel student bookings |
| Borrowing Management | Staff | Approve/reject/collect requests, process returns |
| Maintenance | Staff | View and resolve equipment maintenance issues |
| Studio Management | Staff | Studio CRUD, image galleries, unavailability calendar, archive |
| Global Search | Staff, Student | Fuzzy search across items/studios/bookings/borrowings |
| Admin Dashboard | Admin | System-wide KPIs |
| Reports | Admin | PDF/CSV export (bookings, borrowings, inventory, maintenance, utilization) |
| User Management | Admin | Create/edit/deactivate staff and student accounts |

## Database

- **Engine:** PostgreSQL (developed against PostgreSQL 18)
- **Database name:** configurable via `.env` (`DB_DATABASE`); developed as `music_studio_db`

### Main tables

| Table | Purpose |
|---|---|
| `staff` | Staff/admin accounts (`is_admin` flag distinguishes the two) |
| `students` | Student accounts |
| `categories` | Item categories, typed as `equipment` or `attire` |
| `items` | Inventory items — stock, available quantity, condition, status |
| `item_images` | Multi-image gallery per item |
| `studios` | Bookable studio rooms |
| `studio_images` | Multi-image gallery per studio |
| `studio_unavailability` | Blocked date/time ranges per studio (maintenance/events) |
| `bookings` | Studio booking requests and their status |
| `borrowings` | Equipment borrowing requests (one row per request) |
| `borrowing_details` | Items within a borrowing request (composite key: `borrow_id` + `item_id`) |
| `return_records` | Per-item return records with condition logged |
| `maintenances` | Maintenance issues raised from damaged/lost returns |

### Relationships (high level)

- `items` → `categories` (many-to-one)
- `item_images`, `borrowing_details`, `return_records`, `maintenances` → `items` (many-to-one)
- `borrowings` → `students`, `staff` (many-to-one each)
- `borrowing_details` is the bridge table between `borrowings` and `items` (a request can contain several items)
- `return_records`, `maintenances` → `borrowings` (many-to-one, via `borrow_id` / `return_id`)
- `bookings` → `students`, `staff`, `studios` (many-to-one each)
- `studio_images`, `studio_unavailability` → `studios` (many-to-one)

A full ERD is available at [`docs/erd.puml`](docs/erd.puml) (PlantUML).

### Database-level business logic

Several rules are enforced in PostgreSQL itself, not only in application code:

- A trigger (`trg_borrowings_set_overdue`) automatically flags a borrowing as overdue based on its status and return date; a scheduled stored procedure (`sp_refresh_overdue_borrowings`) keeps that flag fresh even for rows that aren't written to.
- Check constraints restrict `booking_status`, `borrow_status`, and `maintenance_status` to their valid enum values at the database level.
- Trigram (`gin_trgm_ops`) indexes on names/descriptions/purposes power fuzzy global search.
- `database/sql/` contains exported DDL/object dumps (schema, functions, procedures) generated directly from the live database, alongside the ERD source — useful as a reference without needing a running Postgres instance.

## Getting Started

### Requirements

- PHP 8.2+
- Composer
- PostgreSQL (a local instance, e.g. via Laragon, Postgres.app, or a native install)
- Node.js + npm

### Installation

```bash
git clone <your-repository-url>
cd music-studio-system

composer install
npm install
```

### Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Then edit `.env` and set your local database connection:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=music_studio_db
DB_USERNAME=postgres
DB_PASSWORD=your_local_password
```

Create the database itself first (e.g. `createdb music_studio_db`, or via your Postgres GUI/client of choice) — Laravel's migrator does not create the database for you.

On Windows, if you plan to use the backup commands (`spatie/laravel-backup`), also set `PG_DUMP_PATH` in `.env` to your PostgreSQL `bin` directory, since `pg_dump.exe` usually isn't on `PATH`.

### Database Migration

```bash
php artisan migrate
```

This creates all application tables plus the PostgreSQL triggers/functions/procedures/indexes defined in the later migrations (see `database/migrations/2026_06_15_000003_add_postgresql_reporting_objects.php` and `..._add_postgresql_procedures.php`).

### Database Seeding

```bash
php artisan db:seed
```

This runs, in order: categories → items → staff → students → studios → bookings → borrowings, plus additional booking/borrowing history for realistic trend data. It also writes a local-only credentials reference to `storage/app/private/dev-credentials.md` (git-ignored — not committed, not published) for convenience during development.

To reset and reseed from scratch:

```bash
php artisan migrate:fresh --seed
```

### Running the Application

```bash
composer run dev
```

This runs the PHP dev server, queue listener, log viewer (`pail`), and Vite dev server together. Alternatively, run them separately:

```bash
php artisan serve
npm run dev
```

Visit `http://localhost:8000` (or the port `artisan serve` reports).

## Example Login (local development seed data)

`database/seeders/StaffSeeder.php` and `StudentSeeder.php` create synthetic demo accounts (fictional names/emails, not real UTeM students or staff) so the system can be logged into and graded immediately after seeding. One admin account, for reference:

| Role | Email | Password |
|---|---|---|
| Admin (staff) | `faizal@utem.edu.my` | `Staff!Faizal27` |

The remaining staff and ~30 student demo accounts are listed directly in the two seeder files. **See the note in [Security](#security--sensitive-data) below before pushing this repository publicly.**

## Project Structure

```text
music-studio-system/
│
├── app/
│   ├── Exceptions/            # Custom exceptions (e.g. booking conflict, insufficient stock)
│   ├── Http/
│   │   ├── Controllers/       # One controller per module (see System Modules above)
│   │   ├── Middleware/        # Role-based route guards (RequireAdmin, etc.)
│   │   └── Requests/          # Form request validation classes
│   ├── Models/                # Eloquent models (Staff, Student, Item, Booking, Borrowing, ...)
│   ├── Services/              # Business logic (BookingService, BorrowingService)
│   ├── Rules/                 # Custom validation rules
│   └── Support/               # Small helper classes
├── database/
│   ├── migrations/            # Schema + Postgres triggers/functions/procedures
│   ├── seeders/                # Demo/test data generators
│   └── sql/                    # Exported schema/object DDL + ERD source (reference only)
├── docs/                      # ERD and viva/FYP preparation notes
├── public/                    # Web root, static assets
├── resources/
│   ├── views/                 # Blade templates, organized by role/module
│   ├── js/, css/              # Frontend source (built by Vite)
├── routes/                    # web.php, console.php (includes scheduled jobs)
├── storage/                   # Logs, framework cache, generated backups (git-ignored)
├── tests/                     # PHPUnit feature/unit tests
├── .env.example
├── .gitignore
├── composer.json
├── package.json
├── artisan
└── README.md
```

## Team

| Name | Role |
|---|---|
| _Your Name_ | Developer |
| _Supervisor Name_ | Project Supervisor |

_(Replace with actual names before submission.)_

## Future Improvements

- Backfill `return_records` for older seeded "returned" borrowings (currently only a subset have full return-record detail; historical bulk data was seeded directly rather than through the return workflow).
- Add an explicit `resolved_at` timestamp to `maintenances` for precise repair-time reporting.
- API/token-based access (Laravel Sanctum) if a mobile client or external integration is ever needed.
- Email notifications (booking confirmation, overdue reminders) — currently out of scope; no SMTP server configured for the academic environment.
- Automated CI (PHPUnit + Pint) on push, once hosted outside a local environment.

## Security / Sensitive Data

Before pushing this repository publicly, be aware:

- **`.env` is correctly git-ignored** and has never been committed (verified against full git history) — no real database password or `APP_KEY` will be exposed.
- **`storage/app/private/dev-credentials.md`** (auto-generated by the seeder) is also git-ignored and has never been committed.
- **`database/seeders/StaffSeeder.php` and `StudentSeeder.php` contain ~36 hardcoded, plaintext demo passwords.** These are fictional accounts, not real UTeM credentials, but they *will* be published as-is since seeders are source code, not data. If any of these passwords resemble a pattern you reuse elsewhere, change that pattern before pushing. Consider this acceptable for an academic submission, or generate the passwords randomly/from an env value if you'd rather not publish them at all — this wasn't changed automatically since it touches working seed logic.
- No API keys, AWS credentials, or mail credentials are set in this environment (all blank/placeholder in `.env`), so there is nothing else to rotate.

## License

This project was developed for academic purposes as part of a Final Year Project (FYP) at Universiti Teknikal Malaysia Melaka (UTeM). It is provided as-is for educational and evaluation purposes. No specific open-source license is applied; contact the author before reuse outside an academic context.
