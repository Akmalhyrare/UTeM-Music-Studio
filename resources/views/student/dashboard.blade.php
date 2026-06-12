@extends('layouts.student')

@section('content')

<style>
    .dash-header {
        background: linear-gradient(135deg, #1a1a2e, #0f3460);
        color: #fff; padding: 36px 40px;
    }
    .dash-header h2 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
    .dash-header p { color: rgba(255,255,255,0.6); font-size: 14px; }

    .dash-body { max-width: 1000px; margin: 0 auto; padding: 32px 40px; }
    .stat-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 28px; }
    .stat-card {
        background: #fff; border-radius: 12px;
        padding: 20px; border: 1px solid #eee;
        display: flex; align-items: center; gap: 14px;
    }
    .stat-icon { font-size: 28px; }
    .stat-num { font-size: 24px; font-weight: 700; color: #1a1a2e; }
    .stat-label { font-size: 12px; color: #888; }

    .section-title { font-size: 15px; font-weight: 600; color: #1a1a2e; margin-bottom: 14px; }
    .table-card {
        background: #fff; border-radius: 12px;
        border: 1px solid #eee; overflow: hidden;
        margin-bottom: 20px;
    }
    .table-card-header {
        padding: 14px 20px; border-bottom: 1px solid #eee;
        font-size: 14px; font-weight: 600; color: #1a1a2e;
        display: flex; justify-content: space-between; align-items: center;
    }
    .table-card table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .table-card th { padding: 10px 16px; text-align: left; font-size: 11px; color: #888; font-weight: 500; background: #fafafa; border-bottom: 1px solid #eee; }
    .table-card td { padding: 12px 16px; border-bottom: 1px solid #f5f5f5; color: #333; }
    .table-card tr:last-child td { border-bottom: none; }
    .badge { padding: 3px 8px; border-radius: 99px; font-size: 11px; font-weight: 500; }
    .badge-pending { background: #FAEEDA; color: #633806; }
    .badge-approved { background: #E1F5EE; color: #085041; }
    .badge-overdue { background: #FAECE7; color: #712B13; }
    .no-data { text-align: center; color: #888; padding: 30px; font-size: 13px; }

    .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 28px; }
    .qa-card {
        background: #fff; border-radius: 12px;
        padding: 20px; border: 1px solid #eee;
        text-decoration: none; color: inherit;
        display: flex; align-items: center; gap: 14px;
        transition: all 0.2s;
    }
    .qa-card:hover { border-color: #1D9E75; box-shadow: 0 4px 12px rgba(29,158,117,0.1); }
    .qa-icon { font-size: 28px; }
    .qa-title { font-size: 14px; font-weight: 600; color: #1a1a2e; }
    .qa-sub { font-size: 12px; color: #888; }
</style>

{{-- HEADER --}}
<div class="dash-header">
    <h2>👋 Welcome back, {{ session('user_name') }}!</h2>
    <p>Here's a summary of your activity at UTeM Music Studio</p>
</div>

<div class="dash-body">

    {{-- STAT CARDS --}}
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div>
                <div class="stat-num">{{ $myBorrowings->count() }}</div>
                <div class="stat-label">My borrowings</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div>
                <div class="stat-num">{{ $myBookings->count() }}</div>
                <div class="stat-label">My bookings</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div>
                <div class="stat-num">{{ $availableItems }}</div>
                <div class="stat-label">Items available</div>
            </div>
        </div>
    </div>

    {{-- QUICK ACTIONS --}}
    <div class="section-title">Quick Actions</div>
    <div class="quick-actions">
        <a href="{{ route('items.browse') }}" class="qa-card">
            <div class="qa-icon">📦</div>
            <div>
                <div class="qa-title">Browse Items</div>
                <div class="qa-sub">View and request equipment</div>
            </div>
        </a>
        <a href="{{ route('studios.browse') }}" class="qa-card">
            <div class="qa-icon">📅</div>
            <div>
                <div class="qa-title">Book Studio</div>
                <div class="qa-sub">Reserve a studio session</div>
            </div>
        </a>
    </div>

    {{-- MY BORROWINGS --}}
    <div class="section-title">My Recent Borrowings</div>
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Purpose</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($myBorrowings as $borrow)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $borrow->borrow_date }}</td>
                    <td>{{ $borrow->due_date }}</td>
                    <td>{{ $borrow->purpose ?? '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $borrow->borrow_status }}">
                            {{ ucfirst($borrow->borrow_status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="no-data">No borrowings yet. <a href="{{ route('items.browse') }}" style="color:#1D9E75;">Browse items</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MY BOOKINGS --}}
    <div class="section-title">My Recent Studio Bookings</div>
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Studio</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($myBookings as $booking)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $booking->studio->studio_name ?? '-' }}</td>
                    <td>{{ $booking->booking_date }}</td>
                    <td>{{ $booking->start_time }} – {{ $booking->end_time }}</td>
                    <td>
                        <span class="badge badge-{{ $booking->booking_status }}">
                            {{ ucfirst($booking->booking_status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="no-data">No bookings yet. <a href="{{ route('studios.browse') }}" style="color:#1D9E75;">Book a studio</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection