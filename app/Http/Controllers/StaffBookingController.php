<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Studio;
use App\Models\Student;
use App\Services\BookingService;
use App\Support\Search;
use Illuminate\Http\Request;

class StaffBookingController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    // List all bookings with optional filters
    public function index(Request $request)
    {
        // No staff action needed — flip any 'confirmed' booking whose end
        // time has passed to 'completed' before listing.
        $this->bookingService->autoCompletePastBookings();

        $query = Booking::with(['student', 'studio']);

        Search::apply($query, $request->search, ['purpose'], [
            'student' => Student::searchableColumns(),
            'studio'  => ['studio_name'],
        ]);

        if ($request->status) {
            $query->where('booking_status', $request->status);
        }

        if ($request->studio_id) {
            $query->where('studio_id', $request->studio_id);
        }

        if ($request->date) {
            $query->where('booking_date', $request->date);
        }

        // Operational-priority ordering:
        // 1. Confirmed bookings still upcoming (booking_date >= today)
        //    come first, soonest first.
        // 2. Confirmed bookings whose date has already passed (awaiting
        //    completion) come next, most recently passed first.
        // 3. Completed bookings, most recent first.
        // 4. Cancelled bookings always last, most recent first.
        $bookings = $query->orderByRaw("
                               CASE
                                   WHEN booking_status = 'confirmed' AND booking_date >= CURRENT_DATE THEN 1
                                   WHEN booking_status = 'confirmed' AND booking_date <  CURRENT_DATE THEN 2
                                   WHEN booking_status = 'completed' THEN 3
                                   WHEN booking_status = 'cancelled' THEN 4
                                   ELSE 5
                               END
                           ")
                           ->orderByRaw("
                               CASE
                                   WHEN booking_status = 'confirmed' AND booking_date >= CURRENT_DATE
                                       THEN  EXTRACT(EPOCH FROM (booking_date + start_time))
                                   ELSE
                                       -EXTRACT(EPOCH FROM (booking_date + start_time))
                               END
                           ")
                           ->paginate(15)
                           ->withQueryString();

        $studios       = Studio::orderBy('studio_name')->get();
        $confirmedCount = Booking::where('booking_status', 'confirmed')->count();
        $todayCount     = Booking::where('booking_status', 'confirmed')
                                  ->whereDate('booking_date', now()->toDateString())
                                  ->count();

        return view('staff.bookings.index', compact('bookings', 'studios', 'confirmedCount', 'todayCount'));
    }

    // Show a single booking
    public function show($id)
    {
        // No staff action needed — flip to 'completed' if the end time has
        // passed, so a direct/bookmarked link never shows a stale status.
        $this->bookingService->autoCompletePastBookings();

        $booking = Booking::with(['student', 'studio', 'staff'])->findOrFail($id);

        return view('staff.bookings.show', compact('booking'));
    }

    // Cancel a confirmed booking. Completion is automatic (see
    // BookingService::autoCompletePastBookings) and no longer a staff action.
    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $request->validate([
            'booking_status' => 'required|in:cancelled',
        ]);

        if ($booking->booking_status !== 'confirmed') {
            return redirect()->route('staff.bookings.show', $booking->booking_id)
                              ->with('error', 'Only confirmed bookings can be updated.');
        }

        $this->bookingService->cancelBooking($booking, session('user_id'));

        return redirect()->route('staff.bookings.index')
                          ->with('success', 'Booking cancelled successfully!');
    }

    // Delete a booking record
    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return redirect()->route('staff.bookings.index')
                          ->with('success', 'Booking deleted successfully!');
    }
}
