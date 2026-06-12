<?php

namespace App\Http\Controllers;

use App\Models\Item;

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

        return view('landing', compact('items', 'totalItems', 'availableItems'));
    }
}