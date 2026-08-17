<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enables the PostgreSQL pg_trgm extension and adds trigram GIN indexes on
 * the text columns used by the application's search features, so partial
 * (ILIKE) and fuzzy (similarity()) lookups stay fast at scale.
 *
 * No-op on non-PostgreSQL connections (e.g. SQLite used in tests).
 */
return new class extends Migration
{
    /** @var array<int, array{0: string, 1: string}> table => column */
    private array $columns = [
        ['items', 'item_name'],
        ['items', 'item_description'],
        ['students', 'full_name'],
        ['students', 'email'],
        ['students', 'matric_no'],
        ['staff', 'full_name'],
        ['staff', 'email'],
        ['staff', 'position'],
        ['categories', 'category_name'],
        ['studios', 'studio_name'],
        ['bookings', 'purpose'],
        ['borrowings', 'purpose'],
        ['maintenances', 'issue_type'],
        ['maintenances', 'description'],
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach ($this->columns as [$table, $column]) {
            $indexName = "{$table}_{$column}_trgm_idx";
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} USING gin ({$column} gin_trgm_ops)");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->columns as [$table, $column]) {
            $indexName = "{$table}_{$column}_trgm_idx";
            DB::statement("DROP INDEX IF EXISTS {$indexName}");
        }
    }
};
