@extends('layouts.app')

@section('page-title', 'User Management')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    ✅ {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    ❌ {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#F3E8FF;">
                <span style="font-size:20px;">👑</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $adminCount }}</h4>
                <small class="text-muted">Admins</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FAEEDA;">
                <span style="font-size:20px;">👷</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $staffTotal }}</h4>
                <small class="text-muted">Staff accounts</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#E6F1FB;">
                <span style="font-size:20px;">🎓</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $studentTotal }}</h4>
                <small class="text-muted">Student accounts</small>
            </div>
        </div>
    </div>
</div>

{{-- SEARCH --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('users.index') }}" data-instant-search>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <input type="text"
                       name="search"
                       class="form-control"
                       style="font-size:13px; max-width:300px;"
                       placeholder="Search by name, email, matric no., position, phone..."
                       value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm" style="background:#7C3AED;color:#fff;border-radius:8px;">
                    🔍 Search
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

{{-- STAFF TABLE --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">👷 Staff Accounts</span>
        <a href="{{ route('users.staff.create') }}"
           class="btn btn-sm"
           style="background:#7C3AED;color:#fff;border-radius:8px;">
            + Add Staff
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">#</th>
                    <th style="font-size:12px;">Name</th>
                    <th style="font-size:12px;">Email</th>
                    <th style="font-size:12px;">Position</th>
                    <th style="font-size:12px;">Role</th>
                    <th style="font-size:12px;">Status</th>
                    <th style="font-size:12px;">Actions</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse ($staffList as $staff)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $staff->full_name }}</td>
                    <td>{{ $staff->email }}</td>
                    <td>{{ $staff->position ?? '-' }}</td>
                    <td>
                        @if($staff->is_admin)
                            <span class="badge" style="background:#F3E8FF;color:#5B21B6;">Admin</span>
                        @else
                            <span class="badge" style="background:#FAEEDA;color:#633806;">Staff</span>
                        @endif
                    </td>
                    <td>
                        @if($staff->status === 'active')
                            <span class="badge" style="background:#D1FAE5;color:#065F46;">Active</span>
                        @else
                            <span class="badge" style="background:#E5E7EB;color:#374151;">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('users.staff.edit', $staff->staff_id) }}"
                           class="btn btn-sm btn-outline-secondary">✏️ Edit</a>
                    @if($staff->staff_id != session('user_id'))
                        @if($staff->status === 'inactive')
                            <span class="badge" style="background:#E5E7EB;color:#374151;" title="Reactivate via Edit by setting status to Active">
                                📦 Archived
                            </span>
                        @elseif($staff->hasHistoricalRecords())
                            <form method="POST"
                                action="{{ route('users.staff.destroy', $staff->staff_id) }}"
                                style="display:inline;"
                                onsubmit="return confirm('This staff account has related records (approvals, bookings, maintenance, etc.). It will be archived (deactivated) instead of deleted so history is preserved. Continue?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-warning"
                                        title="Archive User">📦 Archive User</button>
                            </form>
                        @else
                            <form method="POST"
                                action="{{ route('users.staff.destroy', $staff->staff_id) }}"
                                style="display:inline;"
                                onsubmit="return confirm('Delete this staff account? This action is permanent.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete">🗑️</button>
                            </form>
                        @endif
                    @else
                        <button class="btn btn-sm btn-outline-secondary"
                                disabled
                                title="You cannot delete your own account">
                            🔒
                        </button>
                    @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-3">
                        @if(request('search'))
                            No staff accounts match "{{ request('search') }}"
                        @else
                            No staff accounts found
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($staffList->hasPages())
        <div class="card-footer bg-white">
            {{ $staffList->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

{{-- STUDENT TABLE --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">🎓 Student Accounts</span>
        <span class="badge"
              style="background:#E6F1FB;color:#0C447C;padding:6px 12px;border-radius:8px;font-size:12px;">
            Students register themselves
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">#</th>
                    <th style="font-size:12px;">Name</th>
                    <th style="font-size:12px;">Email</th>
                    <th style="font-size:12px;">Matric No.</th>
                    <th style="font-size:12px;">Phone</th>
                    <th style="font-size:12px;">Status</th>
                    <th style="font-size:12px;">Actions</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse ($studentList as $student)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $student->full_name }}</td>
                    <td>{{ $student->email }}</td>
                    <td>{{ $student->matric_no }}</td>
                    <td>{{ $student->phone_no ?? '-' }}</td>
                    <td>
                        @if($student->status === 'active')
                            <span class="badge" style="background:#D1FAE5;color:#065F46;">Active</span>
                        @else
                            <span class="badge" style="background:#E5E7EB;color:#374151;">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('users.student.edit', $student->student_id) }}"
                           class="btn btn-sm btn-outline-secondary">✏️ Edit</a>
                        @if($student->status === 'inactive')
                            <span class="badge" style="background:#E5E7EB;color:#374151;" title="Reactivate via Edit by setting status to Active">
                                📦 Archived
                            </span>
                        @elseif($student->hasHistoricalRecords())
                            <form method="POST"
                                  action="{{ route('users.student.destroy', $student->student_id) }}"
                                  style="display:inline;"
                                  onsubmit="return confirm('This student account has related bookings/borrowings. It will be archived (deactivated) instead of deleted so history is preserved. Continue?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-warning"
                                        title="Archive User">📦 Archive User</button>
                            </form>
                        @else
                            <form method="POST"
                                  action="{{ route('users.student.destroy', $student->student_id) }}"
                                  style="display:inline;"
                                  onsubmit="return confirm('Delete this student account? This action is permanent.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete">🗑️</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-3">
                        @if(request('search'))
                            No student accounts match "{{ request('search') }}"
                        @else
                            No student accounts found
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($studentList->hasPages())
        <div class="card-footer bg-white">
            {{ $studentList->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@endsection