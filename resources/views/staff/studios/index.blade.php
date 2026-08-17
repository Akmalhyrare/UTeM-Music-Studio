@extends('layouts.app')

@section('page-title', 'Studio Management')

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

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">🎚️ Studios</span>
        <a href="{{ route('staff.studios.create') }}"
           class="btn btn-sm"
           style="background:#7C3AED;color:#fff;border-radius:8px; font-size:13px;">
            + Add Studio
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">#</th>
                    <th style="font-size:12px;">Image</th>
                    <th style="font-size:12px;">Studio Name</th>
                    <th style="font-size:12px;">Type</th>
                    <th style="font-size:12px;">Capacity</th>
                    <th style="font-size:12px;">Bookings</th>
                    <th style="font-size:12px;">Status</th>
                    <th style="font-size:12px;">Actions</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse ($studios as $studio)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                    @if($studio->primaryImage)
                        <img src="{{ asset('storage/' . $studio->primaryImage->image_path) }}"
                             alt="{{ $studio->studio_name }}"
                             style="width:55px; height:55px; object-fit:cover; border-radius:8px; border:1px solid #eee;">
                    @else
                        <div style="width:55px; height:55px; background:#f0f0f0; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:22px;">
                            🎚️
                        </div>
                    @endif
                    </td>
                    <td>
                        <strong>{{ $studio->studio_name }}</strong>
                        @if($studio->description)
                            <br><small class="text-muted">{{ Str::limit($studio->description, 40) }}</small>
                        @endif
                    </td>
                    <td>{{ $studio->studio_type }}</td>
                    <td>{{ $studio->capacity ?? '-' }}</td>
                    <td>{{ $studio->bookings_count }}</td>
                    <td>
                        @if($studio->status == 'available')
                            <span class="badge" style="background:#D1FAE5;color:#065F46;">Available</span>
                        @elseif($studio->status == 'maintenance')
                            <span class="badge" style="background:#FFEDD5;color:#9A3412;">Maintenance</span>
                        @elseif($studio->status == 'blocked')
                            <span class="badge" style="background:#F3F4F6;color:#4B5563;">Blocked</span>
                        @else
                            <span class="badge" style="background:#E5E7EB;color:#374151;">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('staff.studios.edit', $studio->studio_id) }}"
                           class="btn btn-sm btn-outline-secondary">✏️ Manage</a>
                        <a href="{{ route('staff.studios.calendar', $studio->studio_id) }}"
                           class="btn btn-sm btn-outline-secondary">📅 Calendar</a>
                        @if(session('is_admin') && $studio->status !== 'inactive')
                        <form method="POST"
                              action="{{ route('staff.studios.archive', $studio->studio_id) }}"
                              style="display:inline;"
                              onsubmit="return confirm('Archive this studio? It will be hidden from public booking.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">🗄️ Archive</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No studios found. <a href="{{ route('staff.studios.create') }}">Add your first studio</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
