<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Staff;
use App\Models\Student;

class UserManagementController extends Controller
{
    public function index()
    {
        $staffList   = Staff::orderBy('staff_id', 'desc')->get();
        $studentList = Student::orderBy('student_id', 'desc')->get();

        return view('admin.users.index', compact('staffList', 'studentList'));
    }

    // ── STAFF ──────────────────────────────

    public function createStaff()
    {
        return view('admin.users.create-staff');
    }

    public function storeStaff(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:100',
            'email'     => 'required|email|unique:staff,email',
            'password'  => 'required|min:6',
            'phone_no'  => 'nullable|string|max:20',
            'position'  => 'nullable|string|max:50',
            'status'    => 'required',
        ]);

        Staff::create([
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'phone_no'  => $request->phone_no,
            'position'  => $request->position,
            'status'    => $request->status,
            'is_admin'  => $request->has('is_admin'),
        ]);

        return redirect()->route('users.index')
                         ->with('success', 'Staff account created successfully!');
    }

    public function editStaff($id)
    {
        $staff = Staff::findOrFail($id);
        return view('admin.users.edit-staff', compact('staff'));
    }

    public function updateStaff(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);

        $request->validate([
            'full_name' => 'required|string|max:100',
            'email'     => 'required|email|unique:staff,email,' . $id . ',staff_id',
            'phone_no'  => 'nullable|string|max:20',
            'position'  => 'nullable|string|max:50',
            'status'    => 'required',
        ]);

        $data = [
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'phone_no'  => $request->phone_no,
            'position'  => $request->position,
            'status'    => $request->status,
            'is_admin'  => $request->has('is_admin'),
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $staff->update($data);

        return redirect()->route('users.index')
                         ->with('success', 'Staff account updated successfully!');
    }

    public function destroyStaff($id)
    {
        $staff = Staff::findOrFail($id);

        // Prevent deleting yourself
        if ($staff->staff_id == session('user_id')) {
            return redirect()->route('users.index')
                             ->with('error', 'You cannot delete your own account!');
        }

        $staff->delete();
        return redirect()->route('users.index')
                         ->with('success', 'Staff account deleted successfully!');
    }

    // ── STUDENT ────────────────────────────

    public function createStudent()
    {
        return view('admin.users.create-student');
    }

    public function storeStudent(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:100',
            'email'     => 'required|email|unique:students,email',
            'password'  => 'required|min:6',
            'phone_no'  => 'nullable|string|max:20',
            'matric_no' => 'required|string|max:20|unique:students,matric_no',
            'status'    => 'required',
        ]);

        Student::create([
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'phone_no'  => $request->phone_no,
            'matric_no' => $request->matric_no,
            'status'    => $request->status,
        ]);

        return redirect()->route('users.index')
                         ->with('success', 'Student account created successfully!');
    }

    public function editStudent($id)
    {
        $student = Student::findOrFail($id);
        return view('admin.users.edit-student', compact('student'));
    }

    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'full_name' => 'required|string|max:100',
            'email'     => 'required|email|unique:students,email,' . $id . ',student_id',
            'phone_no'  => 'nullable|string|max:20',
            'matric_no' => 'required|string|max:20|unique:students,matric_no,' . $id . ',student_id',
            'status'    => 'required',
        ]);

        $data = [
            'full_name' => $request->full_name,
            'email'     => $request->email,
            'phone_no'  => $request->phone_no,
            'matric_no' => $request->matric_no,
            'status'    => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $student->update($data);

        return redirect()->route('users.index')
                         ->with('success', 'Student account updated successfully!');
    }

    public function destroyStudent($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return redirect()->route('users.index')
                         ->with('success', 'Student account deleted successfully!');
    }
}