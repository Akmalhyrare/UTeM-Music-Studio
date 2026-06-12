<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowingDetail extends Model
{
    protected $table = 'borrowing_details';
    protected $primaryKey = 'borrow_detail_id';

    protected $fillable = [
        'borrow_id',
        'item_id',
        'quantity_borrowed',
    ];

    // Detail belongs to a borrowing
    public function borrowing()
    {
        return $this->belongsTo(Borrowing::class, 'borrow_id', 'borrow_id');
    }

    // Detail belongs to an item
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }
}