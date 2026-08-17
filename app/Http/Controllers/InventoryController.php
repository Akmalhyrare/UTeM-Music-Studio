<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use App\Services\ItemImageService;
use App\Support\Search;

class InventoryController extends Controller
{
    public function __construct(private ItemImageService $itemImageService)
    {
    }

    public function index(Request $request)
    {
        $query = Item::with('category');

        Search::apply($query, $request->search, Item::searchableColumns(), [
            'category' => ['category_name'],
        ]);

        if ($request->type) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
        }

        if ($request->status) {
            $query->where('item_status', $request->status);
        }

        $items          = $query->orderBy('item_id', 'desc')->paginate(15)->withQueryString();
        $totalEquipment = Item::whereHas('category', fn ($q) => $q->where('type', 'equipment'))->count();
        $totalAttire    = Item::whereHas('category', fn ($q) => $q->where('type', 'attire'))->count();
        // Threshold matches the v_inventory_low_stock view and ReportController::inventoryReport().
        $lowStock       = Item::where('item_status', 'available')
            ->where('quantity', '>', 0)
            ->whereRaw('available_quantity <= quantity * 0.2')
            ->count();
        $totalItems     = Item::count();

        return view('inventory.index', compact(
            'items',
            'totalEquipment',
            'totalAttire',
            'lowStock',
            'totalItems'
        ));
    }

    public function create()
    {
        $categories = Category::orderBy('type')->get();
        return view('inventory.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'      => 'required',
            'item_name'        => 'required|string|max:100',
            'quantity'         => 'required|integer|min:1',
            'condition_status' => 'required|in:good,fair,poor',
            'item_status'      => 'required|in:available,unavailable',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->itemImageService->uploadAndCompress($request->file('image'));
        }

        Item::create([
            'category_id'        => $request->category_id,
            'item_name'          => $request->item_name,
            'item_description'   => $request->item_description,
            'image'              => $imagePath,
            'quantity'           => $request->quantity,
            'available_quantity' => $request->quantity,
            'condition_status'   => $request->condition_status,
            'item_status'        => $request->item_status,
            'date_added'         => now(),
        ]);

        return redirect()->route('inventory.index')
                         ->with('success', 'Item added successfully!');
    }

    public function edit($id)
    {
        $item       = Item::findOrFail($id);
        $categories = Category::orderBy('type')->get();
        return view('inventory.edit', compact('item', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id'        => 'required',
            'item_name'          => 'required|string|max:100',
            'quantity'           => 'required|integer|min:1',
            'available_quantity' => 'required|integer|min:0|lte:quantity',
            'condition_status'   => 'required|in:good,fair,poor',
            'item_status'        => 'required|in:available,unavailable',
            'image'              => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
        ]);

        $item = Item::findOrFail($id);

        $imagePath = $item->image;
        if ($request->hasFile('image')) {
            if ($item->image) {
                $this->itemImageService->deleteStoredFile($item->image);
            }
            $imagePath = $this->itemImageService->uploadAndCompress($request->file('image'));
        }

        $item->update([
            'category_id'        => $request->category_id,
            'item_name'          => $request->item_name,
            'item_description'   => $request->item_description,
            'image'              => $imagePath,
            'quantity'           => $request->quantity,
            'available_quantity' => $request->available_quantity,
            'condition_status'   => $request->condition_status,
            'item_status'        => $request->item_status,
        ]);

        return redirect()->route('inventory.index')
                         ->with('success', 'Item updated successfully!');
    }

    public function destroy($id)
    {
        $item = Item::findOrFail($id);

        $hasHistory = $item->borrowingDetails()->exists()
            || $item->returnRecords()->exists()
            || $item->maintenances()->exists();

        if ($hasHistory) {
            $item->update(['item_status' => 'unavailable']);

            return redirect()->route('inventory.index')
                             ->with('success', 'Item has borrowing/maintenance history, so it was marked Unavailable instead of deleted.');
        }

        if ($item->image) {
            $this->itemImageService->deleteStoredFile($item->image);
        }

        foreach ($item->images as $image) {
            $this->itemImageService->deleteStoredFile($image->image_path);
        }

        $item->delete();

        return redirect()->route('inventory.index')
                         ->with('success', 'Item deleted successfully!');
    }

    public function categories()
    {
        $categories = Category::withCount('items')->get();
        return view('inventory.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:100',
            'type'          => 'required|in:equipment,attire',
            'description'   => 'nullable|string',
        ]);

        Category::create([
            'category_name' => $request->category_name,
            'type'          => $request->type,
            'description'   => $request->description,
        ]);

        return redirect()->route('inventory.categories')
                         ->with('success', 'Category added successfully!');
    }

    public function destroyCategory($id)
    {
        $category = Category::findOrFail($id);

        if ($category->items()->count() > 0) {
            return redirect()->route('inventory.categories')
                             ->with('error', 'Cannot delete category — it has items attached!');
        }

        $category->delete();
        return redirect()->route('inventory.categories')
                         ->with('success', 'Category deleted successfully!');
    }
}
