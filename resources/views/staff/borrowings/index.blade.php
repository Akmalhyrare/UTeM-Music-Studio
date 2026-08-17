@extends('layouts.app')

@section('page-title', 'Borrowing Management')

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
<div style="display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-bottom:24px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FAEEDA;">
                <span style="font-size:20px;">⏳</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $pendingCount }}</h4>
                <small class="text-muted">Pending requests</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#F3E8FF;">
                <span style="font-size:20px;">📋</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $borrowings->total() }}</h4>
                <small class="text-muted">Matching requests</small>
            </div>
        </div>
    </div>
</div>

{{-- FILTER --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('staff.borrowings.index') }}" data-instant-search>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <input type="text"
                       name="search"
                       class="form-control"
                       style="font-size:13px; max-width:220px;"
                       placeholder="Search student, item, purpose..."
                       value="{{ request('search') }}">
                <select name="status" class="form-control" style="font-size:13px; max-width:180px;" onchange="this.form.submit()">
                    <option value="">All status</option>
                    <option value="pending"   {{ request('status') == 'pending'   ? 'selected' : '' }}>Pending</option>
                    <option value="reserved"  {{ request('status') == 'reserved'  ? 'selected' : '' }}>Reserved</option>
                    <option value="collected" {{ request('status') == 'collected' ? 'selected' : '' }}>Collected</option>
                    <option value="returned"  {{ request('status') == 'returned'  ? 'selected' : '' }}>Returned</option>
                    <option value="rejected"  {{ request('status') == 'rejected'  ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                <a href="{{ route('staff.borrowings.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- BORROWINGS TABLE --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">📋 Borrowing Requests</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">#</th>
                    <th style="font-size:12px;">Student</th>
                    <th style="font-size:12px;">Items</th>
                    <th style="font-size:12px;">Request Date</th>
                    <th style="font-size:12px;">Pickup Date</th>
                    <th style="font-size:12px;">Return Date</th>
                    <th style="font-size:12px;">Status</th>
                    <th style="font-size:12px;">Actions</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse ($borrowings as $borrowing)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $borrowing->student->full_name ?? '-' }}</td>
                    <td>
                        @foreach($borrowing->borrowingDetails as $detail)
                            {{ $detail->item->item_name ?? '-' }} (x{{ $detail->quantity_borrowed }})@if(!$loop->last), @endif
                        @endforeach
                    </td>
                    <td>{{ $borrowing->created_at->format('d M Y') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($borrowing->pickup_date)->format('d M Y') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($borrowing->return_date)->format('d M Y') }}</td>
                    <td>
                        <x-status-badge :status="$borrowing->borrow_status" />
                        @if($borrowing->is_overdue)
                            <x-status-badge status="overdue" label="Overdue" />
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('staff.borrowings.show', $borrowing->borrow_id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        @if(request('search') || request('status'))
                            No borrowing requests match your search/filters.
                        @else
                            No borrowing requests found.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($borrowings->hasPages())
        <div class="card-footer bg-white">
            {{ $borrowings->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@endsection
