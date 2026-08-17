<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use HasFactory;

    protected $table = 'students';
    protected $primaryKey = 'student_id';

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone_no',
        'matric_no',
        'status',
        'last_login_at',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
    ];

    /** Columns checked when performing a text search on students. */
    public static function searchableColumns(): array
    {
        return ['full_name', 'email', 'matric_no', 'phone_no'];
    }

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

    /**
     * Whether this student is referenced by any historical/operational
     * records (borrowings, bookings). Used to decide between hard-delete
     * and archival — `borrowings`/`bookings` use ON DELETE RESTRICT, so a
     * hard delete would fail with a foreign key violation if any exist.
     */
    public function hasHistoricalRecords(): bool
    {
        return $this->borrowings()->exists()
            || $this->bookings()->exists();
    }
}