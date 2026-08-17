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

    .page-body { max-width: 700px; margin: 0 auto; padding: 32px 40px; }

    .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: #888; }
    .detail-value { color: #211C36; font-weight: 500; text-align: right; }

    .table-mini { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
    .table-mini th { padding: 8px 10px; text-align: left; font-size: 11px; color: #888; font-weight: 500; background: #fafafa; border-bottom: 1px solid #eee; }
    .table-mini td { padding: 8px 10px; border-bottom: 1px solid #f5f5f5; }
</style>

<div class="page-header">
    <h2>📋 Borrowing Details</h2>
    <p>Request #{{ $borrowing->borrow_id }}</p>
</div>

<div class="page-body">

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('borrowings.index') }}" class="btn btn-sm btn-outline-secondary">← Back to My Borrowings</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">

            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    <x-status-badge :status="$borrowing->borrow_status" />
                    @if($borrowing->is_overdue)
                        <x-status-badge status="overdue" label="Overdue" />
                    @endif
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Pickup Date</span>
                <span class="detail-value">{{ \Illuminate\Support\Carbon::parse($borrowing->pickup_date)->format('d M Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Return Date</span>
                <span class="detail-value">{{ \Illuminate\Support\Carbon::parse($borrowing->return_date)->format('d M Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Purpose / Event</span>
                <span class="detail-value">{{ $borrowing->purpose ?? '-' }}</span>
            </div>
            @if($borrowing->staff)
            <div class="detail-row">
                <span class="detail-label">Handled By</span>
                <span class="detail-value">{{ $borrowing->staff->full_name }}</span>
            </div>
            @endif
            @if($borrowing->collected_at)
            <div class="detail-row">
                <span class="detail-label">Collected</span>
                <span class="detail-value">{{ $borrowing->collected_at->format('d M Y, H:i') }}{{ $borrowing->collectedByStaff ? ' by ' . $borrowing->collectedByStaff->full_name : '' }}</span>
            </div>
            @endif
            @if($borrowing->returned_at)
            <div class="detail-row">
                <span class="detail-label">Returned</span>
                <span class="detail-value">{{ $borrowing->returned_at->format('d M Y, H:i') }}{{ $borrowing->returnedByStaff ? ' by ' . $borrowing->returnedByStaff->full_name : '' }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Requested On</span>
                <span class="detail-value">{{ $borrowing->created_at->format('d M Y, H:i') }}</span>
            </div>

            @if(in_array($borrowing->borrow_status, ['pending', 'reserved']))
            <div class="mt-4">
                <form method="POST" action="{{ route('borrowings.destroy', $borrowing->borrow_id) }}" onsubmit="return confirm('Cancel this request?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger" style="font-size:13px;border-radius:8px;">🗑️ Cancel Request</button>
                </form>
            </div>
            @endif

        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">
            <h6 style="font-size:13px; font-weight:600; margin-bottom:0;">Requested Items</h6>
            <table class="table-mini">
                <thead><tr><th>Item</th><th>Category</th><th>Quantity</th></tr></thead>
                <tbody>
                    @foreach($borrowing->borrowingDetails as $detail)
                    <tr>
                        <td>{{ $detail->item->item_name ?? '-' }}</td>
                        <td>{{ $detail->item->category->category_name ?? '-' }}</td>
                        <td>{{ $detail->quantity_borrowed }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($borrowing->returnRecords->isNotEmpty())
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">
            <h6 style="font-size:13px; font-weight:600; margin-bottom:0;">Return History</h6>
            <table class="table-mini">
                <thead><tr><th>Item</th><th>Qty Returned</th><th>Condition</th><th>Return Date</th></tr></thead>
                <tbody>
                    @foreach($borrowing->returnRecords as $record)
                    <tr>
                        <td>{{ $record->item->item_name ?? '-' }}</td>
                        <td>{{ $record->quantity_returned }}</td>
                        <td><x-status-badge :status="$record->item_condition" /></td>
                        <td>{{ \Illuminate\Support\Carbon::parse($record->return_date)->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

@endsection
