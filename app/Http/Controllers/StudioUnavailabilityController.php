<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudioUnavailabilityRequest;
use App\Models\Studio;
use App\Models\StudioUnavailability;
use App\Services\StudioService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StudioUnavailabilityController extends Controller
{
    public function __construct(private StudioService $studioService)
    {
    }

    // Render the staff calendar for a studio
    public function calendar($studioId)
    {
        $studio = Studio::findOrFail($studioId);

        return view('staff.studios.calendar', compact('studio'));
    }

    // AJAX: events (bookings + maintenance/blocked periods) for a date range
    public function calendarData(Request $request, $studioId)
    {
        $studio = Studio::findOrFail($studioId);

        $request->validate([
            'start' => 'required|date',
            'end'   => 'required|date|after_or_equal:start',
        ]);

        $events = $this->studioService->getCalendarEvents(
            $studio,
            Carbon::parse($request->query('start'))->startOfDay(),
            Carbon::parse($request->query('end'))->endOfDay()
        );

        return response()->json(['events' => $events]);
    }

    // AJAX: create a maintenance/blocked period
    public function store(StoreStudioUnavailabilityRequest $request, $studioId)
    {
        $studio = Studio::findOrFail($studioId);

        $period = $this->studioService->createUnavailability($studio, [
            ...$request->validated(),
            'staff_id' => session('user_type') === 'staff' ? session('user_id') : null,
        ]);

        return response()->json(['success' => true, 'period' => $period]);
    }

    // AJAX: delete a maintenance/blocked period
    public function destroy($studioId, $periodId)
    {
        $period = StudioUnavailability::where('studio_id', $studioId)->findOrFail($periodId);

        $this->studioService->deleteUnavailability($period);

        return response()->json(['success' => true]);
    }
}
