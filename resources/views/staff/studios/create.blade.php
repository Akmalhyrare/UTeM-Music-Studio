@extends('layouts.app')

@section('page-title', 'Add Studio')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('staff.studios.index') }}" class="btn btn-sm btn-outline-secondary">← Back</a>
    <h5 class="mb-0 fw-bold">Add New Studio</h5>
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

<div class="card border-0 shadow-sm" style="max-width:700px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('staff.studios.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Studio Name <span class="text-danger">*</span></label>
                <input type="text" name="studio_name" class="form-control" style="font-size:13px;"
                       placeholder="e.g. Music Studio A" value="{{ old('studio_name') }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Studio Type <span class="text-danger">*</span></label>
                <input type="text" name="studio_type" class="form-control" style="font-size:13px;"
                       placeholder="e.g. music, dance, gamelan" value="{{ old('studio_type') }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Description</label>
                <textarea name="description" class="form-control" style="font-size:13px;" rows="3"
                          placeholder="Short description shown to students">{{ old('description') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Capacity</label>
                <input type="number" name="capacity" class="form-control" style="font-size:13px; max-width:200px;"
                       placeholder="e.g. 10" min="1" value="{{ old('capacity') }}">
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Equipment Available</label>
                <textarea name="equipment" class="form-control" style="font-size:13px;" rows="3"
                          placeholder="One item per line, e.g.&#10;Drum kit&#10;PA system&#10;Microphones">{{ old('equipment') }}</textarea>
                <small class="text-muted">Enter one item per line.</small>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="font-size:13px;">Location</label>
                    <input type="text" name="location" class="form-control" style="font-size:13px;"
                           placeholder="e.g. Block A, Level 2" value="{{ old('location') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="font-size:13px;">Studio Size</label>
                    <input type="text" name="size" class="form-control" style="font-size:13px;"
                           placeholder="e.g. 30 sqm" value="{{ old('size') }}">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="font-size:13px;">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-control" style="font-size:13px;" required>
                    <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="blocked" {{ old('status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">
                    ✅ Save Studio
                </button>
                <a href="{{ route('staff.studios.index') }}" class="btn btn-outline-secondary" style="font-size:13px;border-radius:8px;">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

@endsection
