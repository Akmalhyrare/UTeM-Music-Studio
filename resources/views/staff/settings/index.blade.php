@extends('layouts.app')

@section('page-title', 'Account Settings')

@section('content')

@include('settings._tabs-style')

<div class="mb-4">
    <h5 class="mb-0 fw-bold">⚙️ Account Settings</h5>
    <p class="text-muted mb-0" style="font-size:13px;">Manage your profile and security settings.</p>
</div>

@if (session('success'))
<div class="alert alert-success">
    ✅ {{ session('success') }}
</div>
@endif

@if (session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="d-flex gap-2 mb-3" id="settings-tabs">
    <button type="button" class="settings-tab active" data-tab="profile">👤 Profile</button>
    <button type="button" class="settings-tab" data-tab="security">🔒 Security</button>
</div>

<div id="tab-profile" class="settings-tab-content" style="display:block;">
    <div class="card border-0 shadow-sm" style="max-width:600px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3">👤 Profile Information</h6>

            <form method="POST" action="{{ route('staff.settings.profile') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;">Full Name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="full_name"
                           class="form-control"
                           style="font-size:13px;"
                           value="{{ old('full_name', $user->full_name) }}"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;">Email Address <span class="text-danger">*</span></label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           style="font-size:13px;"
                           value="{{ old('email', $user->email) }}"
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
                           value="{{ old('phone_no', $user->phone_no) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;">Role</label>
                    <input type="text"
                           class="form-control"
                           style="font-size:13px;background:#F3F4F6;"
                           value="{{ $user->is_admin ? 'Administrator' : ($user->position ?: 'Staff') }}"
                           disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label" style="font-size:13px;">Account Status</label>
                    <input type="text"
                           class="form-control"
                           style="font-size:13px;background:#F3F4F6;"
                           value="{{ ucfirst($user->status) }}"
                           disabled>
                </div>

                <button type="submit"
                        class="btn"
                        style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">
                    💾 Save Profile
                </button>
            </form>
        </div>
    </div>
</div>

@include('settings._security-tab')

@include('settings._tabs-script')

@endsection
