<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Borrowing;
use App\Models\Booking;

class StaffController extends Controller
{
    public function dashboard()
    {
        // Check if staff is logged in
        if (session('user_type') !== 'staff') {
            return redirect()->route('login');
        }

        $totalEquipment  = Item::whereHas('category', fn($q) => $q->where('type', 'equipment'))->count();
        $totalAttire     = Item::whereHas('category', fn($q) => $q->where('type', 'attire'))->count();
        $pendingBorrows  = Borrowing::where('borrow_status', 'pending')->count();
        $todayBookings   = Booking::where('booking_date', today())->count();
        $recentBorrows   = Borrowing::with('student')->where('borrow_status', 'pending')->latest()->take(5)->get();
        $todayBookingList = Booking::with('studio')->where('booking_date', today())->get();

        return view('staff.dashboard', compact(
            'totalEquipment',
            'totalAttire',
            'pendingBorrows',
            'todayBookings',
            'recentBorrows',
            'todayBookingList'
        ));
    }
}