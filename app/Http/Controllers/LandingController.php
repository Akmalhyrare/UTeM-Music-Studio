<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Studio;

class LandingController extends Controller
{
    public function index()
    {
        $items          = Item::with('category')
                              ->where('item_status', 'available')
                              ->orderBy('item_id', 'desc')
                              ->get();
        $totalItems     = Item::count();
        $availableItems = Item::where('item_status', 'available')->count();

        $studios = Studio::with('primaryImage')
            ->where('status', '!=', 'inactive')
            ->orderBy('studio_name')
            ->take(4)
            ->get();

        return view('landing', compact('items', 'totalItems', 'availableItems', 'studios'));
    }
}