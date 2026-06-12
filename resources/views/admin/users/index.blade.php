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
            <div class="p-2 rounded" style="background:#E1F5EE;">
                <span style="font-size:20px;">👑</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $staffList->where('is_admin', true)->count() }}</h4>
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
                <h4 class="mb-0 fw-bold">{{ $staffList->count() }}</h4>
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
                <h4 class="mb-0 fw-bold">{{ $studentList->count() }}</h4>
                <small class="text-muted">Student accounts</small>
            </div>
        </div>
    </div>
</div>

{{-- STAFF TABLE --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">👷 Staff Accounts</span>
        <a href="{{ route('users.staff.create') }}"
           class="btn btn-sm"
           style="background:#1D9E75;color:#fff;border-radius:8px;">
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
                            <span class="badge" style="background:#E1F5EE;color:#085041;">Admin</span>
                        @else
                            <span class="badge" style="background:#FAEEDA;color:#633806;">Staff</span>
                        @endif
                    </td>
                    <td>
                        @if($staff->status === 'active')
                            <span class="badge" style="background:#E1F5EE;color:#085041;">Active</span>
                        @else
                            <span class="badge" style="background:#FAECE7;color:#712B13;">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('users.staff.edit', $staff->staff_id) }}"
                           class="btn btn-sm btn-outline-secondary">✏️ Edit</a>
                    @if($staff->staff_id != session('user_id'))
                        <form method="POST"
                            action="{{ route('users.staff.destroy', $staff->staff_id) }}"
                            style="display:inline;"
                            onsubmit="return confirm('Delete this staff account?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger">🗑️</button>
                        </form>
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
                        No staff accounts found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
                            <span class="badge" style="background:#E1F5EE;color:#085041;">Active</span>
                        @else
                            <span class="badge" style="background:#FAECE7;color:#712B13;">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('users.student.edit', $student->student_id) }}"
                           class="btn btn-sm btn-outline-secondary">✏️ Edit</a>
                        <form method="POST"
                              action="{{ route('users.student.destroy', $student->student_id) }}"
                              style="display:inline;"
                              onsubmit="return confirm('Delete this student account?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger">🗑️</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-3">
                        No student accounts found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection