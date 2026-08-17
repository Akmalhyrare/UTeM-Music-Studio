<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Student;
use App\Services\BorrowingService;
use Carbon\Carbon;

class BorrowingSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::where('status', 'active')->get();
        $items    = Item::where('available_quantity', '>', 0)->get();
        $service  = app(BorrowingService::class);
        $staffId  = \App\Models\Staff::first()?->staff_id;

        if ($students->isEmpty() || $items->isEmpty() || !$staffId) {
            return;
        }

        $randomItems = fn (int $count) => $items->random(min($count, $items->count()))
            ->map(fn (Item $item) => ['item_id' => $item->item_id, 'quantity' => random_int(1, 2)])
            ->values()
            ->all();

        // --- Pending requests (awaiting staff approval) ---
        foreach (range(1, 8) as $i) {
            $pickup = Carbon::today()->addDays(random_int(1, 7));
            try {
                $service->createBorrowing([
                    'pickup_date' => $pickup->toDateString(),
                    'return_date' => $pickup->copy()->addDays(random_int(1, 7))->toDateString(),
                    'purpose'     => fake()->randomElement(['Final year project', 'Class assignment', 'Performance rehearsal']),
                    'items'       => $randomItems(random_int(1, 2)),
                ], $students->random()->student_id);
            } catch (\Throwable $e) {
                // insufficient availability for the chosen dates — skip
            }
        }

        // --- Reserved (approved, awaiting pickup) ---
        foreach (range(1, 6) as $i) {
            $pickup = Carbon::today()->addDays(random_int(1, 7));
            try {
                $borrowing = $service->createBorrowing([
                    'pickup_date' => $pickup->toDateString(),
                    'return_date' => $pickup->copy()->addDays(random_int(1, 7))->toDateString(),
                    'purpose'     => fake()->randomElement(['Recording session', 'Workshop', 'Stage performance']),
                    'items'       => $randomItems(random_int(1, 2)),
                ], $students->random()->student_id);

                $service->approveBorrowing($borrowing, $staffId);
            } catch (\Throwable $e) {
                // insufficient availability — leave as pending
            }
        }

        // --- Collected and fully returned, good condition ---
        foreach (range(1, 5) as $i) {
            $pickup = Carbon::today()->subDays(random_int(2, 10));
            try {
                $borrowing = $service->createBorrowing([
                    'pickup_date' => $pickup->toDateString(),
                    'return_date' => $pickup->copy()->addDays(random_int(1, 5))->toDateString(),
                    'purpose'     => 'Band practice',
                    'items'       => $randomItems(1),
                ], $students->random()->student_id);

                $service->approveBorrowing($borrowing, $staffId);
                $service->collectBorrowing($borrowing, $staffId);

                $returns = $borrowing->borrowingDetails->map(fn ($detail) => [
                    'item_id'           => $detail->item_id,
                    'quantity_returned' => $detail->quantity_borrowed,
                    'item_condition'    => 'good',
                ])->all();

                $service->processReturn($borrowing, $returns, $staffId);
            } catch (\Throwable $e) {
                // insufficient availability — leave as pending
            }
        }

        // --- Returned with a damaged item (raises a maintenance record) ---
        foreach (range(1, 2) as $i) {
            $pickup = Carbon::today()->subDays(random_int(2, 5));
            try {
                $borrowing = $service->createBorrowing([
                    'pickup_date' => $pickup->toDateString(),
                    'return_date' => $pickup->copy()->addDays(2)->toDateString(),
                    'purpose'     => 'Recording session',
                    'items'       => $randomItems(1),
                ], $students->random()->student_id);

                $service->approveBorrowing($borrowing, $staffId);
                $service->collectBorrowing($borrowing, $staffId);

                $returns = $borrowing->borrowingDetails->map(fn ($detail) => [
                    'item_id'           => $detail->item_id,
                    'quantity_returned' => $detail->quantity_borrowed,
                    'item_condition'    => 'damaged',
                    'damage_note'       => 'Returned with visible damage — needs inspection',
                ])->all();

                $service->processReturn($borrowing, $returns, $staffId);
            } catch (\Throwable $e) {
                // insufficient availability — leave as pending
            }
        }

        // --- Rejected requests ---
        foreach (range(1, 3) as $i) {
            $pickup = Carbon::today()->addDays(random_int(3, 14));
            try {
                $borrowing = $service->createBorrowing([
                    'pickup_date' => $pickup->toDateString(),
                    'return_date' => $pickup->copy()->addDays(2)->toDateString(),
                    'purpose'     => 'Personal project',
                    'items'       => $randomItems(1),
                ], $students->random()->student_id);

                $service->rejectBorrowing($borrowing, $staffId);
            } catch (\Throwable $e) {
                // insufficient availability — skip
            }
        }

        // --- Cancelled requests (student-initiated) ---
        foreach (range(1, 2) as $i) {
            $pickup = Carbon::today()->addDays(random_int(3, 14));
            try {
                $borrowing = $service->createBorrowing([
                    'pickup_date' => $pickup->toDateString(),
                    'return_date' => $pickup->copy()->addDays(2)->toDateString(),
                    'purpose'     => 'Plans changed',
                    'items'       => $randomItems(1),
                ], $students->random()->student_id);

                $service->cancelBorrowing($borrowing);
            } catch (\Throwable $e) {
                // insufficient availability — skip
            }
        }
    }
}
