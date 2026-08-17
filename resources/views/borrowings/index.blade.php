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

    .table-card { background: #fff; border-radius: 12px; border: 1px solid #eee; overflow: hidden; margin-bottom: 20px; }
    .table-card-header {
        padding: 14px 20px; border-bottom: 1px solid #eee;
        font-size: 14px; font-weight: 600; color: #211C36;
        display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .table-card table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .table-card th { padding: 10px 16px; text-align: left; font-size: 11px; color: #888; font-weight: 500; background: #fafafa; border-bottom: 1px solid #eee; }
    .table-card td { padding: 12px 16px; border-bottom: 1px solid #f5f5f5; color: #333; vertical-align: middle; }
    .table-card tr:last-child td { border-bottom: none; }
    .no-data { text-align: center; color: #888; padding: 30px; font-size: 13px; }

    .btn-new { background: #7C3AED; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; text-decoration: none; }
    .btn-new:hover { background: #6D28D9; color: #fff; }
    .action-cancel { color: #A32D2D; background: none; border: none; padding: 0; cursor: pointer; font-size: 12px; }
</style>

<div class="page-header">
    <h2>📋 My Borrowings</h2>
    <p>Track your equipment & attire borrowing requests</p>
</div>

<div class="page-body">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">✅ {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">❌ {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="table-card">
        <div class="table-card-header">
            <span>📋 Borrowing Requests</span>
            <div style="display:flex; gap:8px; align-items:center;">
                <form method="GET" action="{{ route('borrowings.index') }}" data-instant-search style="display:flex; gap:8px;">
                    <input type="text" name="search" class="form-control form-control-sm" style="font-size:12px;" placeholder="Search item, purpose..." value="{{ request('search') }}">
                    <select name="status" class="form-control form-control-sm" style="font-size:12px;" onchange="this.form.submit()">
                        <option value="">All status</option>
                        <option value="pending"   {{ request('status') == 'pending'   ? 'selected' : '' }}>Pending</option>
                        <option value="reserved"  {{ request('status') == 'reserved'  ? 'selected' : '' }}>Reserved</option>
                        <option value="collected" {{ request('status') == 'collected' ? 'selected' : '' }}>Collected</option>
                        <option value="returned"  {{ request('status') == 'returned'  ? 'selected' : '' }}>Returned</option>
                        <option value="rejected"  {{ request('status') == 'rejected'  ? 'selected' : '' }}>Rejected</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </form>
                <a href="{{ route('borrowings.create') }}" class="btn-new">+ New Request</a>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Items</th>
                    <th>Pickup Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($borrowings as $borrowing)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        @foreach($borrowing->borrowingDetails as $detail)
                            {{ $detail->item->item_name ?? '-' }} (x{{ $detail->quantity_borrowed }})@if(!$loop->last), @endif
                        @endforeach
                    </td>
                    <td>{{ \Illuminate\Support\Carbon::parse($borrowing->pickup_date)->format('d M Y') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($borrowing->return_date)->format('d M Y') }}</td>
                    <td>
                        <x-status-badge :status="$borrowing->borrow_status" />
                        @if($borrowing->is_overdue)
                            <x-status-badge status="overdue" label="Overdue" />
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('borrowings.show', $borrowing->borrow_id) }}" class="action-link" style="color:#211C36; font-size:12px; text-decoration:none; margin-right:8px;">View</a>
                        @if(in_array($borrowing->borrow_status, ['pending', 'reserved']))
                            <form method="POST" action="{{ route('borrowings.destroy', $borrowing->borrow_id) }}" style="display:inline;" onsubmit="return confirm('Cancel this request?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-cancel">Cancel</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">
                    @if(request('search') || request('status'))
                        No borrowing requests match your search/filters.
                    @else
                        No borrowing requests yet. <a href="{{ route('borrowings.create') }}" style="color:#7C3AED;">Create your first request</a>
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection
