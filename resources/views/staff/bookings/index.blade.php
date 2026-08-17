@extends('layouts.app')

@section('page-title', 'Booking Management')

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

{{-- STAT CARDS --}}
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#F3E8FF;">
                <span style="font-size:20px;">✅</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $confirmedCount }}</h4>
                <small class="text-muted">Confirmed bookings</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FAEEDA;">
                <span style="font-size:20px;">📆</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $todayCount }}</h4>
                <small class="text-muted">Today's sessions</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#E6F1FB;">
                <span style="font-size:20px;">📅</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $bookings->total() }}</h4>
                <small class="text-muted">Matching bookings</small>
            </div>
        </div>
    </div>
</div>

{{-- FILTERS --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('staff.bookings.index') }}" data-instant-search>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <input type="text"
                       name="search"
                       class="form-control"
                       style="font-size:13px; max-width:220px;"
                       placeholder="Search student, studio, purpose..."
                       value="{{ request('search') }}">
                <select name="status" class="form-control" style="font-size:13px; max-width:160px;">
                    <option value="">All status</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                <select name="studio_id" class="form-control" style="font-size:13px; max-width:200px;">
                    <option value="">All studios</option>
                    @foreach($studios as $studio)
                        <option value="{{ $studio->studio_id }}" {{ request('studio_id') == $studio->studio_id ? 'selected' : '' }}>
                            {{ $studio->studio_name }}
                        </option>
                    @endforeach
                </select>
                <input type="date" name="date" class="form-control" style="font-size:13px; max-width:160px;" value="{{ request('date') }}">
                <button type="submit" class="btn btn-sm" style="background:#7C3AED;color:#fff;border-radius:8px;">
                    🔍 Filter
                </button>
                <a href="{{ route('staff.bookings.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

{{-- BOOKINGS TABLE --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">📅 Studio Bookings</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">#</th>
                    <th style="font-size:12px;">Student</th>
                    <th style="font-size:12px;">Studio</th>
                    <th style="font-size:12px;">Date</th>
                    <th style="font-size:12px;">Time</th>
                    <th style="font-size:12px;">Purpose</th>
                    <th style="font-size:12px;">Status</th>
                    <th style="font-size:12px;">Actions</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse ($bookings as $booking)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $booking->student->full_name ?? '-' }}</td>
                    <td>{{ $booking->studio->studio_name ?? '-' }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d M Y') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('H:i') }} – {{ \Illuminate\Support\Carbon::parse($booking->end_time)->format('H:i') }}</td>
                    <td>{{ Str::limit($booking->purpose ?? '-', 30) }}</td>
                    <td>
                        <x-status-badge :status="$booking->booking_status" />
                    </td>
                    <td>
                        <a href="{{ route('staff.bookings.show', $booking->booking_id) }}"
                           class="btn btn-sm btn-outline-secondary">View</a>
                        <form method="POST"
                              action="{{ route('staff.bookings.destroy', $booking->booking_id) }}"
                              style="display:inline;"
                              onsubmit="return confirm('Delete this booking permanently?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        @if(request('search') || request('status') || request('studio_id') || request('date'))
                            No bookings match your search/filters.
                        @else
                            No bookings found.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bookings->hasPages())
        <div class="card-footer bg-white">
            {{ $bookings->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@endsection
