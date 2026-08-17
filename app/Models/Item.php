<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'category_id',
        'item_name',
        'item_description',
        'image',
        'quantity',
        'available_quantity',
        'condition_status',
        'item_status',
        'date_added',
    ];

    /** Columns checked when performing a text search on items. */
    public static function searchableColumns(): array
    {
        return [
            'item_name',
            'item_description',
        ];
    }

    // Item belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    // Item has many borrowing details
    public function borrowingDetails()
    {
        return $this->hasMany(BorrowingDetail::class, 'item_id', 'item_id');
    }

    // Item has many return records
    public function returnRecords()
    {
        return $this->hasMany(ReturnRecord::class, 'item_id', 'item_id');
    }

    // Item has many maintenances
    public function maintenances()
    {
        return $this->hasMany(Maintenance::class, 'item_id', 'item_id');
    }

    // Item has many gallery images
    public function images()
    {
        return $this->hasMany(ItemImage::class, 'item_id', 'item_id')->orderBy('position');
    }

    public function primaryImage()
    {
        return $this->hasOne(ItemImage::class, 'item_id', 'item_id')->where('is_primary', true);
    }

    /**
     * All images to show in the catalog gallery/modal. Falls back to the
     * legacy single `image` column when no item_images rows exist yet.
     *
     * @return \Illuminate\Support\Collection<int, ItemImage>
     */
    public function galleryImages()
    {
        if ($this->images->isNotEmpty()) {
            return $this->images;
        }

        if ($this->image) {
            return collect([new ItemImage(['image_path' => $this->image])]);
        }

        return collect();
    }

    /** Thumbnail for the catalog card: primary gallery image, else the legacy image. */
    public function cardThumbnailUrl(): ?string
    {
        $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->first();

        if ($primary) {
            return $primary->thumbnailUrl();
        }

        return $this->image ? self::resolveThumbnailUrl($this->image) : null;
    }

    /** URL of a compressed `_thumb` variant of a stored path, falling back to the full image. */
    public static function resolveThumbnailUrl(string $path): string
    {
        $thumbPath = preg_replace('/\.([^.]+)$/', '_thumb.$1', $path);

        if (Storage::disk('public')->exists($thumbPath)) {
            return asset('storage/' . $thumbPath);
        }

        return asset('storage/' . $path);
    }

    /** Availability label/CSS class derived from item_status and stock levels. */
    public function availabilityBadge(): array
    {
        if ($this->item_status === 'unavailable') {
            return ['label' => 'Under Maintenance', 'class' => 'badge-maintenance'];
        }

        if ($this->available_quantity <= 0) {
            return ['label' => 'Out of Stock', 'class' => 'badge-out'];
        }

        if ($this->available_quantity <= $this->quantity * 0.3) {
            return ['label' => 'Low Stock', 'class' => 'badge-low'];
        }

        return ['label' => 'Available', 'class' => 'badge-avail'];
    }
}