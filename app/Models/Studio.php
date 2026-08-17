<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Studio extends Model
{
    protected $table = 'studios';
    protected $primaryKey = 'studio_id';

    protected $fillable = [
        'studio_name',
        'studio_type',
        'description',
        'capacity',
        'equipment',
        'location',
        'size',
        'status',
    ];

    // Studio has many bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'studio_id', 'studio_id');
    }

    // Studio has many gallery images, ordered for display
    public function images()
    {
        return $this->hasMany(StudioImage::class, 'studio_id', 'studio_id')->orderBy('position');
    }

    // The featured/primary gallery image
    public function primaryImage()
    {
        return $this->hasOne(StudioImage::class, 'studio_id', 'studio_id')->where('is_primary', true);
    }

    // Scheduled maintenance / blocked periods
    public function unavailabilities()
    {
        return $this->hasMany(StudioUnavailability::class, 'studio_id', 'studio_id');
    }

    // Equipment list, one item per line
    public function equipmentList(): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", $this->equipment ?? ''))));
    }
}