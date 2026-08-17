<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Existing 'pending'/'approved'/'rejected' bookings become 'confirmed' under the new auto-confirm model
        DB::table('bookings')->whereIn('booking_status', ['pending', 'approved'])->update(['booking_status' => 'confirmed']);
        DB::table('bookings')->where('booking_status', 'rejected')->update(['booking_status' => 'cancelled']);

        // Avoid Schema::change() (requires doctrine/dbal) — alter the default via raw SQL
        DB::statement("ALTER TABLE bookings ALTER COLUMN booking_status SET DEFAULT 'confirmed'");

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['studio_id', 'booking_date'], 'bookings_studio_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_studio_date_index');
        });

        DB::statement("ALTER TABLE bookings ALTER COLUMN booking_status SET DEFAULT 'pending'");
    }
};
