<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ItemImage extends Model
{
    protected $table = 'item_images';
    protected $primaryKey = 'image_id';

    protected $fillable = [
        'item_id',
        'image_path',
        'position',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    /** URL of the compressed thumbnail, falling back to the full image if no thumbnail exists. */
    public function thumbnailUrl(): string
    {
        return Item::resolveThumbnailUrl($this->image_path);
    }

    public function fullUrl(): string
    {
        return asset('storage/' . $this->image_path);
    }
}
