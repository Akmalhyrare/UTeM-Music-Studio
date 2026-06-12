<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'student_id',
        'staff_id',
        'studio_id',
        'booking_date',
        'start_time',
        'end_time',
        'booking_status',
        'purpose',
    ];

    // Booking belongs to a student
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // Booking approved by staff
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    // Booking belongs to a studio
    public function studio()
    {
        return $this->belongsTo(Studio::class, 'studio_id', 'studio_id');
    }
}