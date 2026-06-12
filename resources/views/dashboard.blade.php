@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')

{{-- STAT CARDS --}}
<div style="display:grid; grid-template-columns: repeat(4,1fr); gap:16px; margin-bottom:24px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#E1F5EE;">
                <span style="font-size:20px;">📦</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">142</h4>
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
                <h4 class="mb-0 fw-bold">58</h4>
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
                <h4 class="mb-0 fw-bold">7</h4>
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
                <h4 class="mb-0 fw-bold">5</h4>
                <small class="text-muted">Bookings today</small>
            </div>
        </div>
    </div>
</div>

{{-- TABLES ROW --}}
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">

    {{-- BORROW REQUESTS --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <span style="font-weight:600;">📋 Recent Borrow Requests</span>
            <a href="#" class="text-success" style="font-size:13px;">View all</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="font-size:12px;">Student</th>
                        <th style="font-size:12px;">Item</th>
                        <th style="font-size:12px;">Status</th>
                    </tr>
                </thead>
                <tbody style="font-size:13px;">
                    <tr>
                        <td>Ahmad Muzakkir</td>
                        <td>Acoustic Guitar</td>
                        <td><span class="badge" style="background:#FAEEDA;color:#633806;">Pending</span></td>
                    </tr>
                    <tr>
                        <td>Siti Farhana</td>
                        <td>Formal Blazer</td>
                        <td><span class="badge" style="background:#E1F5EE;color:#085041;">Approved</span></td>
                    </tr>
                    <tr>
                        <td>Haziq Zulkifli</td>
                        <td>Keyboard Stand</td>
                        <td><span class="badge" style="background:#FAECE7;color:#712B13;">Overdue</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- STUDIO BOOKINGS --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <span style="font-weight:600;">📅 Today's Studio Bookings</span>
            <a href="#" class="text-success" style="font-size:13px;">View all</a>
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
                    <tr>
                        <td>8:00 – 10:00</td>
                        <td>Music Studio</td>
                        <td><span class="badge bg-success">Active</span></td>
                    </tr>
                    <tr>
                        <td>11:00 – 13:00</td>
                        <td>Dance Studio</td>
                        <td><span class="badge bg-primary">Upcoming</span></td>
                    </tr>
                    <tr>
                        <td>14:00 – 16:00</td>
                        <td>Gamelan Studio</td>
                        <td><span class="badge bg-primary">Upcoming</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection