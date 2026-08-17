<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingConflictException;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Studio;
use App\Services\BookingService;
use App\Support\Search;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    // List the logged-in student's bookings
    public function index(Request $request)
    {
        // No staff action needed — flip any 'confirmed' booking whose end
        // time has passed to 'completed' before listing.
        $this->bookingService->autoCompletePastBookings();

        $query = Booking::with('studio')->where('student_id', session('user_id'));

        Search::apply($query, $request->search, ['purpose'], [
            'studio' => ['studio_name'],
        ]);

        if ($request->status) {
            $query->where('booking_status', $request->status);
        }

        // Operational-priority ordering (see StaffBookingController::index
        // for the full explanation): upcoming confirmed bookings first
        // (soonest first), then past-due confirmed, completed, and
        // cancelled bookings (most recent first within each group).
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
                           ->get();

        return view('bookings.index', compact('bookings'));
    }

    // Show booking creation form
    public function create(Request $request)
    {
        $studios = Studio::where('status', '!=', 'inactive')->orderBy('studio_name')->get();
        $selectedStudio = $request->query('studio_id');

        return view('bookings.create', compact('studios', 'selectedStudio'));
    }

    // AJAX: return the day's availability schedule for a studio
    public function availability(Request $request, Studio $studio)
    {
        $request->validate([
            'date'    => 'required|date',
            'exclude' => 'nullable|integer',
        ]);

        $schedule = $this->bookingService->getAvailability(
            $studio,
            $request->query('date'),
            $request->query('exclude') ? (int) $request->query('exclude') : null
        );

        return response()->json([
            'studio' => $studio->studio_name,
            'date'   => $request->query('date'),
            ...$schedule,
        ]);
    }

    // Store a new booking — automatically confirmed if the slot is free
    public function store(StoreBookingRequest $request)
    {
        try {
            $this->bookingService->createBooking($request->validated(), session('user_id'));
        } catch (BookingConflictException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('bookings.index')
                          ->with('success', 'Booking confirmed!');
    }

    // Show a single booking belonging to the logged-in student
    public function show($id)
    {
        // No staff action needed — flip to 'completed' if the end time has
        // passed, so a direct/bookmarked link never shows a stale status.
        $this->bookingService->autoCompletePastBookings();

        $booking = Booking::with(['studio', 'staff'])
                           ->where('student_id', session('user_id'))
                           ->findOrFail($id);

        return view('bookings.show', compact('booking'));
    }

    // Show edit form for a confirmed (upcoming) booking
    public function edit($id)
    {
        $booking = Booking::where('student_id', session('user_id'))->findOrFail($id);

        if ($booking->booking_status !== 'confirmed') {
            return redirect()->route('bookings.index')
                              ->with('error', 'Only confirmed, upcoming bookings can be edited.');
        }

        $studios = Studio::where('status', '!=', 'inactive')->orderBy('studio_name')->get();

        return view('bookings.edit', compact('booking', 'studios'));
    }

    // Update a confirmed booking
    public function update(UpdateBookingRequest $request, $id)
    {
        $booking = Booking::where('student_id', session('user_id'))->findOrFail($id);

        if ($booking->booking_status !== 'confirmed') {
            return redirect()->route('bookings.index')
                              ->with('error', 'Only confirmed, upcoming bookings can be edited.');
        }

        try {
            $this->bookingService->updateBooking($booking, $request->validated());
        } catch (BookingConflictException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('bookings.index')
                          ->with('success', 'Booking updated successfully!');
    }

    // Cancel a booking belonging to the logged-in student
    public function destroy($id)
    {
        $booking = Booking::where('student_id', session('user_id'))->findOrFail($id);

        if ($booking->booking_status !== 'confirmed') {
            return redirect()->route('bookings.index')
                              ->with('error', 'Only confirmed, upcoming bookings can be cancelled.');
        }

        $this->bookingService->cancelBooking($booking);

        return redirect()->route('bookings.index')
                          ->with('success', 'Booking cancelled successfully.');
    }
}
