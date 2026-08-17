<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemImagesRequest;
use App\Models\Item;
use App\Models\ItemImage;
use App\Services\ItemImageService;
use Illuminate\Http\Request;

class ItemImageController extends Controller
{
    public function __construct(private ItemImageService $itemImageService)
    {
    }

    public function store(StoreItemImagesRequest $request, $itemId)
    {
        $item = Item::findOrFail($itemId);

        $existing = $item->images()->count();
        $incoming = count($request->file('images', []));

        if ($existing + $incoming > 10) {
            return back()->with('error', 'An item can have a maximum of 10 images. '
                . "This item already has {$existing}.");
        }

        $this->itemImageService->addImages($item, $request->file('images'));

        return redirect()->route('inventory.edit', $item->item_id)
                          ->with('success', 'Images uploaded successfully!');
    }

    // Admin-only
    public function destroy($itemId, $imageId)
    {
        $image = ItemImage::where('item_id', $itemId)->findOrFail($imageId);

        $this->itemImageService->deleteImage($image);

        return redirect()->route('inventory.edit', $itemId)
                          ->with('success', 'Image deleted.');
    }

    public function setPrimary($itemId, $imageId)
    {
        $image = ItemImage::where('item_id', $itemId)->findOrFail($imageId);

        $this->itemImageService->setPrimaryImage($image);

        return redirect()->route('inventory.edit', $itemId)
                          ->with('success', 'Primary image updated.');
    }

    // AJAX: persist a new image order
    public function reorder(Request $request, $itemId)
    {
        $request->validate([
            'image_ids'   => 'required|array',
            'image_ids.*' => 'integer',
        ]);

        $item = Item::findOrFail($itemId);

        $this->itemImageService->reorderImages($item, $request->input('image_ids'));

        return response()->json(['success' => true]);
    }
}
