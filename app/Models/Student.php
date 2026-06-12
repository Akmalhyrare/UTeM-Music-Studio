<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    protected $table = 'students';
    protected $primaryKey = 'student_id';

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone_no',
        'matric_no',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    // Student requests borrowings
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'student_id', 'student_id');
    }

    // Student requests bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'student_id', 'student_id');
    }
}