<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudioImagesRequest;
use App\Models\Studio;
use App\Models\StudioImage;
use App\Services\StudioService;
use Illuminate\Http\Request;

class StudioImageController extends Controller
{
    public function __construct(private StudioService $studioService)
    {
    }

    public function store(StoreStudioImagesRequest $request, $studioId)
    {
        $studio = Studio::findOrFail($studioId);

        $existing = $studio->images()->count();
        $incoming = count($request->file('images', []));

        if ($existing + $incoming > 10) {
            return back()->with('error', 'A studio can have a maximum of 10 images. '
                . "This studio already has {$existing}.");
        }

        $this->studioService->addImages($studio, $request->file('images'));

        return redirect()->route('staff.studios.edit', $studio->studio_id)
                          ->with('success', 'Images uploaded successfully!');
    }

    // Admin-only
    public function destroy($studioId, $imageId)
    {
        $image = StudioImage::where('studio_id', $studioId)->findOrFail($imageId);

        $this->studioService->deleteImage($image);

        return redirect()->route('staff.studios.edit', $studioId)
                          ->with('success', 'Image deleted.');
    }

    public function setPrimary($studioId, $imageId)
    {
        $image = StudioImage::where('studio_id', $studioId)->findOrFail($imageId);

        $this->studioService->setPrimaryImage($image);

        return redirect()->route('staff.studios.edit', $studioId)
                          ->with('success', 'Primary image updated.');
    }

    // AJAX: persist a new image order
    public function reorder(Request $request, $studioId)
    {
        $request->validate([
            'image_ids'   => 'required|array',
            'image_ids.*' => 'integer',
        ]);

        $studio = Studio::findOrFail($studioId);

        $this->studioService->reorderImages($studio, $request->input('image_ids'));

        return response()->json(['success' => true]);
    }
}
