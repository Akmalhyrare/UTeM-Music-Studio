<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\Staff;
use App\Models\Student;

class AuthController extends Controller
{
    // Show login page
    public function showLogin()
    {
        return view('auth.login');
    }

    // Handle login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Check staff table first
        $staff = Staff::where('email', $request->email)->first();
        if ($staff && Hash::check($request->password, $staff->password)) {
            Session::put('user_id',   $staff->staff_id);
            Session::put('user_name', $staff->full_name);
            Session::put('user_role', $staff->is_admin ? 'admin' : 'staff');
            Session::put('user_type', 'staff');
            Session::put('is_admin',  $staff->is_admin);
            return redirect()->route('staff.dashboard');
        }

        // Check student table
        $student = Student::where('email', $request->email)->first();
        if ($student && Hash::check($request->password, $student->password)) {
            Session::put('user_id',   $student->student_id);
            Session::put('user_name', $student->full_name);
            Session::put('user_role', 'student');
            Session::put('user_type', 'student');
            return redirect()->route('student.dashboard');
        }

        // If no match
        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ]);
    }

    // Logout
    public function logout()
    {
        Session::flush();
        return redirect()->route('login');
    }
    // Show register page
public function showRegister()
{
    return view('auth.register');
}

// Handle student registration
public function register(Request $request)
{
    $request->validate([
        'full_name' => 'required|string|max:100',
        'email'     => 'required|email|unique:students,email',
        'matric_no' => 'required|string|max:20|unique:students,matric_no',
        'phone_no'  => 'nullable|string|max:20',
        'password'  => 'required|min:6|confirmed',
    ]);

    Student::create([
        'full_name' => $request->full_name,
        'email'     => $request->email,
        'matric_no' => $request->matric_no,
        'phone_no'  => $request->phone_no,
        'password'  => Hash::make($request->password),
        'status'    => 'active',
    ]);

    return redirect()->route('login')
                     ->with('success', 'Account created! Please login.');
}
}