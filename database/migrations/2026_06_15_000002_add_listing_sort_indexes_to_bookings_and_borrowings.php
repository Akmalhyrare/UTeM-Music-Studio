<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supports the staff/admin Bookings and Borrowing listing sort
 * (date/time DESC, then status priority).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['booking_date', 'start_time', 'booking_status'], 'bookings_date_time_status_index');
        });

        Schema::table('borrowings', function (Blueprint $table) {
            $table->index(['pickup_date', 'created_at', 'borrow_status'], 'borrowings_pickup_created_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_date_time_status_index');
        });

        Schema::table('borrowings', function (Blueprint $table) {
            $table->dropIndex('borrowings_pickup_created_status_index');
        });
    }
};
