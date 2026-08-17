<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\Student;
use App\Models\Studio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Bulk historical + near-future bookings (last 12 months -> current month)
 * for dashboard/report demo data. Adds rows only to `bookings`.
 */
class BookingHistorySeeder extends Seeder
{
    /** Realistic purpose text pools per studio type. */
    private const PURPOSES = [
        'music' => [
            'Band practice', 'Recording session', 'Vocal rehearsal', 'Songwriting session',
            'Solo instrument practice', 'Guitar ensemble rehearsal', 'Music club jam session',
        ],
        'dance' => [
            'Dance practice', 'Choreography rehearsal', 'Cultural dance training',
            'Performance run-through', 'Dance society workshop',
        ],
        'gamelan' => [
            'Gamelan ensemble rehearsal', 'Traditional music workshop', 'Gamelan recital preparation',
        ],
        'caklempong' => [
            'Caklempong ensemble practice', 'Traditional performance rehearsal', 'Caklempong workshop',
        ],
    ];

    /** Candidate start hours grouped by time-of-day block. */
    private const TIME_BLOCKS = [
        'morning'   => [8, 9, 10],
        'afternoon' => [12, 13, 14, 15],
        'evening'   => [17, 18, 19, 20],
    ];

    public function run(): void
    {
        $students = Student::where('status', 'active')->get();
        $staff    = Staff::all();
        $studios  = Studio::all();

        if ($students->isEmpty() || $studios->isEmpty()) {
            return;
        }

        $start = Carbon::today()->subYear();
        $today = Carbon::today();

        $rows = [];

        for ($date = $start->copy(); $date->lte($today); $date->addDay()) {
            $isWeekend = $date->isWeekend();

            // Weekends are the busiest; weekdays are mostly quiet evenings.
            $bookingsToday = $isWeekend
                ? random_int(3, 6)
                : (random_int(1, 10) <= 7 ? random_int(0, 2) : 0);

            for ($i = 0; $i < $bookingsToday; $i++) {
                $studio      = $studios->random();
                $purposePool = self::PURPOSES[$studio->studio_type] ?? self::PURPOSES['music'];

                // Weekends skew toward afternoon/evening; weekdays toward evening.
                $block = $isWeekend
                    ? fake()->randomElement(['morning', 'afternoon', 'afternoon', 'evening', 'evening'])
                    : fake()->randomElement(['morning', 'afternoon', 'evening', 'evening']);

                $startHour = fake()->randomElement(self::TIME_BLOCKS[$block]);

                // Mostly short 1-2hr sessions, occasionally a longer 3-6hr block (e.g. a recital).
                $duration = random_int(1, 5) === 1 ? random_int(3, 6) : random_int(1, 2);
                $endHour  = min($startHour + $duration, 23);

                if ($endHour <= $startHour) {
                    continue;
                }

                $startTime = sprintf('%02d:00', $startHour);
                $endTime   = sprintf('%02d:%s', $endHour, $endHour === 23 ? '30' : '00');

                // Skip if this would overlap an already-generated active booking
                // for the same studio/date (mirrors BookingService::hasConflict).
                $conflict = false;
                foreach ($rows as $existing) {
                    if ($existing['studio_id'] === $studio->studio_id
                        && $existing['booking_date'] === $date->toDateString()
                        && in_array($existing['booking_status'], ['confirmed', 'completed'], true)
                        && $startTime < $existing['end_time']
                        && $endTime > $existing['start_time']) {
                        $conflict = true;
                        break;
                    }
                }

                if ($conflict) {
                    continue;
                }

                // Status distribution: past dates are mostly completed with some
                // cancellations; today/future dates are mostly confirmed.
                if ($date->lt($today)) {
                    $status = fake()->randomElement([
                        'completed', 'completed', 'completed', 'completed',
                        'completed', 'completed', 'completed', 'completed',
                        'cancelled', 'cancelled',
                    ]);
                } elseif ($date->isSameDay($today)) {
                    $status = fake()->randomElement(['confirmed', 'confirmed', 'confirmed', 'completed', 'cancelled']);
                } else {
                    $status = fake()->randomElement(['confirmed', 'confirmed', 'confirmed', 'confirmed', 'cancelled']);
                }

                $staffId = in_array($status, ['completed', 'cancelled'], true) && $staff->isNotEmpty()
                    ? $staff->random()->staff_id
                    : null;

                $createdAt = $date->copy()->subDays(random_int(1, 5))->setTime(random_int(8, 22), random_int(0, 59));

                $rows[] = [
                    'student_id'     => $students->random()->student_id,
                    'staff_id'       => $staffId,
                    'studio_id'      => $studio->studio_id,
                    'booking_date'   => $date->toDateString(),
                    'start_time'     => $startTime,
                    'end_time'       => $endTime,
                    'booking_status' => $status,
                    'purpose'        => fake()->randomElement($purposePool),
                    'created_at'     => $createdAt->toDateTimeString(),
                    'updated_at'     => $createdAt->toDateTimeString(),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('bookings')->insert($chunk);
        }
    }
}
