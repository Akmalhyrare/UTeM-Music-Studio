@extends('layouts.app')

@section('page-title', 'Add Staff Account')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">← Back</a>
    <h5 class="mb-0 fw-bold">Add New Staff Account</h5>
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
        <form method="POST" action="{{ route('users.staff.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Full Name <span class="text-danger">*</span></label>
                <input type="text"
                       name="full_name"
                       class="form-control"
                       style="font-size:13px;"
                       placeholder="Enter full name"
                       value="{{ old('full_name') }}"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Email Address <span class="text-danger">*</span></label>
                <input type="email"
                       name="email"
                       class="form-control"
                       style="font-size:13px;"
                       placeholder="Enter email address"
                       value="{{ old('email') }}"
                       pattern="[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}"
                       title="Please enter a valid email address with a domain extension (e.g. .com, .edu.my)."
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Phone No.</label>
                <input type="text"
                       name="phone_no"
                       class="form-control"
                       style="font-size:13px;"
                       placeholder="e.g. 0123456789"
                       value="{{ old('phone_no') }}">
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Position</label>
                <input type="text"
                       name="position"
                       class="form-control"
                       style="font-size:13px;"
                       placeholder="e.g. Studio Manager"
                       value="{{ old('position') }}">
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Password <span class="text-danger">*</span></label>
                <input type="password"
                       name="password"
                       class="form-control"
                       style="font-size:13px;"
                       placeholder="Minimum 8 characters"
                       required>
                <small class="text-muted" style="font-size:12px;">
                    Minimum 8 characters, including an uppercase letter, a lowercase letter, a number and a special character.
                </small>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-control" style="font-size:13px;" required>
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="checkbox"
                           name="is_admin"
                           class="form-check-input"
                           id="is_admin"
                           {{ old('is_admin') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_admin" style="font-size:13px;">
                        Grant Admin privileges
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit"
                        class="btn"
                        style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">
                    ✅ Save Staff Account
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