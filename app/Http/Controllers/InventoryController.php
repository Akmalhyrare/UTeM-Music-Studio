<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use App\Models\Category;

class InventoryController extends Controller
{
    // Check if staff is logged in
    private function checkStaff()
    {
        if (session('user_type') !== 'staff') {
            return redirect()->route('login');
        }
        return null;
    }

    // Show all items
    public function index(Request $request)
    {
        if (session('user_type') !== 'staff') {
            return redirect()->route('login');
        }

        $query = Item::with('category');

        // Search
        if ($request->search) {
            $query->where('item_name', 'like', '%' . $request->search . '%');
        }

        // Filter by category type
        if ($request->type) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('type', $request->type);
            });
        }

        // Filter by status
        if ($request->status) {
            $query->where('item_status', $request->status);
        }

        $items         = $query->orderBy('item_id', 'desc')->get();
        $totalEquipment = Item::whereHas('category', fn($q) => $q->where('type', 'equipment'))->count();
        $totalAttire   = Item::whereHas('category', fn($q) => $q->where('type', 'attire'))->count();
        $lowStock      = Item::whereRaw('available_quantity <= quantity * 0.3')->count();
        $totalItems    = Item::count();

        return view('inventory.index', compact(
            'items',
            'totalEquipment',
            'totalAttire',
            'lowStock',
            'totalItems'
        ));
    }

    // Show create form
    public function create()
    {
        if (session('user_type') !== 'staff') {
            return redirect()->route('login');
        }

        $categories = Category::orderBy('type')->get();
        return view('inventory.create', compact('categories'));
    }

    // Store new item
    public function store(Request $request)
    {
        $request->validate([
            'category_id'      => 'required',
            'item_name'        => 'required|string|max:100',
            'quantity'         => 'required|integer|min:1',
            'condition_status' => 'required',
            'item_status'      => 'required',
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

    // Show edit form
    public function edit($id)
    {
        if (session('user_type') !== 'staff') {
            return redirect()->route('login');
        }

        $item       = Item::findOrFail($id);
        $categories = Category::orderBy('type')->get();
        return view('inventory.edit', compact('item', 'categories'));
    }

    // Update item
    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id'      => 'required',
            'item_name'        => 'required|string|max:100',
            'quantity'         => 'required|integer|min:1',
            'condition_status' => 'required',
            'item_status'      => 'required',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
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
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',            
            'quantity'           => $request->quantity,
            'available_quantity' => $request->available_quantity ?? $item->available_quantity,
            'condition_status'   => $request->condition_status,
            'item_status'        => $request->item_status,
        ]);

        return redirect()->route('inventory.index')
                        ->with('success', 'Item updated successfully!');
    }

    // Delete item
    public function destroy($id)
    {
        $item = Item::findOrFail($id);
        $item->delete();

        return redirect()->route('inventory.index')
                         ->with('success', 'Item deleted successfully!');
    }

    // Show categories page
    public function categories()
    {
        if (session('user_type') !== 'staff') {
            return redirect()->route('login');
        }

        $categories = Category::withCount('items')->get();
        return view('inventory.categories', compact('categories'));
    }

    // Store new category
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

    // Delete category
    public function destroyCategory($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has items
        if ($category->items()->count() > 0) {
            return redirect()->route('inventory.categories')
                             ->with('error', 'Cannot delete category — it has items attached!');
        }

        $category->delete();
        return redirect()->route('inventory.categories')
                         ->with('success', 'Category deleted successfully!');
    }

    // Compress and save image
    private function uploadAndCompress($file)
    {
        $filename = time() . '_' . uniqid() . '.jpg';
        $folder   = storage_path('app/public/items');

        // Make sure folder exists
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        // Try GD compression if available
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

            if ($origWidth > $maxWidth) {
                $ratio     = $maxWidth / $origWidth;
                $newWidth  = $maxWidth;
                $newHeight = (int)($origHeight * $ratio);
            } else {
                $newWidth  = $origWidth;
                $newHeight = $origHeight;
            }

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

        // GD not available — just store original file
        return $file->store('items', 'public');
    }
}