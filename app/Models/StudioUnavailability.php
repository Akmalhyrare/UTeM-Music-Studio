<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudioUnavailability extends Model
{
    protected $table = 'studio_unavailability';
    protected $primaryKey = 'unavailability_id';

    protected $fillable = [
        'studio_id',
        'type',
        'start_at',
        'end_at',
        'reason',
        'staff_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    public function studio()
    {
        return $this->belongsTo(Studio::class, 'studio_id', 'studio_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    // Periods that have not yet ended
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('end_at', '>=', now());
    }

    // Periods for a studio that overlap the given datetime range
    public function scopeOverlapping(Builder $query, int $studioId, $start, $end): Builder
    {
        return $query->where('studio_id', $studioId)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start);
    }
}
