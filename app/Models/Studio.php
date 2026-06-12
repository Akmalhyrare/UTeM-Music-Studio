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
        'status',
    ];

    // Studio has many bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'studio_id', 'studio_id');
    }
}