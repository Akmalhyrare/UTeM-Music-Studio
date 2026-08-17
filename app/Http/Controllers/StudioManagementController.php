<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudioRequest;
use App\Http\Requests\UpdateStudioRequest;
use App\Models\Studio;
use App\Services\StudioService;

class StudioManagementController extends Controller
{
    public function __construct(private StudioService $studioService)
    {
    }

    // List all studios (including inactive) for staff/admin management
    public function index()
    {
        $studios = Studio::with('primaryImage')
            ->withCount('bookings')
            ->orderBy('studio_name')
            ->get();

        return view('staff.studios.index', compact('studios'));
    }

    public function create()
    {
        return view('staff.studios.create');
    }

    public function store(StoreStudioRequest $request)
    {
        $studio = $this->studioService->createStudio($request->validated());

        return redirect()->route('staff.studios.edit', $studio->studio_id)
                          ->with('success', 'Studio created successfully! You can now add gallery images and manage availability.');
    }

    // Manage-studio page: details form + image gallery + unavailability list
    public function edit($id)
    {
        $studio = Studio::with(['images', 'unavailabilities' => function ($query) {
            $query->orderBy('start_at', 'desc');
        }])->findOrFail($id);

        return view('staff.studios.edit', compact('studio'));
    }

    public function update(UpdateStudioRequest $request, $id)
    {
        $studio = Studio::findOrFail($id);

        $this->studioService->updateStudio($studio, $request->validated());

        return redirect()->route('staff.studios.edit', $studio->studio_id)
                          ->with('success', 'Studio updated successfully!');
    }

    public function show($id)
    {
        $studio = Studio::with(['images', 'unavailabilities'])->findOrFail($id);

        return view('staff.studios.show', compact('studio'));
    }

    // Archive (soft-deactivate) — admin only
    public function archive($id)
    {
        $studio = Studio::findOrFail($id);

        $this->studioService->archiveStudio($studio);

        return redirect()->route('staff.studios.index')
                          ->with('success', 'Studio archived (marked inactive).');
    }
}
