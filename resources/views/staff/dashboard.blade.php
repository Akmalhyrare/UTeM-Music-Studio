@extends('layouts.app')

@section('page-title', 'Staff Dashboard')

@section('content')

@if (session('error'))
<div class="alert alert-danger" style="font-size:13px;">{{ session('error') }}</div>
@endif

<div style="display:grid; grid-template-columns: repeat(4,1fr); gap:16px; margin-bottom:24px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#F3E8FF;">
                <span style="font-size:20px;">📦</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $totalEquipment }}</h4>
                <small class="text-muted">Total equipment</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FAEEDA;">
                <span style="font-size:20px;">👔</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $totalAttire }}</h4>
                <small class="text-muted">Attire items</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FAECE7;">
                <span style="font-size:20px;">📋</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $pendingBorrows }}</h4>
                <small class="text-muted">Pending requests</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#E6F1FB;">
                <span style="font-size:20px;">📅</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $todayBookings }}</h4>
                <small class="text-muted">Bookings today</small>
            </div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(3,1fr); gap:16px; margin-bottom:24px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#D1FAE5;">
                <span style="font-size:20px;">📦</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $todaysPickups }}</h4>
                <small class="text-muted">Pickups today</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#DBEAFE;">
                <span style="font-size:20px;">↩️</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $todaysReturns }}</h4>
                <small class="text-muted">Returns due today</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FBE3E3;">
                <span style="font-size:20px;">⚠️</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $overdueReturns }}</h4>
                <small class="text-muted">Overdue returns</small>
            </div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <span style="font-weight:600;">📋 Pending Borrow Requests</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="font-size:12px;">Student</th>
                        <th style="font-size:12px;">Purpose</th>
                        <th style="font-size:12px;">Status</th>
                    </tr>
                </thead>
                <tbody style="font-size:13px;">
                    @forelse ($recentBorrows as $borrow)
                    <tr>
                        <td>{{ $borrow->student->full_name ?? '-' }}</td>
                        <td>{{ $borrow->purpose ?? '-' }}</td>
                        <td><span class="badge" style="background:#FAEEDA;color:#633806;">Pending</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-3">No pending requests</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <span style="font-weight:600;">📅 Today's Studio Bookings</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="font-size:12px;">Time</th>
                        <th style="font-size:12px;">Studio</th>
                        <th style="font-size:12px;">Status</th>
                    </tr>
                </thead>
                <tbody style="font-size:13px;">
                    @forelse ($todayBookingList as $booking)
                    <tr>
                        <td>{{ $booking->start_time }} – {{ $booking->end_time }}</td>
                        <td>{{ $booking->studio->studio_name ?? '-' }}</td>
                        <td><span class="badge bg-primary">{{ ucfirst($booking->booking_status) }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-3">No bookings today</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection