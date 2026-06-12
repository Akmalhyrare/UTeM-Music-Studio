@extends('layouts.app')

@section('page-title', 'Edit Staff Account')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">← Back</a>
    <h5 class="mb-0 fw-bold">Edit Staff Account</h5>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card border-0 shadow-sm" style="max-width:600px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('users.staff.update', $staff->staff_id) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Full Name <span class="text-danger">*</span></label>
                <input type="text"
                       name="full_name"
                       class="form-control"
                       style="font-size:13px;"
                       value="{{ old('full_name', $staff->full_name) }}"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Email Address <span class="text-danger">*</span></label>
                <input type="email"
                       name="email"
                       class="form-control"
                       style="font-size:13px;"
                       value="{{ old('email', $staff->email) }}"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Phone No.</label>
                <input type="text"
                       name="phone_no"
                       class="form-control"
                       style="font-size:13px;"
                       value="{{ old('phone_no', $staff->phone_no) }}">
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Position</label>
                <input type="text"
                       name="position"
                       class="form-control"
                       style="font-size:13px;"
                       value="{{ old('position', $staff->position) }}">
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">
                    New Password
                    <span class="text-muted">(leave blank to keep current)</span>
                </label>
                <input type="password"
                       name="password"
                       class="form-control"
                       style="font-size:13px;"
                       placeholder="Enter new password">
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-control" style="font-size:13px;" required>
                    <option value="active" {{ old('status', $staff->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $staff->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="checkbox"
                           name="is_admin"
                           class="form-check-input"
                           id="is_admin"
                           {{ old('is_admin', $staff->is_admin) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_admin" style="font-size:13px;">
                        Grant Admin privileges
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit"
                        class="btn"
                        style="background:#1D9E75;color:#fff;font-size:13px;border-radius:8px;">
                    ✅ Update Staff Account
                </button>
                <a href="{{ route('users.index') }}"
                   class="btn btn-outline-secondary"
                   style="font-size:13px;border-radius:8px;">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

@endsection