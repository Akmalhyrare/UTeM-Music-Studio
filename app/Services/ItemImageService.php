<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemImage;
use Illuminate\Support\Facades\Storage;

class ItemImageService
{
    /** Maximum number of gallery images allowed per item. */
    private const MAX_IMAGES = 10;

    /**
     * Upload and store gallery images for an item, enforcing the
     * per-item image limit. The very first image ever uploaded
     * automatically becomes the primary image.
     *
     * @param array<\Illuminate\Http\UploadedFile> $files
     */
    public function addImages(Item $item, array $files): void
    {
        $existingCount = $item->images()->count();
        $hasPrimary = $item->images()->where('is_primary', true)->exists();
        $position = (int) $item->images()->max('position');

        foreach ($files as $file) {
            if ($existingCount >= self::MAX_IMAGES) {
                break;
            }

            $position++;
            $existingCount++;

            ItemImage::create([
                'item_id'    => $item->item_id,
                'image_path' => $this->uploadAndCompress($file),
                'position'   => $position,
                'is_primary' => !$hasPrimary,
            ]);

            $hasPrimary = true;
        }
    }

    public function deleteImage(ItemImage $image): void
    {
        $itemId = $image->item_id;
        $wasPrimary = $image->is_primary;

        $this->deleteStoredFile($image->image_path);
        $image->delete();

        if ($wasPrimary) {
            $next = ItemImage::where('item_id', $itemId)->orderBy('position')->first();
            $next?->update(['is_primary' => true]);
        }
    }

    public function setPrimaryImage(ItemImage $image): void
    {
        ItemImage::where('item_id', $image->item_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);
    }

    /**
     * Persist a new ordering for an item's gallery images.
     *
     * @param array<int> $orderedImageIds
     */
    public function reorderImages(Item $item, array $orderedImageIds): void
    {
        foreach (array_values($orderedImageIds) as $index => $imageId) {
            ItemImage::where('item_id', $item->item_id)
                ->where('image_id', $imageId)
                ->update(['position' => $index + 1]);
        }
    }

    /** Delete both the full-size image and its `_thumb` companion, if present. */
    public function deleteStoredFile(string $path): void
    {
        Storage::disk('public')->delete($path);
        Storage::disk('public')->delete($this->thumbPath($path));
    }

    private function thumbPath(string $path): string
    {
        return preg_replace('/\.([^.]+)$/', '_thumb.$1', $path);
    }

    /**
     * Compress and store an uploaded image, writing both a full-size
     * (max 1600x1600, q82) and a `_thumb` (max 400x400, q75) variant
     * under storage/app/public/items. Returns the full-size path.
     */
    public function uploadAndCompress($file): string
    {
        $filename = time() . '_' . uniqid() . '.jpg';
        $folder   = storage_path('app/public/items');

        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        if (!extension_loaded('gd')) {
            return $file->store('items', 'public');
        }

        $imageInfo = getimagesize($file->getRealPath());
        $mime      = $imageInfo['mime'];

        $source = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file->getRealPath()),
            'image/png'  => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default      => null,
        };

        if (!$source) {
            return $file->store('items', 'public');
        }

        $hasAlpha = in_array($mime, ['image/png', 'image/webp'], true);

        $this->saveResized($source, $folder . '/' . $filename, 1600, 1600, 82, $hasAlpha);

        $thumbFilename = preg_replace('/\.([^.]+)$/', '_thumb.$1', $filename);
        $this->saveResized($source, $folder . '/' . $thumbFilename, 400, 400, 75, $hasAlpha);

        imagedestroy($source);

        return 'items/' . $filename;
    }

    private function saveResized($source, string $savePath, int $maxWidth, int $maxHeight, int $quality, bool $hasAlpha): void
    {
        $origWidth  = imagesx($source);
        $origHeight = imagesy($source);

        $ratio     = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1);
        $newWidth  = (int) ($origWidth * $ratio);
        $newHeight = (int) ($origHeight * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if ($hasAlpha) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        imagejpeg($resized, $savePath, $quality);
        imagedestroy($resized);
    }
}
