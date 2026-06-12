<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use App\Models\Studio;
use App\Models\Booking;

class BrowseController extends Controller
{
    // Browse all items (public)
    public function items(Request $request)
    {
        $query = Item::with('category')->where('item_status', 'available');

        if ($request->search) {
            $query->where('item_name', 'like', '%' . $request->search . '%');
        }
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

    // Browse studios (public)
    public function studios()
    {
        $studios = Studio::all();
        return view('student.studios', compact('studios'));
    }
}