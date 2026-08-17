<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\ReturnRecord;
use Illuminate\Support\Facades\DB;

class BorrowingService
{
    /**
     * Number of units of an item still free for the given pickup/return date
     * range, after accounting for overlapping pending/reserved/collected
     * reservations.
     */
    public function getAvailableQuantity(int $itemId, string $pickupDate, string $returnDate, ?int $excludeBorrowId = null): int
    {
        $item = Item::findOrFail($itemId);

        $reserved = BorrowingDetail::where('item_id', $itemId)
            ->whereHas('borrowing', function ($q) use ($pickupDate, $returnDate, $excludeBorrowId) {
                $q->whereIn('borrow_status', ['pending', 'reserved', 'collected'])
                  ->where('pickup_date', '<=', $returnDate)
                  ->where('return_date', '>=', $pickupDate);

                if ($excludeBorrowId) {
                    $q->where('borrow_id', '!=', $excludeBorrowId);
                }
            })
            ->sum('quantity_borrowed');

        return max(0, $item->available_quantity - $reserved);
    }

    /**
     * Create a new borrowing reservation (status: pending). The reservation
     * immediately counts toward the date-range availability for the
     * requested items, blocking overlapping over-bookings.
     *
     * @throws InsufficientStockException
     */
    public function createBorrowing(array $data, int $studentId): Borrowing
    {
        return DB::transaction(function () use ($data, $studentId) {
            foreach ($data['items'] as $item) {
                Item::where('item_id', $item['item_id'])->lockForUpdate()->first();

                $available = $this->getAvailableQuantity($item['item_id'], $data['pickup_date'], $data['return_date']);

                if ($item['quantity'] > $available) {
                    $itemName = Item::find($item['item_id'])->item_name ?? 'item';
                    throw new InsufficientStockException(
                        "Only {$available} unit(s) of '{$itemName}' are available during the selected reservation period."
                    );
                }
            }

            $borrowing = Borrowing::create([
                'student_id'    => $studentId,
                'staff_id'      => null,
                'pickup_date'   => $data['pickup_date'],
                'return_date'   => $data['return_date'],
                'borrow_status' => 'pending',
                'purpose'       => $data['purpose'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                BorrowingDetail::create([
                    'borrow_id'         => $borrowing->borrow_id,
                    'item_id'           => $item['item_id'],
                    'quantity_borrowed' => $item['quantity'],
                ]);
            }

            return $borrowing;
        });
    }

    /**
     * Approve a pending reservation. Re-checks date-range availability
     * (race-safe) before confirming. Fails atomically if any item is no
     * longer available for the reserved dates.
     *
     * @throws InsufficientStockException
     */
    public function approveBorrowing(Borrowing $borrowing, int $staffId): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $staffId) {
            $details = $borrowing->borrowingDetails()->with('item')->get();

            foreach ($details as $detail) {
                $item = Item::where('item_id', $detail->item_id)->lockForUpdate()->first();

                $available = $this->getAvailableQuantity(
                    $detail->item_id,
                    $borrowing->pickup_date->toDateString(),
                    $borrowing->return_date->toDateString(),
                    $borrowing->borrow_id
                );

                if (!$item || $detail->quantity_borrowed > $available) {
                    throw new InsufficientStockException(
                        "Only {$available} unit(s) of '{$item?->item_name}' are available during the selected reservation period."
                    );
                }
            }

            $borrowing->update([
                'borrow_status' => 'reserved',
                'staff_id'      => $staffId,
            ]);

            return $borrowing;
        });
    }

    /**
     * Reject a pending reservation. No stock is affected.
     */
    public function rejectBorrowing(Borrowing $borrowing, int $staffId): Borrowing
    {
        $borrowing->update([
            'borrow_status' => 'rejected',
            'staff_id'      => $staffId,
        ]);

        return $borrowing;
    }

    /**
     * Cancel a pending or reserved (not yet collected) reservation.
     */
    public function cancelBorrowing(Borrowing $borrowing): Borrowing
    {
        $borrowing->update(['borrow_status' => 'cancelled']);

        return $borrowing;
    }

    /**
     * Mark a reserved borrowing's items as collected by the student.
     */
    public function collectBorrowing(Borrowing $borrowing, int $staffId): Borrowing
    {
        $borrowing->update([
            'borrow_status' => 'collected',
            'collected_at'  => now(),
            'collected_by'  => $staffId,
        ]);

        return $borrowing;
    }

    /**
     * Process returned items for a collected borrowing. Good-condition
     * items become available again for future reservations automatically
     * (the date range has ended). Damaged/lost items are taken out of
     * circulation and raise a maintenance record.
     */
    public function processReturn(Borrowing $borrowing, array $returns, int $staffId): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $returns, $staffId) {
            foreach ($returns as $return) {
                if ((int) $return['quantity_returned'] === 0) {
                    continue;
                }

                $detail = BorrowingDetail::where('borrow_id', $borrowing->borrow_id)
                    ->where('item_id', $return['item_id'])
                    ->firstOrFail();

                $returnRecord = ReturnRecord::create([
                    'borrow_id'         => $borrowing->borrow_id,
                    'item_id'           => $detail->item_id,
                    'staff_id'          => $staffId,
                    'return_date'       => now()->toDateString(),
                    'quantity_returned' => $return['quantity_returned'],
                    'item_condition'    => $return['item_condition'],
                    'damage_note'       => $return['damage_note'] ?? null,
                    'return_status'     => 'completed',
                ]);

                if ($return['item_condition'] !== 'good') {
                    Item::where('item_id', $detail->item_id)
                        ->decrement('available_quantity', $return['quantity_returned']);

                    Maintenance::create([
                        'item_id'            => $detail->item_id,
                        'staff_id'           => $staffId,
                        'return_id'          => $returnRecord->return_id,
                        'report_date'        => now()->toDateString(),
                        'issue_type'         => $return['item_condition'] === 'lost' ? 'lost' : 'damage',
                        'description'        => $return['damage_note'] ?? null,
                        'maintenance_status' => 'pending',
                    ]);
                }
            }

            $totalBorrowed = $borrowing->borrowingDetails()->sum('quantity_borrowed');
            $totalReturned = $borrowing->returnRecords()->sum('quantity_returned');

            if ($totalReturned >= $totalBorrowed) {
                $borrowing->update([
                    'borrow_status' => 'returned',
                    'returned_at'   => now(),
                    'returned_by'   => $staffId,
                ]);
            }

            return $borrowing->refresh();
        });
    }

    /**
     * Mark a maintenance issue as resolved and return its item quantity
     * to the available pool.
     */
    public function resolveMaintenance(Maintenance $maintenance): Maintenance
    {
        return DB::transaction(function () use ($maintenance) {
            if ($maintenance->maintenance_status !== 'resolved') {
                $quantity = $maintenance->returnRecord?->quantity_returned ?? 1;

                Item::where('item_id', $maintenance->item_id)
                    ->increment('available_quantity', $quantity);
            }

            $maintenance->update(['maintenance_status' => 'resolved']);

            return $maintenance;
        });
    }
}
