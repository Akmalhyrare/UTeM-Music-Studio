<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Borrowing;
use App\Models\Booking;
use App\Models\Item;

class StudentController extends Controller
{
    public function dashboard()
    {
        if (session('user_type') !== 'student') {
            return redirect()->route('login');
        }

        $studentId      = session('user_id');
        $myBorrowings   = Borrowing::where('student_id', $studentId)->latest()->take(5)->get();
        $myBookings     = Booking::with('studio')->where('student_id', $studentId)->latest()->take(5)->get();
        $availableItems = Item::where('item_status', 'available')->count();

        return view('student.dashboard', compact(
            'myBorrowings',
            'myBookings',
            'availableItems'
        ));
    }
}