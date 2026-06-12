<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
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
}