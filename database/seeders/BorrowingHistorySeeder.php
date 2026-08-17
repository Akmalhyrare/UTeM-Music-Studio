<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Bulk historical + near-future borrowing reservations (last 12 months ->
 * current month) for dashboard/report demo data. Adds rows only to `borrowings` and
 * `borrowing_details` (the latter is the required item linkage for a
 * borrowing — without it the borrowing has no items and reports relying on
 * `borrowing_details.quantity_borrowed` would treat it as empty).
 */
class BorrowingHistorySeeder extends Seeder
{
    private const PURPOSES = [
        'Band practice', 'Recording session', 'Class assignment', 'Final year project',
        'Performance rehearsal', 'Workshop', 'Stage performance', 'Cultural event',
        'Audition preparation', 'Personal practice',
    ];

    public function run(): void
    {
        $students = Student::where('status', 'active')->get();
        $items    = Item::all();
        $staff    = Staff::all();

        if ($students->isEmpty() || $items->isEmpty() || $staff->isEmpty()) {
            return;
        }

        $start   = Carbon::today()->subYear();
        $today   = Carbon::today();
        $maxFuture = $today->copy()->addDays(7);

        $detailRows = [];

        for ($weekStart = $start->copy(); $weekStart->lte($today); $weekStart->addWeek()) {
            $requestsThisWeek = random_int(6, 11);

            for ($i = 0; $i < $requestsThisWeek; $i++) {
                $pickup = $weekStart->copy()->addDays(random_int(0, 6));

                // Don't generate reservations too far beyond "now".
                if ($pickup->gt($maxFuture)) {
                    continue;
                }

                $returnDate = $pickup->copy()->addDays(random_int(1, 7));

                [$status, $collectedAt, $collectedBy, $returnedAt, $returnedBy, $staffId] =
                    $this->resolveLifecycle($pickup, $returnDate, $today, $staff);

                $createdAt = $pickup->copy()->subDays(random_int(1, 4))->setTime(random_int(8, 22), random_int(0, 59));
                $updatedAt = $returnedAt ?? $collectedAt ?? $createdAt;

                $borrowId = DB::table('borrowings')->insertGetId([
                    'student_id'    => $students->random()->student_id,
                    'staff_id'      => $staffId,
                    'pickup_date'   => $pickup->toDateString(),
                    'return_date'   => $returnDate->toDateString(),
                    'borrow_status' => $status,
                    'purpose'       => fake()->randomElement(self::PURPOSES),
                    'collected_at'  => $collectedAt?->toDateTimeString(),
                    'collected_by'  => $collectedBy,
                    'returned_at'   => $returnedAt?->toDateTimeString(),
                    'returned_by'   => $returnedBy,
                    'created_at'    => $createdAt->toDateTimeString(),
                    'updated_at'    => $updatedAt->toDateTimeString(),
                ], 'borrow_id');

                // 1-3 items per reservation.
                $itemCount   = random_int(1, 3);
                $chosenItems = $items->random(min($itemCount, $items->count()));

                foreach ($chosenItems as $item) {
                    $detailRows[] = [
                        'borrow_id'         => $borrowId,
                        'item_id'           => $item->item_id,
                        'quantity_borrowed' => random_int(1, 2),
                        'created_at'        => $createdAt->toDateTimeString(),
                        'updated_at'        => $updatedAt->toDateTimeString(),
                    ];
                }
            }
        }

        foreach (array_chunk($detailRows, 200) as $chunk) {
            DB::table('borrowing_details')->insert($chunk);
        }
    }

    /**
     * Decide a realistic lifecycle outcome for a reservation based on where
     * its pickup/return dates fall relative to "today".
     *
     * @return array{0: string, 1: ?Carbon, 2: ?int, 3: ?Carbon, 4: ?int, 5: ?int}
     */
    private function resolveLifecycle(Carbon $pickup, Carbon $returnDate, Carbon $today, $staff): array
    {
        $staffMember = $staff->random();

        // Pickup is still in the future: awaiting approval or already reserved.
        if ($pickup->gt($today)) {
            $status  = fake()->randomElement(['pending', 'pending', 'reserved']);
            $staffId = $status === 'reserved' ? $staffMember->staff_id : null;

            return [$status, null, null, null, null, $staffId];
        }

        // Pickup has happened (or is today) and the return window hasn't closed yet.
        if ($returnDate->gte($today)) {
            $roll = random_int(1, 10);

            if ($roll <= 7) {
                // Currently collected (out on loan, within the return window).
                $collectedAt = $pickup->copy()->setTime(random_int(9, 17), random_int(0, 59));

                return ['collected', $collectedAt, $staffMember->staff_id, null, null, $staffMember->staff_id];
            }

            if ($roll <= 9) {
                // Approved but not yet picked up.
                return ['reserved', null, null, null, null, $staffMember->staff_id];
            }

            // Never made it past the request stage.
            return ['pending', null, null, null, null, null];
        }

        // Return date is in the past — final outcome.
        $roll = random_int(1, 100);

        if ($roll <= 79) {
            // Returned on time / slightly early.
            $collectedAt = $pickup->copy()->setTime(random_int(9, 17), random_int(0, 59));
            $returnedAt  = $returnDate->copy()->setTime(random_int(9, 17), random_int(0, 59));

            return ['returned', $collectedAt, $staffMember->staff_id, $returnedAt, $staffMember->staff_id, $staffMember->staff_id];
        }

        if ($roll <= 80) {
            // Still out and overdue — collected, never returned. Kept rare
            // (~1% of past-due reservations) so the overdue dashboard count stays low.
            $collectedAt = $pickup->copy()->setTime(random_int(9, 17), random_int(0, 59));

            return ['collected', $collectedAt, $staffMember->staff_id, null, null, $staffMember->staff_id];
        }

        if ($roll <= 92) {
            // Returned late.
            $collectedAt = $pickup->copy()->setTime(random_int(9, 17), random_int(0, 59));
            $returnedAt  = $returnDate->copy()->addDays(random_int(1, 4))->setTime(random_int(9, 17), random_int(0, 59));

            return ['returned', $collectedAt, $staffMember->staff_id, $returnedAt, $staffMember->staff_id, $staffMember->staff_id];
        }

        if ($roll <= 97) {
            // Request was rejected by staff.
            return ['rejected', null, null, null, null, $staffMember->staff_id];
        }

        // Cancelled by the student before pickup.
        return ['cancelled', null, null, null, null, null];
    }
}
