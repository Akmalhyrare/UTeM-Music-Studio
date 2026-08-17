@extends('layouts.app')

@section('page-title', 'Borrowing Details')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('staff.borrowings.index') }}" class="btn btn-sm btn-outline-secondary">← Back</a>
    <h5 class="mb-0 fw-bold">Borrowing #{{ $borrowing->borrow_id }}</h5>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">✅ {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">❌ {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card border-0 shadow-sm mb-3" style="max-width:700px;">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Student</span>
            <strong>{{ $borrowing->student->full_name ?? '-' }}</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Student Email</span>
            <strong>{{ $borrowing->student->email ?? '-' }}</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Pickup Date</span>
            <strong>{{ \Illuminate\Support\Carbon::parse($borrowing->pickup_date)->format('d M Y') }}</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Return Date</span>
            <strong>{{ \Illuminate\Support\Carbon::parse($borrowing->return_date)->format('d M Y') }}</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Purpose / Event</span>
            <strong>{{ $borrowing->purpose ?? '-' }}</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Status</span>
            <strong>
                <x-status-badge :status="$borrowing->borrow_status" />
                @if($borrowing->is_overdue)
                    <x-status-badge status="overdue" label="Overdue" />
                @endif
            </strong>
        </div>
        @if($borrowing->staff)
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Approved By</span>
            <strong>{{ $borrowing->staff->full_name }}</strong>
        </div>
        @endif
        @if($borrowing->collected_at)
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Collected</span>
            <strong>{{ $borrowing->collected_at->format('d M Y, H:i') }}{{ $borrowing->collectedByStaff ? ' by ' . $borrowing->collectedByStaff->full_name : '' }}</strong>
        </div>
        @endif
        @if($borrowing->returned_at)
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Returned</span>
            <strong>{{ $borrowing->returned_at->format('d M Y, H:i') }}{{ $borrowing->returnedByStaff ? ' by ' . $borrowing->returnedByStaff->full_name : '' }}</strong>
        </div>
        @endif
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Requested On</span>
            <strong>{{ $borrowing->created_at->format('d M Y, H:i') }}</strong>
        </div>

        @if($borrowing->borrow_status === 'pending')
        <div class="d-flex gap-2 mt-4">
            <form method="POST" action="{{ route('staff.borrowings.approve', $borrowing->borrow_id) }}" onsubmit="return confirm('Approve this reservation?')">
                @csrf
                @method('PUT')
                <button type="submit" class="btn" style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">✅ Approve Reservation</button>
            </form>
            <form method="POST" action="{{ route('staff.borrowings.reject', $borrowing->borrow_id) }}" onsubmit="return confirm('Reject this request?')">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-outline-danger" style="font-size:13px;border-radius:8px;">❌ Reject</button>
            </form>
        </div>
        @endif

        @if($borrowing->borrow_status === 'reserved')
        <div class="d-flex gap-2 mt-4">
            <form method="POST" action="{{ route('staff.borrowings.collect', $borrowing->borrow_id) }}" onsubmit="return confirm('Mark these items as collected by the student?')">
                @csrf
                @method('PUT')
                <button type="submit" class="btn" style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">📦 Mark as Collected</button>
            </form>
        </div>
        @endif

    </div>
</div>

{{-- ITEMS --}}
<div class="card border-0 shadow-sm mb-3" style="max-width:700px;">
    <div class="card-header bg-white border-bottom"><span style="font-weight:600;">Requested Items</span></div>
    <div class="card-body p-0">
        <table class="table mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Quantity Borrowed</th>
                    <th>Available Stock</th>
                </tr>
            </thead>
            <tbody>
                @foreach($borrowing->borrowingDetails as $detail)
                <tr>
                    <td>{{ $detail->item->item_name ?? '-' }}</td>
                    <td>{{ $detail->item->category->category_name ?? '-' }}</td>
                    <td>{{ $detail->quantity_borrowed }}</td>
                    <td>{{ $detail->item->available_quantity ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- PROCESS RETURN (collected only) --}}
@if($borrowing->borrow_status === 'collected')
@php
    $returnedByItem = $borrowing->returnRecords->groupBy('item_id')->map(fn($r) => $r->sum('quantity_returned'));
@endphp
<div class="card border-0 shadow-sm mb-3" style="max-width:700px;">
    <div class="card-header bg-white border-bottom"><span style="font-weight:600;">📦 Process Return</span></div>
    <div class="card-body">
        <form method="POST" action="{{ route('staff.borrowings.return', $borrowing->borrow_id) }}">
            @csrf
            <table class="table mb-3" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Outstanding</th>
                        <th>Qty Returning</th>
                        <th>Condition</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($borrowing->borrowingDetails as $i => $detail)
                        @php
                            $alreadyReturned = $returnedByItem[$detail->item_id] ?? 0;
                            $outstanding = $detail->quantity_borrowed - $alreadyReturned;
                        @endphp
                        @if($outstanding > 0)
                        <tr>
                            <td>
                                {{ $detail->item->item_name ?? '-' }}
                                <input type="hidden" name="returns[{{ $i }}][item_id]" value="{{ $detail->item_id }}">
                            </td>
                            <td>{{ $outstanding }}</td>
                            <td style="max-width:100px;">
                                <input type="number" name="returns[{{ $i }}][quantity_returned]" class="form-control" min="0" max="{{ $outstanding }}" value="0">
                            </td>
                            <td style="max-width:140px;">
                                <select name="returns[{{ $i }}][item_condition]" class="form-control">
                                    <option value="good">Good</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="lost">Lost</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="returns[{{ $i }}][damage_note]" class="form-control" placeholder="Optional note">
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn" style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">✅ Record Return</button>
        </form>
    </div>
</div>
@endif

{{-- RETURN HISTORY --}}
@if($borrowing->returnRecords->isNotEmpty())
<div class="card border-0 shadow-sm mb-3" style="max-width:700px;">
    <div class="card-header bg-white border-bottom"><span style="font-weight:600;">Return History</span></div>
    <div class="card-body p-0">
        <table class="table mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr><th>Item</th><th>Qty Returned</th><th>Condition</th><th>Note</th><th>Return Date</th></tr>
            </thead>
            <tbody>
                @foreach($borrowing->returnRecords as $record)
                <tr>
                    <td>{{ $record->item->item_name ?? '-' }}</td>
                    <td>{{ $record->quantity_returned }}</td>
                    <td>{{ ucfirst($record->item_condition) }}</td>
                    <td>{{ $record->damage_note ?? '-' }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($record->return_date)->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
