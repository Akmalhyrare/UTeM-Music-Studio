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

    /* Studio availability schedule */
    .availability-legend { display:flex; gap:14px; flex-wrap:wrap; font-size:12px; color:#555; margin-bottom:10px; }
    .availability-legend span { display:inline-flex; align-items:center; gap:6px; }
    .availability-dot { width:12px; height:12px; border-radius:3px; display:inline-block; }
    .dot-available { background:#10B981; }
    .dot-booked { background:#E0524A; }
    .dot-pending { background:#F0AD4E; }
    .dot-blocked { background:#9AA0A6; }

    .availability-table { width:100%; border-collapse:collapse; font-size:13px; }
    .availability-table td { padding:8px 10px; border-bottom:1px solid #eee; }
    .availability-row { cursor:pointer; transition:background 0.15s; }
    .availability-row[data-status="available"]:hover { background:#f1faf6; }
    .availability-row[data-status="booked"],
    .availability-row[data-status="pending"],
    .availability-row[data-status="maintenance"],
    .availability-row[data-status="blocked"] { cursor:not-allowed; opacity:0.75; }

    .availability-badge { font-size:11px; font-weight:600; padding:2px 10px; border-radius:99px; color:#fff; }
    .badge-available { background:#10B981; }
    .badge-booked { background:#E0524A; }
    .badge-pending { background:#F0AD4E; }
    .badge-maintenance { background:#9AA0A6; }
    .badge-blocked { background:#9AA0A6; }

    .availability-empty { text-align:center; color:#888; font-size:13px; padding:16px 0; }
</style>

<div class="page-header">
    <h2>📅 New Booking</h2>
    <p>Reserve a studio session</p>
</div>

<div class="page-body">

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-outline-secondary">← Back to My Bookings</a>
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

    @if(session('error'))
    <div class="alert alert-danger">❌ {{ session('error') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('bookings.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;">Studio <span class="text-danger">*</span></label>
                    <select name="studio_id" id="studio_id" class="form-control" style="font-size:13px;" required>
                        <option value="">-- Select studio --</option>
                        @foreach($studios as $studio)
                            <option value="{{ $studio->studio_id }}"
                                {{ (old('studio_id', $selectedStudio)) == $studio->studio_id ? 'selected' : '' }}>
                                {{ $studio->studio_name }} ({{ ucfirst($studio->studio_type) }})
                            </option>
                        @endforeach
                    </select>
                    @if($studios->isEmpty())
                        <small class="text-muted">No studios are currently available for booking.</small>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;">Booking Date <span class="text-danger">*</span></label>
                    <input type="date"
                           name="booking_date"
                           id="booking_date"
                           class="form-control"
                           style="font-size:13px;"
                           min="{{ date('Y-m-d') }}"
                           value="{{ old('booking_date') }}"
                           required>
                </div>

                {{-- Studio availability schedule --}}
                <div class="mb-3" id="availability-section" style="display:none;">
                    <label class="form-label" style="font-size:13px;">Studio Schedule</label>

                    <div class="availability-legend">
                        <span><span class="availability-dot dot-available"></span> Available</span>
                        <span><span class="availability-dot dot-booked"></span> Booked</span>
                        <span><span class="availability-dot dot-pending"></span> Pending</span>
                        <span><span class="availability-dot dot-blocked"></span> Maintenance/Blocked</span>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0" id="availability-body">
                            <div class="availability-empty">Select a studio and date to view the schedule.</div>
                        </div>
                    </div>
                    <small class="text-muted">Click an available slot to fill in the time fields below.</small>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label" style="font-size:13px;">Start Time <span class="text-danger">*</span></label>
                        <input type="time"
                               name="start_time"
                               id="start_time"
                               class="form-control"
                               style="font-size:13px;"
                               min="08:00"
                               max="23:30"
                               value="{{ old('start_time') }}"
                               required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="font-size:13px;">End Time <span class="text-danger">*</span></label>
                        <input type="time"
                               name="end_time"
                               id="end_time"
                               class="form-control"
                               style="font-size:13px;"
                               min="08:00"
                               max="23:30"
                               value="{{ old('end_time') }}"
                               required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" style="font-size:13px;">Purpose</label>
                    <textarea name="purpose"
                              class="form-control"
                              style="font-size:13px;"
                              rows="3"
                              placeholder="e.g. Band practice, recording session">{{ old('purpose') }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit"
                            class="btn"
                            style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">
                        ✅ Confirm Booking
                    </button>
                    <a href="{{ route('bookings.index') }}"
                       class="btn btn-outline-secondary"
                       style="font-size:13px;border-radius:8px;">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const studioSelect = document.getElementById('studio_id');
    const dateInput = document.getElementById('booking_date');
    const section = document.getElementById('availability-section');
    const body = document.getElementById('availability-body');
    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');

    const availabilityUrlBase = "{{ url('/studios') }}";

    function renderEmpty(message) {
        body.innerHTML = `<div class="availability-empty">${message}</div>`;
    }

    function renderSchedule(data) {
        if (data.fully_booked) {
            const statuses = new Set(data.slots.map(s => s.status));

            if (statuses.size === 1 && statuses.has('maintenance')) {
                renderEmpty('🔧 Studio is under maintenance for this date.');
            } else if (statuses.size === 1 && statuses.has('blocked')) {
                renderEmpty('🚫 Studio is temporarily blocked for this date.');
            } else {
                renderEmpty('Studio is fully booked for this date.');
            }
            return;
        }

        const badgeLabels = {
            available: 'Available',
            booked: 'Booked',
            pending: 'Pending',
            maintenance: 'Maintenance',
            blocked: 'Blocked',
        };

        const rows = data.slots.map(slot => `
            <tr class="availability-row" data-status="${slot.status}" data-start="${slot.start}" data-end="${slot.end}">
                <td>${slot.label}</td>
                <td style="text-align:right;">
                    <span class="availability-badge badge-${slot.status}">${badgeLabels[slot.status]}</span>
                </td>
            </tr>
        `).join('');

        body.innerHTML = `<table class="availability-table"><tbody>${rows}</tbody></table>`;

        body.querySelectorAll('.availability-row[data-status="available"]').forEach(row => {
            row.addEventListener('click', () => {
                startTime.value = row.dataset.start;
                endTime.value = row.dataset.end;
            });
        });
    }

    function loadAvailability() {
        const studioId = studioSelect.value;
        const date = dateInput.value;

        if (!studioId || !date) {
            section.style.display = 'none';
            return;
        }

        section.style.display = '';
        renderEmpty('Loading schedule...');

        fetch(`${availabilityUrlBase}/${studioId}/availability?date=${date}`, {
            headers: { 'Accept': 'application/json' },
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load availability');
                }
                return response.json();
            })
            .then(renderSchedule)
            .catch(() => renderEmpty('Unable to load the schedule right now. Please try again.'));
    }

    studioSelect.addEventListener('change', loadAvailability);
    dateInput.addEventListener('change', loadAvailability);

    loadAvailability();
});
</script>

@endsection
