<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Staff;
use App\Models\Student;
use App\Rules\StrongPassword;
use App\Rules\ValidEmailDomain;
use App\Support\Search;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $staffQuery   = Staff::query();
        $studentQuery = Student::query();

        Search::apply($staffQuery, $request->search, Staff::searchableColumns());
        Search::apply($studentQuery, $request->search, Student::searchableColumns());

        $adminCount   = (clone $staffQuery)->where('is_admin', true)->count();
        $staffTotal   = (clone $staffQuery)->count();
        $studentTotal = (clone $studentQuery)->count();

        $staffList   = $staffQuery->orderBy('staff_id', 'desc')->paginate(15, ['*'], 'staffPage')->withQueryString();
        $studentList = $studentQuery->orderBy('student_id', 'desc')->paginate(15, ['*'], 'studentPage')->withQueryString();

        return view('admin.users.index', compact('staffList', 'studentList', 'adminCount', 'staffTotal', 'studentTotal'));
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
            'email'     => ['required', 'email', new ValidEmailDomain(), 'unique:staff,email'],
            'password'  => ['required', new StrongPassword()],
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
            'email'     => ['required', 'email', new ValidEmailDomain(), 'unique:staff,email,' . $id . ',staff_id'],
            'password'  => ['nullable', new StrongPassword()],
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
        $staff = Staff::find($id);

        if (! $staff) {
            return redirect()->route('users.index')
                             ->with('error', 'Staff account not found.');
        }

        // A staff member (including an admin) can never remove their own account
        if ($staff->staff_id == session('user_id')) {
            return redirect()->route('users.index')
                             ->with('error', 'You cannot delete your own account.');
        }

        // Already archived — nothing further to do from this action
        if ($staff->status === 'inactive') {
            return redirect()->route('users.index')
                             ->with('error', 'This staff account is already archived.');
        }

        // Staff referenced by approvals/collections/returns/maintenance/bookings
        // cannot be physically deleted without destroying audit history —
        // archive (deactivate) the account instead.
        if ($staff->hasHistoricalRecords()) {
            $staff->update(['status' => 'inactive']);

            return redirect()->route('users.index')
                             ->with('success', 'Staff account has related records and was archived (deactivated) instead of deleted. Their history remains intact for reports and audits.');
        }

        try {
            $staff->delete();
        } catch (QueryException $e) {
            // Defense in depth: if the database still rejects the delete
            // because of a foreign key, fall back to archival.
            $staff->update(['status' => 'inactive']);

            return redirect()->route('users.index')
                             ->with('success', 'Staff account has related records and was archived (deactivated) instead of deleted.');
        }

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
            'email'     => ['required', 'email', new ValidEmailDomain(), 'unique:students,email'],
            'password'  => ['required', new StrongPassword()],
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
            'email'     => ['required', 'email', new ValidEmailDomain(), 'unique:students,email,' . $id . ',student_id'],
            'password'  => ['nullable', new StrongPassword()],
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
        $student = Student::find($id);

        if (! $student) {
            return redirect()->route('users.index')
                             ->with('error', 'Student account not found.');
        }

        // Already archived — nothing further to do from this action
        if ($student->status === 'inactive') {
            return redirect()->route('users.index')
                             ->with('error', 'This student account is already archived.');
        }

        // Students with borrowings/bookings cannot be physically deleted —
        // those tables use ON DELETE RESTRICT to protect historical/report
        // data. Archive (deactivate) the account instead.
        if ($student->hasHistoricalRecords()) {
            $student->update(['status' => 'inactive']);

            return redirect()->route('users.index')
                             ->with('success', 'Student account has related bookings/borrowings and was archived (deactivated) instead of deleted. Their history remains intact for reports and audits.');
        }

        try {
            $student->delete();
        } catch (QueryException $e) {
            // Defense in depth: if the database still rejects the delete
            // because of a foreign key, fall back to archival.
            $student->update(['status' => 'inactive']);

            return redirect()->route('users.index')
                             ->with('success', 'Student account has related records and was archived (deactivated) instead of deleted.');
        }

        return redirect()->route('users.index')
                         ->with('success', 'Student account deleted successfully!');
    }
}