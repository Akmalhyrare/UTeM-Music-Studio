@extends('layouts.student')

@section('content')

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

    .table-card {
        background: #fff; border-radius: 12px;
        border: 1px solid #eee; overflow: hidden;
        margin-bottom: 20px;
    }
    .table-card-header {
        padding: 14px 20px; border-bottom: 1px solid #eee;
        font-size: 14px; font-weight: 600; color: #211C36;
        display: flex; justify-content: space-between; align-items: center;
        gap: 10px; flex-wrap: wrap;
    }
    .table-card table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .table-card th { padding: 10px 16px; text-align: left; font-size: 11px; color: #888; font-weight: 500; background: #fafafa; border-bottom: 1px solid #eee; }
    .table-card td { padding: 12px 16px; border-bottom: 1px solid #f5f5f5; color: #333; vertical-align: middle; }
    .table-card tr:last-child td { border-bottom: none; }
    .no-data { text-align: center; color: #888; padding: 30px; font-size: 13px; }

    .badge { padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 500; }
    .badge-confirmed { background: #D1FAE5; color: #065F46; }
    .badge-cancelled { background: #FEE2E2; color: #991B1B; }
    .badge-completed { background: #E6F1FB; color: #0C447C; }

    .btn-new {
        background: #7C3AED; color: #fff;
        border: none; padding: 8px 16px;
        border-radius: 8px; font-size: 13px;
        font-weight: 500; text-decoration: none;
    }
    .btn-new:hover { background: #6D28D9; color: #fff; }

    .action-link { font-size: 12px; text-decoration: none; margin-right: 8px; }
    .action-edit { color: #0C447C; }
    .action-cancel { color: #A32D2D; background: none; border: none; padding: 0; cursor: pointer; font-size: 12px; }
</style>

<div class="page-header">
    <h2>📅 My Bookings</h2>
    <p>Manage your studio booking requests</p>
</div>

<div class="page-body">

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

    <div class="table-card">
        <div class="table-card-header">
            <span>📋 Booking Requests</span>
            <div style="display:flex; gap:8px; align-items:center;">
                <form method="GET" action="{{ route('bookings.index') }}" data-instant-search style="display:flex; gap:8px;">
                    <input type="text" name="search" class="form-control form-control-sm" style="font-size:12px;" placeholder="Search studio, purpose..." value="{{ request('search') }}">
                    <select name="status" class="form-control form-control-sm" style="font-size:12px;" onchange="this.form.submit()">
                        <option value="">All status</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </form>
                <a href="{{ route('bookings.create') }}" class="btn-new">+ New Booking</a>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Studio</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Purpose</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $booking->studio->studio_name ?? '-' }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d M Y') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('H:i') }} – {{ \Illuminate\Support\Carbon::parse($booking->end_time)->format('H:i') }}</td>
                    <td>{{ $booking->purpose ?? '-' }}</td>
                    <td>
                        <x-status-badge :status="$booking->booking_status" />
                    </td>
                    <td>
                        <a href="{{ route('bookings.show', $booking->booking_id) }}" class="action-link" style="color:#211C36;">View</a>
                        @if($booking->booking_status === 'confirmed')
                            <a href="{{ route('bookings.edit', $booking->booking_id) }}" class="action-link action-edit">Edit</a>
                        @endif
                        @if($booking->booking_status === 'confirmed')
                            <form method="POST" action="{{ route('bookings.destroy', $booking->booking_id) }}" style="display:inline;" onsubmit="return confirm('Cancel this booking?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-cancel">Cancel</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="no-data">
                    @if(request('search') || request('status'))
                        No bookings match your search/filters.
                    @else
                        No bookings yet. <a href="{{ route('bookings.create') }}" style="color:#7C3AED;">Create your first booking</a>
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection
