<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Studio;
use App\Services\BookingService;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::where('status', 'active')->get();
        $staff    = Staff::first();
        $studios  = Studio::all();
        $service  = app(BookingService::class);

        if ($students->isEmpty() || $studios->isEmpty()) {
            return;
        }

        // --- Past, completed bookings (history) ---
        foreach (range(1, 15) as $i) {
            $studio = $studios->random();
            $date   = Carbon::today()->subDays(random_int(1, 30));
            $start  = sprintf('%02d:00', random_int(9, 16));
            $end    = sprintf('%02d:00', (int) substr($start, 0, 2) + 2);

            Booking::create([
                'student_id'     => $students->random()->student_id,
                'staff_id'       => $staff?->staff_id,
                'studio_id'      => $studio->studio_id,
                'booking_date'   => $date->toDateString(),
                'start_time'     => $start,
                'end_time'       => $end,
                'booking_status' => 'completed',
                'purpose'        => fake()->randomElement(['Band practice', 'Recording session', 'Rehearsal', 'Workshop']),
            ]);
        }

        // --- A few cancelled bookings ---
        foreach (range(1, 5) as $i) {
            $studio = $studios->random();
            $date   = Carbon::today()->addDays(random_int(1, 20));

            Booking::create([
                'student_id'     => $students->random()->student_id,
                'staff_id'       => $staff?->staff_id,
                'studio_id'      => $studio->studio_id,
                'booking_date'   => $date->toDateString(),
                'start_time'     => '09:00',
                'end_time'       => '11:00',
                'booking_status' => 'cancelled',
                'purpose'        => 'Plans changed',
            ]);
        }

        // --- Upcoming confirmed bookings, created through the service so
        //     conflict detection / auto-confirm logic is exercised ---
        $slots = [
            ['start' => '09:00', 'end' => '11:00'],
            ['start' => '13:00', 'end' => '15:00'],
            ['start' => '15:00', 'end' => '17:00'],
        ];

        for ($day = 1; $day <= 7; $day++) {
            $date = Carbon::today()->addDays($day);

            foreach ($studios as $studio) {
                // ~50% chance a slot is booked on a given day for a given studio
                $slot = $slots[array_rand($slots)];

                if (random_int(0, 1) === 1) {
                    try {
                        $service->createBooking([
                            'studio_id'    => $studio->studio_id,
                            'booking_date' => $date->toDateString(),
                            'start_time'   => $slot['start'],
                            'end_time'     => $slot['end'],
                            'purpose'      => fake()->randomElement(['Band practice', 'Recording session', 'Audition prep', 'Rehearsal']),
                        ], $students->random()->student_id);
                    } catch (\Throwable $e) {
                        // Slot already taken for this studio/date — skip, this is expected
                    }
                }
            }
        }

        // --- A dedicated, documented slot for manual conflict-testing ---
        // Music Studio is confirmed for tomorrow 10:00-12:00. Attempting to book
        // Music Studio for any overlapping time tomorrow should be rejected
        // with a "studio already booked" conflict error.
        $musicStudio = $studios->firstWhere('studio_type', 'music') ?? $studios->first();
        try {
            $service->createBooking([
                'studio_id'    => $musicStudio->studio_id,
                'booking_date' => Carbon::tomorrow()->toDateString(),
                'start_time'   => '10:00',
                'end_time'     => '12:00',
                'purpose'      => 'CONFLICT TEST SLOT — do not remove',
            ], $students->first()->student_id);
        } catch (\Throwable $e) {
            // already exists, fine
        }
    }
}
