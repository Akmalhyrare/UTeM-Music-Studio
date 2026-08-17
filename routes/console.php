<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep borrowings.is_overdue (maintained by trg_borrowings_set_overdue on write)
// in sync for rows that become overdue without being written to.
Schedule::call(fn () => DB::statement('CALL sp_refresh_overdue_borrowings(NULL)'))
    ->dailyAt('00:05')
    ->name('refresh-overdue-borrowings')
    ->onOneServer();

// Auto-complete confirmed studio bookings whose end date/time has passed.
// Booking pages already do this on load (see BookingService::autoCompletePastBookings);
// this job is just a freshness safety net for dashboard/report stats when no
// one has opened a booking page since the slot ended.
Schedule::call(fn () => app(\App\Services\BookingService::class)->autoCompletePastBookings())
    ->everyFiveMinutes()
    ->name('auto-complete-bookings')
    ->onOneServer();

// Database + storage backup, then prune old backups per config/backup.php.
Schedule::command('backup:run')
    ->dailyAt('01:00')
    ->onOneServer();

Schedule::command('backup:clean')
    ->dailyAt('01:30')
    ->onOneServer();

// Alerts if the latest backup is missing/too old or storage usage is too high.
Schedule::command('backup:monitor')
    ->dailyAt('02:00')
    ->onOneServer();
