@extends('layouts.app')

@section('page-title', 'Studio Calendar')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('staff.studios.edit', $studio->studio_id) }}" class="btn btn-sm btn-outline-secondary">← Back to {{ $studio->studio_name }}</a>
    <h5 class="mb-0 fw-bold">📅 Calendar — {{ $studio->studio_name }}</h5>
</div>

<div id="calendar-alert"></div>

<div class="calendar-legend mb-3">
    <span><span class="cal-dot" style="background:#10B981;"></span> Available</span>
    <span><span class="cal-dot" style="background:#3B82C4;"></span> Confirmed Booking</span>
    <span><span class="cal-dot" style="background:#F0AD4E;"></span> Pending Booking</span>
    <span><span class="cal-dot" style="background:#FB923C;"></span> Maintenance</span>
    <span><span class="cal-dot" style="background:#9AA0A6;"></span> Blocked</span>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="prev-week">← Previous Week</button>
        <span id="week-label" style="font-weight:600; font-size:13px;"></span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="next-week">Next Week →</button>
    </div>
    <div class="card-body p-0" style="overflow-x:auto;">
        <table class="calendar-table" id="calendar-table">
            <thead>
                <tr id="calendar-head"></tr>
            </thead>
            <tbody id="calendar-body"></tbody>
        </table>
    </div>
</div>

<small class="text-muted d-block mt-2">
    Click an empty cell to schedule a maintenance or blocked period. Click a maintenance/blocked cell to remove it.
</small>

{{-- CREATE PERIOD MODAL --}}
<div id="period-modal" class="cal-modal-overlay" style="display:none;">
    <div class="cal-modal">
        <h6 class="fw-bold mb-3">Schedule Maintenance / Blocked Period</h6>

        <div id="period-error" class="alert alert-danger" style="display:none; font-size:12px; padding:8px 12px;"></div>

        <div class="mb-2">
            <label class="form-label" style="font-size:12px;">Type</label>
            <select id="period-type" class="form-control" style="font-size:13px;">
                <option value="maintenance">Maintenance</option>
                <option value="blocked">Blocked</option>
            </select>
        </div>
        <div class="row">
            <div class="col-6 mb-2">
                <label class="form-label" style="font-size:12px;">From</label>
                <input type="datetime-local" id="period-start" class="form-control" style="font-size:13px;">
            </div>
            <div class="col-6 mb-2">
                <label class="form-label" style="font-size:12px;">To</label>
                <input type="datetime-local" id="period-end" class="form-control" style="font-size:13px;">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" style="font-size:12px;">Reason</label>
            <input type="text" id="period-reason" class="form-control" style="font-size:13px;"
                   placeholder="e.g. Air conditioning repair">
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="period-cancel">Cancel</button>
            <button type="button" class="btn btn-sm" style="background:#7C3AED;color:#fff;" id="period-save">Save</button>
        </div>
    </div>
</div>

