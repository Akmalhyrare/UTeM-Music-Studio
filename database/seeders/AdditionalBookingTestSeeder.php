<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Adds 30 standalone future "confirmed" studio bookings for manual testing.
 *
 * Intentionally NOT registered in DatabaseSeeder::run() — running the global
 * `php artisan db:seed` will never touch this file or any other table. Run
 * it explicitly and only once:
 *
 *     php artisan db:seed --class=AdditionalBookingTestSeeder
 *
 * Every row is tagged with a "[TEST]" suffix in `purpose` so the seeder can
 * detect it has already run (no-ops instead of duplicating) and so the rows
 * are easy to identify/remove later:
 *
 *     DELETE FROM bookings WHERE purpose LIKE '%[TEST]';
 *
 * Each row uses a real, existing student_id (1-30, all currently 'active')
 * and studio_id (1-9, all currently 'available'). Dates are computed as
 * "today + N days" at run time, so every booking_date is always in the
 * future regardless of when this seeder is executed. Each (studio_id, date)
 * pair is unique across the 30 rows, and the single date that already has
 * an active booking in this dataset (studio 2 on day +1) is deliberately
 * not reused for studio 2 — so there is no possibility of a time-slot
 * conflict with existing data. staff_id is left NULL, matching the normal
 * auto-confirm flow (BookingService::createBooking()), since no staff
 * action is involved in creating or confirming a booking.
 */
class AdditionalBookingTestSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('bookings')->where('purpose', 'like', '%[TEST]')->exists()) {
            $this->command?->warn('Test booking rows already exist — skipping to avoid duplicates.');
            return;
        }

        // [day offset from today, student_id, studio_id, start_time, end_time, purpose]
        $rows = [
            [1,  1,  1, '09:00:00', '10:00:00', 'Vocal warm-up and recording rehearsal'],
            [2,  2,  2, '10:30:00', '11:30:00', 'Dance choreography practice'],
            [3,  3,  3, '13:00:00', '14:00:00', 'Gamelan ensemble rehearsal'],
            [4,  4,  4, '14:30:00', '16:00:00', 'Caklempong performance practice'],
            [5,  5,  5, '16:00:00', '17:00:00', 'Recording session for final year project'],
            [6,  6,  6, '17:30:00', '18:30:00', 'Ensemble group rehearsal'],
            [7,  7,  7, '19:00:00', '20:30:00', 'Stage performance rehearsal'],
            [8,  8,  8, '20:30:00', '21:30:00', 'Concert rehearsal for upcoming showcase'],
            [9,  9,  9, '11:00:00', '12:00:00', 'Band practice session'],
            [10, 10, 1, '09:00:00', '10:00:00', 'Solo guitar practice'],
            [11, 11, 2, '10:30:00', '11:30:00', 'Traditional dance rehearsal'],
            [12, 12, 3, '13:00:00', '14:00:00', 'Gamelan workshop practice'],
            [13, 13, 4, '14:30:00', '16:00:00', 'Caklempong ensemble practice'],
            [14, 14, 5, '16:00:00', '17:00:00', 'Audio mixing and recording session'],
            [15, 15, 6, '17:30:00', '18:30:00', 'Ensemble rehearsal for cultural night'],
            [16, 16, 7, '19:00:00', '20:30:00', 'Theatre rehearsal'],
            [17, 17, 8, '20:30:00', '21:30:00', 'Final year project performance practice'],
            [18, 18, 9, '11:00:00', '12:00:00', 'Vocal group practice'],
            [19, 19, 1, '09:00:00', '10:00:00', 'Keyboard practice session'],
            [20, 20, 2, '10:30:00', '11:30:00', 'Dance ensemble rehearsal'],
            [21, 21, 3, '13:00:00', '14:00:00', 'Gamelan practice for cultural performance'],
            [22, 22, 4, '14:30:00', '16:00:00', 'Caklempong group practice'],
            [23, 23, 5, '16:00:00', '17:00:00', 'Recording session for music assignment'],
            [24, 24, 6, '17:30:00', '18:30:00', 'Ensemble practice for end-of-semester concert'],
            [25, 25, 7, '19:00:00', '20:30:00', 'Rehearsal for drama production'],
            [26, 26, 8, '20:30:00', '21:30:00', 'Performance practice for graduation showcase'],
            [27, 27, 9, '11:00:00', '12:00:00', 'Band rehearsal session'],
            [28, 28, 1, '09:00:00', '10:00:00', 'Music theory practical session'],
            [29, 29, 2, '10:30:00', '11:30:00', 'Contemporary dance practice'],
            [30, 30, 3, '13:00:00', '14:00:00', 'Gamelan club rehearsal'],
        ];

        $now = Carbon::now();
        $records = array_map(function ($row) use ($now) {
            [$dayOffset, $studentId, $studioId, $start, $end, $purpose] = $row;

            return [
                'student_id'     => $studentId,
                'staff_id'       => null,
                'studio_id'      => $studioId,
                'booking_date'   => Carbon::today()->addDays($dayOffset)->toDateString(),
                'start_time'     => $start,
                'end_time'       => $end,
                'booking_status' => 'confirmed',
                'purpose'        => $purpose . ' [TEST]',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }, $rows);

        DB::table('bookings')->insert($records);

        $this->command?->info(count($records) . ' test booking record(s) inserted.');
    }
}
