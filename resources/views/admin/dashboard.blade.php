@extends('layouts.app')

@section('page-title', 'Admin Analytics Dashboard')

@section('content')

{{-- KPI CARDS --}}
<div style="display:grid; grid-template-columns: repeat(4,1fr); gap:16px; margin-bottom:24px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#E6F1FB;">
                <span style="font-size:20px;">📅</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $kpiActiveBookings }}</h4>
                <small class="text-muted">Active bookings this month</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#F3E8FF;">
                <span style="font-size:20px;">🏢</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $kpiUtilizationRate }}%</h4>
                <small class="text-muted">Studio utilization rate (this month)</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FBE3E3;">
                <span style="font-size:20px;">🔧</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $kpiDamagedInstruments }}</h4>
                <small class="text-muted">Instruments damaged / under maintenance</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FAEEDA;">
                <span style="font-size:20px;">👥</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $kpiActiveUsers }}</h4>
                <small class="text-muted">Active users (students + staff)</small>
            </div>
        </div>
    </div>
</div>

{{-- RESERVATION KPI CARDS --}}
<div style="display:grid; grid-template-columns: repeat(4,1fr); gap:16px; margin-bottom:24px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FEF3C7;">
                <span style="font-size:20px;">⏳</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $kpiPendingReservations }}</h4>
                <small class="text-muted">Pending reservations</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#D1FAE5;">
                <span style="font-size:20px;">📦</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $kpiUpcomingPickups }}</h4>
                <small class="text-muted">Upcoming pickups</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#DBEAFE;">
                <span style="font-size:20px;">🎒</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $kpiItemsCollected }}</h4>
                <small class="text-muted">Items currently collected</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FBE3E3;">
                <span style="font-size:20px;">⚠️</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $kpiOverdueReturns }}</h4>
                <small class="text-muted">Overdue returns</small>
            </div>
        </div>
    </div>
</div>

{{-- FILTERS --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.dashboard') }}">
            <div style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
                <div>
                    <label class="form-label" style="font-size:11px; margin-bottom:2px;">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" style="font-size:13px;">
                </div>
                <div>
                    <label class="form-label" style="font-size:11px; margin-bottom:2px;">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" style="font-size:13px;">
                </div>
                <div>
                    <label class="form-label" style="font-size:11px; margin-bottom:2px;">Instrument Category</label>
                    <select name="category_id" class="form-control" style="font-size:13px;">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->category_id }}" {{ ($filters['category_id'] ?? '') == $category->category_id ? 'selected' : '' }}>{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-sm" style="background:#7C3AED;color:#fff;border-radius:8px;">Apply</button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Reset</a>
                </div>
            </div>
            @error('date_to')
                <div class="text-danger" style="font-size:12px; margin-top:6px;">{{ $message }}</div>
            @enderror
        </form>
    </div>
</div>

{{-- CHARTS --}}
<div style="display:grid; grid-template-columns: 2fr 1fr; gap:16px; margin-bottom:16px;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <span style="font-weight:600;">📈 Most Borrowed Instruments by Month</span>
        </div>
        <div class="card-body">
            <canvas id="borrowedChart" height="260"></canvas>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <span style="font-weight:600;">🏢 Studio Utilization Rate</span>
        </div>
        <div class="card-body">
            <canvas id="utilizationChart" height="260"></canvas>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr; gap:16px; margin-bottom:16px;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <span style="font-weight:600;">🩺 Instrument Health &amp; Damage Status</span>
        </div>
        <div class="card-body">
            <div style="max-width:320px; margin:0 auto;">
                <canvas id="healthChart" height="260"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- RECENT ACTIVITIES --}}
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white border-bottom">
        <span style="font-weight:600;">🕒 Recent Activities</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">Type</th>
                    <th style="font-size:12px;">Date</th>
                    <th style="font-size:12px;">Description</th>
                    <th style="font-size:12px;">Status</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse($recentActivities as $activity)
                    <tr>
                        <td>
                            @if($activity['type'] === 'Booking')
                                <span class="badge" style="background:#E3EEFD;color:#0B4D8C;">📅 Booking</span>
                            @else
                                <span class="badge" style="background:#FFEDD5;color:#9A3412;">🔧 Maintenance</span>
                            @endif
                        </td>
                        <td>{{ $activity['date']->format('d M Y') }}</td>
                        <td>{{ $activity['description'] }}</td>
                        <td>
                            @php
                                $badgeColors = [
                                    'Confirmed' => ['#D1FAE5', '#065F46'],
                                    'Completed' => ['#E3EEFD', '#0B4D8C'],
                                    'Cancelled' => ['#FEE2E2', '#991B1B'],
                                    'Pending'   => ['#FAEEDA', '#8C5A0B'],
                                ];
                                [$bg, $fg] = $badgeColors[$activity['status']] ?? ['#eee', '#555'];
                            @endphp
                            <span class="badge" style="background:{{ $bg }};color:{{ $fg }};">{{ $activity['status'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No recent activity recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const borrowedChartLabels = @json($borrowedChartLabels);
    const borrowedChartDatasets = @json($borrowedChartDatasets);
    const utilizationChartLabels = @json($utilizationChartLabels);
    const utilizationChartData = @json($utilizationChartData);
    const healthChartLabels = @json($healthChartLabels);
    const healthChartData = @json($healthChartData);

const palette = [
    '#8B5CF6', // Caklempong (Purple)
    '#3B82F6', // Dance (Blue)
    '#F59E0B', // Gamelan (Gold)
    '#10B981', // Music (Green)
    '#6366F1', // Extra
    '#14B8A6'  // Extra
];

    new Chart(document.getElementById('borrowedChart'), {
        type: 'bar',
        data: {
            labels: borrowedChartLabels,
            datasets: borrowedChartDatasets.map((dataset, index) => ({
                label: dataset.label,
                data: dataset.data,
                backgroundColor: palette[index % palette.length],
            })),
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
            },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            },
        },
    });

    new Chart(document.getElementById('utilizationChart'), {
        type: 'doughnut',
        data: {
            labels: utilizationChartLabels,
            datasets: [{
                data: utilizationChartData,
                backgroundColor: palette,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            },
        },
    });

    new Chart(document.getElementById('healthChart'), {
        type: 'doughnut',
        data: {
            labels: healthChartLabels,
            datasets: [{
                data: healthChartData,
                backgroundColor: ['#10B981', '#8C1F1F'],
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            },
        },
    });
</script>

@endsection
