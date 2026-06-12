<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $table = 'maintenances';
    protected $primaryKey = 'maintenance_id';

    protected $fillable = [
        'item_id',
        'staff_id',
        'return_id',
        'report_date',
        'issue_type',
        'description',
        'maintenance_status',
    ];

    // Maintenance belongs to an item
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    // Maintenance handled by staff
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    // Maintenance triggered by return
    public function returnRecord()
    {
        return $this->belongsTo(ReturnRecord::class, 'return_id', 'return_id');
    }
}