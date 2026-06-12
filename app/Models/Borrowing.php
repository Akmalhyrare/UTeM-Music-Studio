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
        'borrow_date',
        'due_date',
        'borrow_status',
        'purpose',
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
}