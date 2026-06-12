<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Staff extends Authenticatable
{
    protected $table = 'staff';
    protected $primaryKey = 'staff_id';

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone_no',
        'position',
        'status',
        'is_admin',
    ];

    protected $hidden = [
        'password',
    ];

    // Staff approves borrowings
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'staff_id', 'staff_id');
    }

    // Staff processes return records
    public function returnRecords()
    {
        return $this->hasMany(ReturnRecord::class, 'staff_id', 'staff_id');
    }

    // Staff handles maintenance
    public function maintenances()
    {
        return $this->hasMany(Maintenance::class, 'staff_id', 'staff_id');
    }

    // Staff approves bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'staff_id', 'staff_id');
    }
}