@extends('layouts.app')

@section('page-title', 'Booking Details')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('staff.bookings.index') }}" class="btn btn-sm btn-outline-secondary">← Back</a>
    <h5 class="mb-0 fw-bold">Booking #{{ $booking->booking_id }}</h5>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card border-0 shadow-sm" style="max-width:600px;">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Student</span>
            <strong>{{ $booking->student->full_name ?? '-' }}</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Student Email</span>
            <strong>{{ $booking->student->email ?? '-' }}</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Studio</span>
            <strong>{{ $booking->studio->studio_name ?? '-' }} ({{ ucfirst($booking->studio->studio_type ?? '-') }})</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Date</span>
            <strong>{{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d M Y') }}</strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Time</span>
            <strong>
                {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('H:i') }}
                –
                {{ \Illuminate\Support\Carbon::parse($booking->end_time)->format('H:i') }}
            </strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Purpose</span>
            <strong>{{ $booking->purpose ?? '-' }}</strong>
        </div>
        @if($booking->staff)
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Last Handled By</span>
            <strong>{{ $booking->staff->full_name }}</strong>
        </div>
        @endif
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Status</span>
            <strong>
                <x-status-badge :status="$booking->booking_status" />
            </strong>
        </div>
        <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13px;">
            <span class="text-muted">Booked On</span>
            <strong>{{ $booking->created_at->format('d M Y, H:i') }}</strong>
        </div>

        @if($booking->booking_status === 'confirmed')
        <form method="POST" action="{{ route('staff.bookings.update', $booking->booking_id) }}" class="mt-4"
              onsubmit="return confirm('Cancel this booking?')">
            @csrf
            @method('PUT')
            <input type="hidden" name="booking_status" value="cancelled">

            <div class="d-flex gap-2">
                <button type="submit"
                        class="btn btn-outline-danger"
                        style="font-size:13px;border-radius:8px;">
                    ❌ Cancel Booking
                </button>
                <a href="{{ route('staff.bookings.index') }}"
                   class="btn btn-outline-secondary"
                   style="font-size:13px;border-radius:8px;">
                    Back
                </a>
            </div>
        </form>
        <small class="text-muted d-block mt-2" style="font-size:11px;">
            This booking will automatically be marked Completed once its end time has passed.
        </small>
        @endif

    </div>
</div>

@endsection
