<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Staff extends Authenticatable
{
    use HasFactory;

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
        'last_login_at',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
    ];

    /** Columns checked when performing a text search on staff. */
    public static function searchableColumns(): array
    {
        return ['full_name', 'email', 'position', 'phone_no'];
    }

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

    // Staff handed items over to students (borrowing collection)
    public function collectedBorrowings()
    {
        return $this->hasMany(Borrowing::class, 'collected_by', 'staff_id');
    }

    // Staff processed item returns (borrowing return)
    public function returnedBorrowings()
    {
        return $this->hasMany(Borrowing::class, 'returned_by', 'staff_id');
    }

    // Staff created studio unavailability periods
    public function studioUnavailabilities()
    {
        return $this->hasMany(StudioUnavailability::class, 'staff_id', 'staff_id');
    }

    /**
     * Whether this staff member is referenced by any historical/operational
     * records (approvals, collections, returns, maintenance, bookings, etc.).
     * Used to decide between hard-delete and archival.
     */
    public function hasHistoricalRecords(): bool
    {
        return $this->borrowings()->exists()
            || $this->collectedBorrowings()->exists()
            || $this->returnedBorrowings()->exists()
            || $this->returnRecords()->exists()
            || $this->maintenances()->exists()
            || $this->bookings()->exists()
            || $this->studioUnavailabilities()->exists();
    }
}