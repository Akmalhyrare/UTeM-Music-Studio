<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioImage extends Model
{
    protected $table = 'studio_images';
    protected $primaryKey = 'image_id';

    protected $fillable = [
        'studio_id',
        'image_path',
        'position',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function studio()
    {
        return $this->belongsTo(Studio::class, 'studio_id', 'studio_id');
    }
}
