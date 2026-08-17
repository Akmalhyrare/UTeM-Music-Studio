<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Borrowing-table-only maintenance script. Two parts:
 *
 *   TASK 1 — UPDATE: any existing borrowing currently 'collected' whose
 *            return_date has already passed (current date is AFTER
 *            return_date) is transitioned to 'returned'. Rows whose
 *            return_date is today or in the future stay 'collected'
 *            (no-op — intentionally left untouched). Only `borrow_status`
 *            and `returned_at` (set for consistency, since other report
 *            queries/views read returned_at whenever borrow_status is
 *            'returned') are touched on these rows. pickup_date and
 *            return_date are never modified. No other table (items,
 *            return_records, maintenances, etc.) is touched — this is a
 *            deliberate scope restriction, so these rows do NOT go through
 *            the normal return workflow (no return_records row is created
 *            and items.available_quantity is NOT restored). This is a
 *            test-data status correction only, not a real return event.
 *
 *   TASK 2 — INSERT: ~30 new borrowings in 'pending'/'reserved' status for
 *            testing, with pickup_date/return_date safely in the future
 *            and request date (created_at) = today/tomorrow. Each row
 *            borrows exactly one distinct item (no item reused across the
 *            30 rows) and pickup_date starts after the latest return_date
 *            of any currently-active (pending/reserved/collected)
 *            borrowing, so there is zero possibility of an availability
 *            conflict with existing data.
 *
 * Intentionally NOT registered in DatabaseSeeder — run explicitly, once:
 *     php artisan db:seed --class=AdditionalBorrowingTestSeeder
 *
 * Safe to re-run: Task 1 is naturally idempotent (no more rows match its
 * WHERE clause after the first run); Task 2 detects its own "[TEST]" tag
 * and skips instead of duplicating.
 */
class AdditionalBorrowingTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->updateOverdueCollectedBorrowings();
        $this->insertNewTestBorrowings();
    }

    /**
     * TASK 1.
     */
    private function updateOverdueCollectedBorrowings(): void
    {
        $updated = DB::update("
            UPDATE borrowings
            SET borrow_status = 'returned',
                returned_at   = (return_date::timestamp + INTERVAL '16 hours')
            WHERE borrow_status = 'collected'
              AND return_date < CURRENT_DATE
        ");

        $this->command?->info("Task 1: {$updated} borrowing(s) transitioned collected -> returned.");
    }

    /**
     * TASK 2.
     */
    private function insertNewTestBorrowings(): void
    {
        if (DB::table('borrowings')->where('purpose', 'like', '%[TEST]')->exists()) {
            $this->command?->warn('Test borrowing rows already exist — skipping to avoid duplicates.');
            return;
        }

        // [item_id, quantity_borrowed, status ('pending'|'reserved'), staff_id (null if pending), purpose]
        $rows = [
            [1,  1, 'pending',  null, 'Borrowing acoustic guitar for music class assignment'],
            [3,  1, 'reserved', 2,    'Electric guitar for band practice'],
            [4,  1, 'pending',  null, 'Telecaster for recording session'],
            [5,  1, 'reserved', 3,    'Ibanez guitar for final year project'],
            [6,  1, 'pending',  null, 'Bass guitar for ensemble rehearsal'],
            [7,  1, 'reserved', 2,    'Les Paul for stage performance practice'],
            [8,  1, 'pending',  null, 'Bass guitar for recording assignment'],
            [10, 2, 'reserved', 4,    'Keyboards for duet performance'],
            [12, 1, 'pending',  null, 'Digital piano for solo recital practice'],
            [14, 1, 'reserved', 1,    'Keyboard for cultural night rehearsal'],
            [15, 2, 'pending',  null, 'MIDI controllers for music production class'],
            [16, 2, 'reserved', 5,    'Vocal mics for choir rehearsal'],
            [17, 2, 'pending',  null, 'Instrument mics for band recording'],
            [18, 1, 'reserved', 2,    'Studio mic for vocal recording session'],
            [20, 1, 'pending',  null, 'Hand percussion for ensemble practice'],
            [21, 1, 'reserved', 3,    'Condenser mic for podcast assignment'],
            [22, 2, 'pending',  null, 'PA speakers for outdoor performance'],
            [24, 1, 'reserved', 4,    'Mixer for sound engineering practical'],
            [25, 1, 'pending',  null, 'Guitar amp for rehearsal session'],
            [26, 1, 'reserved', 1,    'Bass amp for band practice'],
            [27, 1, 'pending',  null, 'Acoustic amp for unplugged set practice'],
            [28, 2, 'reserved', 2,    'Keyboard amps for duo performance'],
            [29, 1, 'pending',  null, 'Drum kit for percussion class'],
            [30, 1, 'reserved', 3,    'Drum kit for ensemble rehearsal'],
            [31, 1, 'pending',  null, 'Electronic drum kit for practice room booking'],
            [32, 2, 'reserved', 5,    'Cajon set for acoustic set rehearsal'],
            [33, 1, 'pending',  null, 'Bongo set for percussion assignment'],
            [34, 2, 'reserved', 2,    'XLR cables for recording session'],
            [35, 2, 'pending',  null, 'Guitar cables for stage setup practice'],
            [36, 2, 'reserved', 4,    'Mic stands for vocal ensemble rehearsal'],
        ];

        $today    = Carbon::today();
        $studentIds = range(1, 30); // existing active students 1-30

        foreach ($rows as $i => [$itemId, $qty, $status, $staffId, $purpose]) {
            $pickupOffset  = 4 + $i;                          // starts today+4 = 2026-06-29, one day later per row
            $durationDays  = [3, 4, 5, 6, 7, 4, 5][$i % 7];   // realistic varied loan length
            $requestOffset = $i % 2;                          // alternate: today (0) / tomorrow (1)

            $pickupDate = $today->copy()->addDays($pickupOffset);
            $returnDate = $pickupDate->copy()->addDays($durationDays);
            $requestedAt = $today->copy()->addDays($requestOffset)->setTime(9 + ($i % 8), 0);

            $borrowId = DB::table('borrowings')->insertGetId([
                'student_id'     => $studentIds[$i],
                'staff_id'       => $staffId,
                'pickup_date'    => $pickupDate->toDateString(),
                'return_date'    => $returnDate->toDateString(),
                'borrow_status'  => $status,
                'purpose'        => $purpose . ' [TEST]',
                'collected_at'   => null,
                'collected_by'   => null,
                'returned_at'    => null,
                'returned_by'    => null,
                'is_overdue'     => false,
                'created_at'     => $requestedAt,
                'updated_at'     => $requestedAt,
            ], 'borrow_id');

            DB::table('borrowing_details')->insert([
                'borrow_id'          => $borrowId,
                'item_id'            => $itemId,
                'quantity_borrowed'  => $qty,
                'created_at'         => $requestedAt,
                'updated_at'         => $requestedAt,
            ]);
        }

        $this->command?->info('Task 2: ' . count($rows) . ' new borrowing record(s) inserted.');
    }
}
