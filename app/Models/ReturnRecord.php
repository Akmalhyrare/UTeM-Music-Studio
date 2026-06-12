<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRecord extends Model
{
    protected $table = 'return_records';
    protected $primaryKey = 'return_id';

    protected $fillable = [
        'borrow_id',
        'item_id',
        'staff_id',
        'return_date',
        'quantity_returned',
        'item_condition',
        'damage_note',
        'return_status',
        'remarks',
    ];

    // Return belongs to a borrowing
    public function borrowing()
    {
        return $this->belongsTo(Borrowing::class, 'borrow_id', 'borrow_id');
    }

    // Return belongs to an item
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    // Return processed by staff
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    // Return triggers maintenance
    public function maintenance()
    {
        return $this->hasOne(Maintenance::class, 'return_id', 'return_id');
    }
}