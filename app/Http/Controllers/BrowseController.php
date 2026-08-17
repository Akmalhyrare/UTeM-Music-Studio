<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use App\Models\Studio;
use App\Models\Booking;
use App\Services\StudioService;
use App\Support\Search;
use Illuminate\Support\Carbon;

class BrowseController extends Controller
{
    // Browse all items (public)
    public function items(Request $request)
    {
        $query = Item::with(['category', 'images'])->where('item_status', 'available');

        Search::apply($query, $request->search, Item::searchableColumns(), [
            'category' => ['category_name'],
        ]);

        if ($request->type) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('type', $request->type);
            });
        }
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        $items      = $query->orderBy('item_id', 'desc')->get();
        $categories = Category::all();

        return view('student.items', compact('items', 'categories'));
    }

    // Browse studios (public) — hide archived/inactive studios
    public function studios()
    {
        $studios = Studio::with('primaryImage')
            ->where('status', '!=', 'inactive')
            ->orderBy('studio_name')
            ->get();

        return view('student.studios', compact('studios'));
    }

    // Public studio preview page
    public function studio($id, StudioService $studioService)
    {
        $studio = Studio::with('images')
            ->where('status', '!=', 'inactive')
            ->findOrFail($id);

        $nextAvailable = $studioService->getNextAvailableSlot($studio, Carbon::today());

        $availableToday = false;
        if ($studio->status === 'available') {
            $availableToday = $nextAvailable !== null && $nextAvailable['date'] === Carbon::today()->toDateString();
        }

        return view('student.studio-show', compact('studio', 'nextAvailable', 'availableToday'));
    }
}