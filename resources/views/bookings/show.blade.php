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

    .page-body { max-width: 600px; margin: 0 auto; padding: 32px 40px; }

    .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: #888; }
    .detail-value { color: #211C36; font-weight: 500; text-align: right; }

    .badge { padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 500; }
    .badge-confirmed { background: #D1FAE5; color: #065F46; }
    .badge-cancelled { background: #FEE2E2; color: #991B1B; }
    .badge-completed { background: #E6F1FB; color: #0C447C; }
</style>

<div class="page-header">
    <h2>📅 Booking Details</h2>
    <p>Studio booking #{{ $booking->booking_id }}</p>
</div>

<div class="page-body">

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-outline-secondary">← Back to My Bookings</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <div class="detail-row">
                <span class="detail-label">Studio</span>
                <span class="detail-value">{{ $booking->studio->studio_name ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Studio Type</span>
                <span class="detail-value">{{ ucfirst($booking->studio->studio_type ?? '-') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date</span>
                <span class="detail-value">{{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d M Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time</span>
                <span class="detail-value">
                    {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('H:i') }}
                    –
                    {{ \Illuminate\Support\Carbon::parse($booking->end_time)->format('H:i') }}
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Purpose</span>
                <span class="detail-value">{{ $booking->purpose ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    <x-status-badge :status="$booking->booking_status" />
                </span>
            </div>
            @if($booking->staff)
            <div class="detail-row">
                <span class="detail-label">Handled By</span>
                <span class="detail-value">{{ $booking->staff->full_name }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Booked On</span>
                <span class="detail-value">{{ $booking->created_at->format('d M Y, H:i') }}</span>
            </div>

            <div class="d-flex gap-2 mt-4">
                @if($booking->booking_status === 'confirmed')
                    <a href="{{ route('bookings.edit', $booking->booking_id) }}"
                       class="btn"
                       style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">
                        ✏️ Edit
                    </a>
                @endif
                @if($booking->booking_status === 'confirmed')
                    <form method="POST" action="{{ route('bookings.destroy', $booking->booking_id) }}" onsubmit="return confirm('Cancel this booking?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" style="font-size:13px;border-radius:8px;">
                            🗑️ Cancel Booking
                        </button>
                    </form>
                @endif
            </div>

        </div>
    </div>

</div>

@endsection