<style>
.calendar-legend { display:flex; gap:16px; flex-wrap:wrap; font-size:12px; color:#555; }
.cal-dot { display:inline-block; width:12px; height:12px; border-radius:3px; margin-right:4px; vertical-align:middle; }

.calendar-table { width:100%; border-collapse:collapse; font-size:12px; min-width:760px; }
.calendar-table th, .calendar-table td { border:1px solid #eee; padding:0; text-align:center; }
.calendar-table th { background:#f8f9fa; padding:8px 4px; font-weight:600; }
.calendar-table .time-col { width:70px; background:#f8f9fa; font-weight:500; padding:6px; white-space:nowrap; }

.cal-cell { height:34px; cursor:pointer; position:relative; transition:background 0.15s; }
.cal-cell:hover { background:#f1faf6; }
.cal-cell.has-event { cursor:default; }
.cal-cell .cal-event { position:absolute; inset:2px; border-radius:4px; color:#fff; font-size:10px; padding:2px 4px; overflow:hidden; text-align:left; line-height:1.2; }
.cal-cell.removable .cal-event { cursor:pointer; }

.cal-modal-overlay {
    position:fixed; top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center;
    z-index:2000;
}
.cal-modal {
    background:#fff; border-radius:10px; padding:20px; width:360px; box-shadow:0 10px 40px rgba(0,0,0,0.2);
}
</style>

<script>
const studioId = {{ $studio->studio_id }};
const calendarDataUrl = "{{ route('staff.studios.calendar.data', $studio->studio_id) }}";
const unavailabilityStoreUrl = "{{ route('staff.studios.unavailability.store', $studio->studio_id) }}";
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

const HOURS = []; // 08:00 .. 23:00 (16 rows, last row covers 23:00-23:30)
for (let h = 8; h <= 23; h++) HOURS.push(h);

let weekStart = getMonday(new Date());

function getMonday(d) {
    const date = new Date(d);
    const day = date.getDay();
    const diff = (day === 0 ? -6 : 1 - day); // shift Sunday to end of week
    date.setDate(date.getDate() + diff);
    date.setHours(0, 0, 0, 0);
    return date;
}

function formatDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function addDays(d, n) {
    const date = new Date(d);
    date.setDate(date.getDate() + n);
    return date;
}

function weekDates() {
    return Array.from({ length: 7 }, (_, i) => addDays(weekStart, i));
}

function showAlert(message, type) {
    const box = document.getElementById('calendar-alert');
    box.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show">${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
}

function renderHeader() {
    const head = document.getElementById('calendar-head');
    const dates = weekDates();
    const opts = { weekday: 'short', day: 'numeric', month: 'short' };

    head.innerHTML = '<th class="time-col">Time</th>' +
        dates.map(d => `<th>${d.toLocaleDateString(undefined, opts)}</th>`).join('');

    const last = dates[6];
    document.getElementById('week-label').textContent =
        `${formatDate(weekStart)} – ${formatDate(last)}`;
}

function renderGrid() {
    const body = document.getElementById('calendar-body');
    const dates = weekDates();

    let html = '';
    HOURS.forEach(hour => {
        const label = `${String(hour).padStart(2, '0')}:00`;
        html += `<tr><td class="time-col">${label}</td>`;
        dates.forEach(d => {
            const dateStr = formatDate(d);
            html += `<td class="cal-cell" data-date="${dateStr}" data-hour="${hour}"></td>`;
        });
        html += '</tr>';
    });
    body.innerHTML = html;

    body.querySelectorAll('.cal-cell').forEach(cell => {
        cell.addEventListener('click', () => onCellClick(cell));
    });
}

function loadEvents() {
    const dates = weekDates();
    const start = formatDate(dates[0]);
    const end = formatDate(dates[6]);

    fetch(`${calendarDataUrl}?start=${start}&end=${end}`)
        .then(res => res.json())
        .then(data => applyEvents(data.events || []));
}

function applyEvents(events) {
    document.querySelectorAll('.cal-cell').forEach(cell => {
        cell.classList.remove('has-event', 'removable');
        cell.innerHTML = '';
        cell.dataset.eventId = '';
        cell.dataset.eventType = '';
    });

    events.forEach(ev => {
        const start = new Date(ev.start.replace(' ', 'T'));
        const end = new Date(ev.end.replace(' ', 'T'));

        HOURS.forEach(hour => {
            weekDates().forEach(d => {
                const cellStart = new Date(d);
                cellStart.setHours(hour, 0, 0, 0);
                const cellEnd = new Date(cellStart);
                cellEnd.setHours(hour + 1, 0, 0, 0);

                if (start < cellEnd && end > cellStart) {
                    const dateStr = formatDate(d);
                    const cell = document.querySelector(`.cal-cell[data-date="${dateStr}"][data-hour="${hour}"]`);
                    if (!cell) return;

                    cell.classList.add('has-event');
                    cell.innerHTML = `<div class="cal-event" style="background:${ev.color};" title="${ev.title}">${ev.title}</div>`;
                    cell.dataset.eventId = ev.id;
                    cell.dataset.eventType = ev.type;

                    if (ev.type === 'maintenance' || ev.type === 'blocked') {
                        cell.classList.add('removable');
                    }
                }
            });
        });
    });
}

function onCellClick(cell) {
    if (cell.classList.contains('has-event')) {
        if (!cell.classList.contains('removable')) return;

        if (!confirm(`Remove this ${cell.dataset.eventType} period?`)) return;

        const periodId = cell.dataset.eventId.replace('unavailability-', '');

        fetch(`/staff/studios/${studioId}/unavailability/${periodId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('Period removed.', 'success');
                    loadEvents();
                }
            });

        return;
    }

    openModal(cell.dataset.date, parseInt(cell.dataset.hour, 10));
}

function openModal(dateStr, hour) {
    document.getElementById('period-error').style.display = 'none';
    document.getElementById('period-type').value = 'maintenance';
    document.getElementById('period-reason').value = '';

    const pad = n => String(n).padStart(2, '0');
    const startStr = `${dateStr}T${pad(hour)}:00`;
    const endHour = Math.min(hour + 1, 23);
    const endStr = hour < 23 ? `${dateStr}T${pad(endHour)}:00` : `${dateStr}T23:30`;

    document.getElementById('period-start').value = startStr;
    document.getElementById('period-end').value = endStr;

    document.getElementById('period-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('period-modal').style.display = 'none';
}

document.getElementById('period-cancel').addEventListener('click', closeModal);

document.getElementById('period-save').addEventListener('click', () => {
    const type = document.getElementById('period-type').value;
    const start = document.getElementById('period-start').value;
    const end = document.getElementById('period-end').value;
    const reason = document.getElementById('period-reason').value;
    const errorBox = document.getElementById('period-error');

    fetch(unavailabilityStoreUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            type: type,
            start_at: start.replace('T', ' '),
            end_at: end.replace('T', ' '),
            reason: reason,
        }),
    })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw data;
            return data;
        })
        .then(() => {
            closeModal();
            showAlert('Period scheduled successfully.', 'success');
            loadEvents();
        })
        .catch(err => {
            const messages = err.errors ? Object.values(err.errors).flat() : [err.message || 'Something went wrong.'];
            errorBox.innerHTML = messages.join('<br>');
            errorBox.style.display = 'block';
        });
});

document.getElementById('prev-week').addEventListener('click', () => {
    weekStart = addDays(weekStart, -7);
    refresh();
});
document.getElementById('next-week').addEventListener('click', () => {
    weekStart = addDays(weekStart, 7);
    refresh();
});

function refresh() {
    renderHeader();
    renderGrid();
    loadEvents();
}

refresh();
</script>

@endsection
