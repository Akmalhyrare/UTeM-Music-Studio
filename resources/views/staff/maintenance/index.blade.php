@extends('layouts.app')

@section('page-title', 'Maintenance')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">✅ {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">❌ {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- FILTER --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('staff.maintenance.index') }}" data-instant-search>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <input type="text"
                       name="search"
                       class="form-control"
                       style="font-size:13px; max-width:220px;"
                       placeholder="Search item, issue, staff..."
                       value="{{ request('search') }}">
                <select name="status" class="form-control" style="font-size:13px; max-width:180px;" onchange="this.form.submit()">
                    <option value="">All status</option>
                    <option value="pending"  {{ request('status') == 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
                <a href="{{ route('staff.maintenance.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">🔧 Maintenance & Damage Reports</span>
        <span class="text-muted" style="font-size:12px;">{{ $maintenances->total() }} record(s)</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">#</th>
                    <th style="font-size:12px;">Item</th>
                    <th style="font-size:12px;">Issue Type</th>
                    <th style="font-size:12px;">Description</th>
                    <th style="font-size:12px;">Reported</th>
                    <th style="font-size:12px;">Status</th>
                    <th style="font-size:12px;">Actions</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse ($maintenances as $maintenance)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $maintenance->item->item_name ?? '-' }}</td>
                    <td>{{ ucfirst($maintenance->issue_type ?? '-') }}</td>
                    <td>{{ Str::limit($maintenance->description ?? '-', 40) }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($maintenance->report_date)->format('d M Y') }}</td>
                    <td>
                        @if($maintenance->maintenance_status == 'resolved')
                            <span class="badge" style="background:#D1FAE5;color:#065F46;">Resolved</span>
                        @else
                            <span class="badge" style="background:#FEF3C7;color:#92400E;">Pending</span>
                        @endif
                    </td>
                    <td>
                        @if($maintenance->maintenance_status !== 'resolved')
                        <form method="POST" action="{{ route('staff.maintenance.resolve', $maintenance->maintenance_id) }}" onsubmit="return confirm('Mark resolved and return item to available stock?')">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-sm" style="background:#7C3AED;color:#fff;">Mark Resolved</button>
                        </form>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        @if(request('search') || request('status'))
                            No maintenance records match your search/filters.
                        @else
                            No maintenance records found.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($maintenances->hasPages())
        <div class="card-footer bg-white">
            {{ $maintenances->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@endsection
