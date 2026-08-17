@extends('layouts.student')

@section('content')

@include('settings._tabs-style')

<style>
    .page-header {
        background-image: linear-gradient(rgba(33,28,54,0.75), rgba(33,28,54,0.75)), url("{{ asset('images/studio-control-room.jpg') }}");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: #fff; padding: 36px 40px;
    }
    .page-header h2 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
    .page-header p { color: rgba(255,255,255,0.6); font-size: 14px; }

    .page-body { max-width: 1000px; margin: 0 auto; padding: 32px 40px; }
</style>

<div class="page-header">
    <h2>⚙️ Account Settings</h2>
    <p>Manage your profile and security settings.</p>
</div>

<div class="page-body">

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

            <form method="POST" action="{{ route('student.settings.profile') }}">
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
                    <label class="form-label" style="font-size:13px;">Matric No.</label>
                    <input type="text"
                           class="form-control"
                           style="font-size:13px;background:#F3F4F6;"
                           value="{{ $user->matric_no }}"
                           disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label" style="font-size:13px;">Role</label>
                    <input type="text"
                           class="form-control"
                           style="font-size:13px;background:#F3F4F6;"
                           value="Student"
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

</div>

@endsection
