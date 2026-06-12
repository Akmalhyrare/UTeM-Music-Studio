<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use App\Models\Category;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with('category');

        if ($request->search) {
            $query->where('item_name', 'like', '%' . $request->search . '%');
        }

        if ($request->type) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
        }

        if ($request->status) {
            $query->where('item_status', $request->status);
        }

        $items          = $query->orderBy('item_id', 'desc')->get();
        $totalEquipment = Item::whereHas('category', fn ($q) => $q->where('type', 'equipment'))->count();
        $totalAttire    = Item::whereHas('category', fn ($q) => $q->where('type', 'attire'))->count();
        $lowStock       = Item::whereRaw('available_quantity <= quantity * 0.3')->count();
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
            $imagePath = $this->uploadAndCompress($request->file('image'));
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
                Storage::disk('public')->delete($item->image);
            }
            $imagePath = $this->uploadAndCompress($request->file('image'));
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

        if ($item->image) {
            Storage::disk('public')->delete($item->image);
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

    private function uploadAndCompress($file)
    {
        $filename = time() . '_' . uniqid() . '.jpg';
        $folder   = storage_path('app/public/items');

        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        if (extension_loaded('gd')) {
            $savePath  = $folder . '/' . $filename;
            $imageInfo = getimagesize($file->getRealPath());
            $mime      = $imageInfo['mime'];

            if ($mime === 'image/jpeg') {
                $source = imagecreatefromjpeg($file->getRealPath());
            } elseif ($mime === 'image/png') {
                $source = imagecreatefrompng($file->getRealPath());
            } else {
                return $file->store('items', 'public');
            }

            $origWidth  = imagesx($source);
            $origHeight = imagesy($source);
            $maxWidth   = 800;
            $maxHeight  = 800;

            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1);
            $newWidth  = (int) ($origWidth  * $ratio);
            $newHeight = (int) ($origHeight * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if ($mime === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagejpeg($resized, $savePath, 75);
            imagedestroy($source);
            imagedestroy($resized);

            return 'items/' . $filename;
        }

        return $file->store('items', 'public');
    }
}
