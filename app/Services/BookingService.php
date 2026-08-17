<?php

namespace App\Services;

use App\Exceptions\BookingConflictException;
use App\Models\Booking;
use App\Models\Studio;
use App\Models\StudioUnavailability;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Statuses that still hold the studio's time slot.
     */
    private const ACTIVE_STATUSES = ['confirmed', 'completed'];

    /** Studio operating hours and slot size used to build the availability schedule. */
    private const OPERATING_START = '08:00';
    private const OPERATING_END = '23:30';
    private const SLOT_MINUTES = 60;

    /**
     * Create a new booking. Automatically confirmed if the slot is free.
     *
     * @throws BookingConflictException
     * @throws ValidationException
     */
    public function createBooking(array $data, int $studentId): Booking
    {
        return DB::transaction(function () use ($data, $studentId) {
            // Lock the studio row so concurrent booking attempts for the
            // same studio are serialized and cannot both pass the conflict check.
            $studio = Studio::where('studio_id', $data['studio_id'])->lockForUpdate()->first();

            if (!$studio) {
                throw ValidationException::withMessages([
                    'studio_id' => 'The selected studio does not exist.',
                ]);
            }

            $this->assertStudioBookable($studio, $data);

            if ($this->hasConflict($data)) {
                throw new BookingConflictException();
            }

            return Booking::create([
                'student_id'     => $studentId,
                'staff_id'       => null,
                'studio_id'      => $data['studio_id'],
                'booking_date'   => $data['booking_date'],
                'start_time'     => $data['start_time'],
                'end_time'       => $data['end_time'],
                'purpose'        => $data['purpose'] ?? null,
                'booking_status' => 'confirmed',
            ]);
        });
    }

    /**
     * Update an existing booking's schedule, re-checking for conflicts.
     *
     * @throws BookingConflictException
     */
    public function updateBooking(Booking $booking, array $data): Booking
    {
        return DB::transaction(function () use ($booking, $data) {
            $studio = Studio::where('studio_id', $data['studio_id'])->lockForUpdate()->first();

            $this->assertStudioBookable($studio, $data);

            if ($this->hasConflict($data, $booking->booking_id)) {
                throw new BookingConflictException();
            }

            $booking->update([
                'studio_id'    => $data['studio_id'],
                'booking_date' => $data['booking_date'],
                'start_time'   => $data['start_time'],
                'end_time'     => $data['end_time'],
                'purpose'      => $data['purpose'] ?? null,
            ]);

            return $booking;
        });
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(Booking $booking, ?int $staffId = null): Booking
    {
        $booking->update([
            'booking_status' => 'cancelled',
            'staff_id'       => $staffId ?? $booking->staff_id,
        ]);

        return $booking;
    }

    /**
     * Auto-complete every 'confirmed' booking whose end date/time has
     * already passed. No staff action involved — called on page load
     * (booking index/show controllers) and by the scheduled job in
     * routes/console.php so stats stay fresh even if no one opens those
     * pages. Mirrors the borrowings.is_overdue pattern already used in
     * this app, but persists the transition directly since booking_status
     * (not a separate flag) drives every report/dashboard query.
     */
    public function autoCompletePastBookings(): int
    {
        return Booking::where('booking_status', 'confirmed')
            ->whereRaw('(booking_date + end_time) < ?', [Carbon::now()])
            ->update(['booking_status' => 'completed']);
    }

    /**
     * Determine whether the given date/time range overlaps an existing
     * active booking for the same studio.
     */
    public function hasConflict(array $data, ?int $excludeBookingId = null): bool
    {
        $query = Booking::where('studio_id', $data['studio_id'])
            ->where('booking_date', $data['booking_date'])
            ->whereIn('booking_status', self::ACTIVE_STATUSES)
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);

        if ($excludeBookingId) {
            $query->where('booking_id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

    /**
     * Reject the booking if the studio's status or a scheduled
     * maintenance/blocked period makes the requested slot unavailable.
     *
     * @throws BookingConflictException
     */
    private function assertStudioBookable(Studio $studio, array $data): void
    {
        if ($studio->status === 'maintenance') {
            throw new BookingConflictException('Studio is under maintenance.');
        }

        if ($studio->status === 'blocked') {
            throw new BookingConflictException('Studio is temporarily blocked.');
        }

        if ($studio->status !== 'available') {
            throw new BookingConflictException('This studio is currently unavailable for booking.');
        }

        $start = Carbon::parse($data['booking_date'] . ' ' . $data['start_time']);
        $end = Carbon::parse($data['booking_date'] . ' ' . $data['end_time']);

        $period = StudioUnavailability::overlapping($studio->studio_id, $start, $end)->first();

        if ($period) {
            throw new BookingConflictException(
                $period->type === 'maintenance' ? 'Studio is under maintenance.' : 'Studio is temporarily blocked.'
            );
        }
    }

    /**
     * Build the day's availability schedule for a studio: one row per
     * operating-hour slot, each labelled available / booked / pending /
     * blocked.
     *
     * @return array{slots: array<int, array{start: string, end: string, label: string, status: string}>, fully_booked: bool}
     */
    public function getAvailability(Studio $studio, string $date, ?int $excludeBookingId = null): array
    {
        $slots = [];

        // The whole studio is blocked/under maintenance for the day if its overall status says so.
        $studioBlockedStatus = match ($studio->status) {
            'available'   => null,
            'maintenance' => 'maintenance',
            default       => 'blocked', // blocked, inactive
        };

        $query = Booking::where('studio_id', $studio->studio_id)
            ->where('booking_date', $date)
            ->whereIn('booking_status', array_merge(self::ACTIVE_STATUSES, ['pending']));

        if ($excludeBookingId) {
            $query->where('booking_id', '!=', $excludeBookingId);
        }

        $bookings = $query->get(['start_time', 'end_time', 'booking_status']);

        // Scheduled maintenance/blocked periods that overlap this day.
        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEnd = Carbon::parse($date)->endOfDay();
        $periods = StudioUnavailability::where('studio_id', $studio->studio_id)
            ->where('start_at', '<', $dayEnd)
            ->where('end_at', '>', $dayStart)
            ->get(['type', 'start_at', 'end_at']);

        $cursor = Carbon::parse(self::OPERATING_START);
        $end = Carbon::parse(self::OPERATING_END);

        while ($cursor->lt($end)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes(self::SLOT_MINUTES);

            // The final slot may be shorter than SLOT_MINUTES if it doesn't divide evenly into the operating window.
            if ($slotEnd->gt($end)) {
                $slotEnd = $end->copy();
            }

            $status = $studioBlockedStatus ?? 'available';

            if ($studioBlockedStatus === null) {
                foreach ($bookings as $booking) {
                    $bookingStart = Carbon::parse($booking->start_time);
                    $bookingEnd = Carbon::parse($booking->end_time);

                    // Overlap check between this slot and the booking's time range.
                    if ($bookingStart->lt($slotEnd) && $bookingEnd->gt($slotStart)) {
                        $status = in_array($booking->booking_status, self::ACTIVE_STATUSES, true)
                            ? 'booked'
                            : 'pending';
                        break;
                    }
                }
            }

            // Scheduled maintenance/blocked periods take precedence over any other status.
            $slotStartAt = Carbon::parse($date . ' ' . $slotStart->format('H:i'));
            $slotEndAt = Carbon::parse($date . ' ' . $slotEnd->format('H:i'));

            foreach ($periods as $period) {
                if ($period->start_at->lt($slotEndAt) && $period->end_at->gt($slotStartAt)) {
                    $status = $period->type === 'maintenance' ? 'maintenance' : 'blocked';
                    break;
                }
            }

            $slots[] = [
                'start'  => $slotStart->format('H:i'),
                'end'    => $slotEnd->format('H:i'),
                'label'  => $slotStart->format('H:i') . ' - ' . $slotEnd->format('H:i'),
                'status' => $status,
            ];

            $cursor = $slotEnd;
        }

        $fullyBooked = collect($slots)->every(fn (array $slot) => $slot['status'] !== 'available');

        return [
            'slots'        => $slots,
            'fully_booked' => $fullyBooked,
        ];
    }
}
