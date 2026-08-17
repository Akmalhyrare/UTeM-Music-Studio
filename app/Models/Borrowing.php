<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrowing extends Model
{
    protected $table = 'borrowings';
    protected $primaryKey = 'borrow_id';

    protected $fillable = [
        'student_id',
        'staff_id',
        'pickup_date',
        'return_date',
        'borrow_status',
        'purpose',
        'collected_at',
        'collected_by',
        'returned_at',
        'returned_by',
    ];

    protected $casts = [
        'pickup_date'  => 'date',
        'return_date'  => 'date',
        'collected_at' => 'datetime',
        'returned_at'  => 'datetime',
        'is_overdue'   => 'boolean',
    ];

    // Borrowing belongs to a student
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // Borrowing approved by staff
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    // Staff member who marked the items as collected
    public function collectedByStaff()
    {
        return $this->belongsTo(Staff::class, 'collected_by', 'staff_id');
    }

    // Staff member who processed the return
    public function returnedByStaff()
    {
        return $this->belongsTo(Staff::class, 'returned_by', 'staff_id');
    }

    // Borrowing has many details
    public function borrowingDetails()
    {
        return $this->hasMany(BorrowingDetail::class, 'borrow_id', 'borrow_id');
    }

    // Borrowing has many return records
    public function returnRecords()
    {
        return $this->hasMany(ReturnRecord::class, 'borrow_id', 'borrow_id');
    }

    // `is_overdue` is maintained by the trg_borrowings_set_overdue trigger
    // (on write) and refreshed daily by fn_refresh_overdue_borrowings()
    // via the scheduler — see routes/console.php.
}
