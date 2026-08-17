@extends('layouts.app')

@section('page-title', 'Reports')

@section('content')

@php
    $bookingStatuses   = ['confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    $borrowingStatuses = ['pending' => 'Pending', 'reserved' => 'Reserved', 'collected' => 'Collected', 'returned' => 'Returned', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'];
    $itemStatuses      = ['available' => 'Available', 'unavailable' => 'Unavailable'];
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- AT-A-GLANCE KPI HEADER                                                 --}}
{{-- Sources: sp_pending_reservations(), is_overdue trigger column,         --}}
{{--          v_inventory_low_stock, sp_maintenance_instruments_report(),   --}}
{{--          sp_active_users_summary()                                     --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns: repeat(5,1fr); gap:12px; margin-bottom:20px;">

    <div class="card border-0 shadow-sm" style="border-left:4px solid #F59E0B;">
        <div class="card-body py-3 px-3">
            <div style="font-size:11px; color:#888; margin-bottom:2px;">Pending Reservations</div>
            @if($atAGlance['kpi_pending_reservations'] > 0)
                <div style="font-size:26px; font-weight:700; color:#92400E;">{{ $atAGlance['kpi_pending_reservations'] }}</div>
            @else
                <div style="font-size:26px; font-weight:700; color:#1f2937;">{{ $atAGlance['kpi_pending_reservations'] }}</div>
            @endif
            <div style="font-size:10px; color:#bbb; margin-top:4px;">pending borrowing requests</div>
        </div>
    </div>

    @if($atAGlance['kpi_overdue_borrowings'] > 0)
    <div class="card border-0 shadow-sm" style="border-left:4px solid #DC2626;">
    @else
    <div class="card border-0 shadow-sm" style="border-left:4px solid #d1d5db;">
    @endif
        <div class="card-body py-3 px-3">
            <div style="font-size:11px; color:#888; margin-bottom:2px;">Overdue Borrowings</div>
            @if($atAGlance['kpi_overdue_borrowings'] > 0)
                <div style="font-size:26px; font-weight:700; color:#991B1B;">{{ $atAGlance['kpi_overdue_borrowings'] }}</div>
            @else
                <div style="font-size:26px; font-weight:700; color:#1f2937;">{{ $atAGlance['kpi_overdue_borrowings'] }}</div>
            @endif
            <div style="font-size:10px; color:#bbb; margin-top:4px;">auto-tracked per borrow</div>
        </div>
    </div>

    @if($atAGlance['kpi_low_stock_items'] > 0)
    <div class="card border-0 shadow-sm" style="border-left:4px solid #F59E0B;">
    @else
    <div class="card border-0 shadow-sm" style="border-left:4px solid #d1d5db;">
    @endif
        <div class="card-body py-3 px-3">
            <div style="font-size:11px; color:#888; margin-bottom:2px;">Low Stock Items</div>
            @if($atAGlance['kpi_low_stock_items'] > 0)
                <div style="font-size:26px; font-weight:700; color:#92400E;">{{ $atAGlance['kpi_low_stock_items'] }}</div>
            @else
                <div style="font-size:26px; font-weight:700; color:#1f2937;">{{ $atAGlance['kpi_low_stock_items'] }}</div>
            @endif
            <div style="font-size:10px; color:#bbb; margin-top:4px;">below availability threshold</div>
        </div>
    </div>

    @if($atAGlance['kpi_pending_maintenance'] > 0)
    <div class="card border-0 shadow-sm" style="border-left:4px solid #DC2626;">
    @else
    <div class="card border-0 shadow-sm" style="border-left:4px solid #d1d5db;">
    @endif
        <div class="card-body py-3 px-3">
            <div style="font-size:11px; color:#888; margin-bottom:2px;">Pending Maintenance</div>
            @if($atAGlance['kpi_pending_maintenance'] > 0)
                <div style="font-size:26px; font-weight:700; color:#991B1B;">{{ $atAGlance['kpi_pending_maintenance'] }}</div>
            @else
                <div style="font-size:26px; font-weight:700; color:#1f2937;">{{ $atAGlance['kpi_pending_maintenance'] }}</div>
            @endif
            <div style="font-size:10px; color:#bbb; margin-top:4px;">open maintenance issues</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-left:4px solid #10B981;">
        <div class="card-body py-3 px-3">
            <div style="font-size:11px; color:#888; margin-bottom:2px;">Active Users (30d)</div>
            <div style="font-size:26px; font-weight:700; color:#065F46;">
                {{ $atAGlance['kpi_active_users_30d'] }}
            </div>
            <div style="font-size:10px; color:#bbb; margin-top:4px;">students &amp; staff in 30 days</div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- TWO-COLUMN LAYOUT: SIDEBAR + MAIN CONTENT                              --}}
{{-- @if/@else on each link avoids {{ }} inside style="" (no CSS linter)    --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="d-flex gap-3 align-items-start mt-3">

{{-- REPORT NAVIGATION SIDEBAR --}}
<div style="width:210px; flex-shrink:0; position:sticky; top:72px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-2">

            {{-- Analytics group --}}
            <div style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af; padding:6px 8px 4px;">Analytics</div>

            @if($type === 'utilization')
            <a href="{{ route('admin.reports.index', ['type' => 'utilization']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="background:#7C3AED; color:#fff; font-weight:600; font-size:13px;">🏢 Studio Utilization</a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'utilization']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="color:#374151; font-size:13px;">🏢 Studio Utilization</a>
            @endif

            @if($type === 'user-engagement')
            <a href="{{ route('admin.reports.index', ['type' => 'user-engagement']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="background:#7C3AED; color:#fff; font-weight:600; font-size:13px;">👥 User Engagement</a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'user-engagement']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="color:#374151; font-size:13px;">👥 User Engagement</a>
            @endif

            <hr class="my-2">

            {{-- Operations group --}}
            <div style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af; padding:2px 8px 4px;">Operations</div>

            @if($type === 'pending-queue')
            <a href="{{ route('admin.reports.index', ['type' => 'pending-queue']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="background:#7C3AED; color:#fff; font-weight:600; font-size:13px;">⏳ Pending Reservations
                @if($atAGlance['kpi_pending_reservations'] > 0)<span class="badge ms-auto" style="background:rgba(255,255,255,0.3); color:#fff; font-size:10px; padding:1px 5px; border-radius:99px;">{{ $atAGlance['kpi_pending_reservations'] }}</span>@endif
            </a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'pending-queue']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="color:#374151; font-size:13px;">⏳ Pending Reservations
                @if($atAGlance['kpi_pending_reservations'] > 0)<span class="badge ms-auto" style="background:#F59E0B; color:#fff; font-size:10px; padding:1px 5px; border-radius:99px;">{{ $atAGlance['kpi_pending_reservations'] }}</span>@endif
            </a>
            @endif

            @if($type === 'collections')
            <a href="{{ route('admin.reports.index', ['type' => 'collections']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="background:#7C3AED; color:#fff; font-weight:600; font-size:13px;">📦 Collections &amp; Returns</a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'collections']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="color:#374151; font-size:13px;">📦 Collections &amp; Returns</a>
            @endif

            <hr class="my-2">

            {{-- Standard Reports group --}}
            <div style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af; padding:2px 8px 4px;">Standard Reports</div>

            @if($type === 'bookings')
            <a href="{{ route('admin.reports.index', ['type' => 'bookings']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="background:#7C3AED; color:#fff; font-weight:600; font-size:13px;">📅 Bookings</a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'bookings']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="color:#374151; font-size:13px;">📅 Bookings</a>
            @endif

            @if($type === 'borrowings')
            <a href="{{ route('admin.reports.index', ['type' => 'borrowings']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="background:#7C3AED; color:#fff; font-weight:600; font-size:13px;">📋 Borrowings
                @if($atAGlance['kpi_overdue_borrowings'] > 0)<span class="badge ms-auto" style="background:rgba(255,255,255,0.3); color:#fff; font-size:10px; padding:1px 5px; border-radius:99px;">{{ $atAGlance['kpi_overdue_borrowings'] }}</span>@endif
            </a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'borrowings']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="color:#374151; font-size:13px;">📋 Borrowings
                @if($atAGlance['kpi_overdue_borrowings'] > 0)<span class="badge ms-auto" style="background:#DC2626; color:#fff; font-size:10px; padding:1px 5px; border-radius:99px;">{{ $atAGlance['kpi_overdue_borrowings'] }}</span>@endif
            </a>
            @endif

            @if($type === 'inventory')
            <a href="{{ route('admin.reports.index', ['type' => 'inventory']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="background:#7C3AED; color:#fff; font-weight:600; font-size:13px;">📦 Inventory
                @if($atAGlance['kpi_low_stock_items'] > 0)<span class="badge ms-auto" style="background:rgba(255,255,255,0.3); color:#fff; font-size:10px; padding:1px 5px; border-radius:99px;">{{ $atAGlance['kpi_low_stock_items'] }}</span>@endif
            </a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'inventory']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="color:#374151; font-size:13px;">📦 Inventory
                @if($atAGlance['kpi_low_stock_items'] > 0)<span class="badge ms-auto" style="background:#F59E0B; color:#fff; font-size:10px; padding:1px 5px; border-radius:99px;">{{ $atAGlance['kpi_low_stock_items'] }}</span>@endif
            </a>
            @endif

            @if($type === 'maintenance')
            <a href="{{ route('admin.reports.index', ['type' => 'maintenance']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="background:#7C3AED; color:#fff; font-weight:600; font-size:13px;">🔧 Maintenance
                @if($atAGlance['kpi_pending_maintenance'] > 0)<span class="badge ms-auto" style="background:rgba(255,255,255,0.3); color:#fff; font-size:10px; padding:1px 5px; border-radius:99px;">{{ $atAGlance['kpi_pending_maintenance'] }}</span>@endif
            </a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'maintenance']) }}" class="d-flex align-items-center px-2 py-2 rounded text-decoration-none mb-1" style="color:#374151; font-size:13px;">🔧 Maintenance
                @if($atAGlance['kpi_pending_maintenance'] > 0)<span class="badge ms-auto" style="background:#DC2626; color:#fff; font-size:10px; padding:1px 5px; border-radius:99px;">{{ $atAGlance['kpi_pending_maintenance'] }}</span>@endif
            </a>
            @endif

        </div>
    </div>
</div>

{{-- MAIN CONTENT AREA --}}
<div style="flex:1; min-width:0;">

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- STANDARD REPORT CONTENT (Bookings / Borrowings / Inventory)            --}}
{{-- UNCHANGED — exact original code preserved                              --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@if(in_array($type, ['bookings', 'borrowings', 'inventory']))

{{-- OVERDUE BORROWING ALERT BANNER --}}
{{-- Source: is_overdue column maintained by trg_borrowings_set_overdue BEFORE INSERT OR UPDATE trigger --}}
@if($type === 'borrowings' && ($overdueCount ?? 0) > 0)
<div class="d-flex align-items-center mb-3" style="background:#FEF3C7; border:1px solid #F59E0B; border-radius:10px; padding:12px 16px;">
    <span style="font-size:20px; margin-right:12px;">⚠️</span>
    <div style="flex:1;">
        <strong style="font-size:13px; color:#92400E;">
            {{ $overdueCount }} borrowing{{ $overdueCount > 1 ? 's are' : ' is' }} currently overdue.
        </strong>
        <span style="font-size:12px; color:#78350F; margin-left:4px;">Items not returned past the due date.</span>
    </div>
    <a href="{{ route('admin.reports.index', ['type' => 'borrowings', 'overdue_only' => '1']) }}"
       class="btn btn-sm ms-3" style="background:#DC2626; color:#fff; border-radius:8px; font-size:12px; white-space:nowrap;">
        View Overdue →
    </a>
</div>
@endif

{{-- FILTERS --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.reports.index') }}">
            <input type="hidden" name="type" value="{{ $type }}">
            {{-- DATE RANGE PRESETS --}}
            <div style="display:flex; gap:5px; align-items:center; flex-wrap:wrap; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #f3f4f6;">
                <span style="font-size:11px; color:#9ca3af; min-width:44px;">Preset:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="today"   style="border-radius:8px; font-size:12px; padding:2px 10px;">Today</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="week"    style="border-radius:8px; font-size:12px; padding:2px 10px;">This Week</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="month"   style="border-radius:8px; font-size:12px; padding:2px 10px;">This Month</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="3months" style="border-radius:8px; font-size:12px; padding:2px 10px;">Last 3 Months</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="year"    style="border-radius:8px; font-size:12px; padding:2px 10px;">This Year</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="custom"  style="border-radius:8px; font-size:12px; padding:2px 10px;">Custom</button>
            </div>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">

                @if($type === 'bookings' || $type === 'borrowings')
                    <div>
                        <label class="form-label" style="font-size:11px; margin-bottom:2px;">From</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" style="font-size:13px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11px; margin-bottom:2px;">To</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" style="font-size:13px;">
                    </div>
                @endif

                @if($type === 'bookings')
                    <div>
                        <label class="form-label" style="font-size:11px; margin-bottom:2px;">Status</label>
                        <select name="status" class="form-control" style="font-size:13px;">
                            <option value="">All statuses</option>
                            @foreach($bookingStatuses as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['status'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11px; margin-bottom:2px;">Studio</label>
                        <select name="studio_id" class="form-control" style="font-size:13px;">
                            <option value="">All studios</option>
                            @foreach($studios as $studio)
                                <option value="{{ $studio->studio_id }}" {{ ($filters['studio_id'] ?? '') == $studio->studio_id ? 'selected' : '' }}>{{ $studio->studio_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($type === 'borrowings')
                    <div>
                        <label class="form-label" style="font-size:11px; margin-bottom:2px;">Status</label>
                        <select name="status" class="form-control" style="font-size:13px;">
                            <option value="">All statuses</option>
                            @foreach($borrowingStatuses as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['status'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($type === 'inventory')
                    <div>
                        <label class="form-label" style="font-size:11px; margin-bottom:2px;">Category</label>
                        <select name="category_id" class="form-control" style="font-size:13px;">
                            <option value="">All categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->category_id }}" {{ ($filters['category_id'] ?? '') == $category->category_id ? 'selected' : '' }}>{{ $category->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11px; margin-bottom:2px;">Status</label>
                        <select name="status" class="form-control" style="font-size:13px;">
                            <option value="">All statuses</option>
                            @foreach($itemStatuses as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['status'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11px; margin-bottom:2px;">Added from</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" style="font-size:13px;">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11px; margin-bottom:2px;">Added to</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" style="font-size:13px;">
                    </div>
                @endif

                <div style="margin-top: 18px;">
                    <button type="submit" class="btn btn-sm" style="background:#7C3AED;color:#fff;border-radius:8px;">Apply</button>
                    <a href="{{ route('admin.reports.index', ['type' => $type]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Reset</a>
                </div>
            </div>
            @error('date_to')
                <div class="text-danger" style="font-size:12px; margin-top:6px;">{{ $message }}</div>
            @enderror
        </form>

        <div style="margin-top:10px; display:flex; gap:8px;">
            <a href="{{ route('admin.reports.export.pdf', array_merge($filters, ['type' => $type])) }}" class="btn btn-sm" style="background:#DC2626;color:#fff;border-radius:8px;">
                📄 Export PDF
            </a>
            <a href="{{ route('admin.reports.export.csv', array_merge($filters, ['type' => $type])) }}" class="btn btn-sm" style="background:#059669;color:#fff;border-radius:8px;">
                📊 Export CSV
            </a>
        </div>

        {{-- QUICK FILTER BUTTONS (borrowings tab only) --}}
        @if($type === 'borrowings')
        @php
            $isOverdueActive  = !empty($filters['overdue_only']);
            $isActiveActive   = (($filters['status'] ?? '') === 'collected') && empty($filters['overdue_only']);
            $isReturnedActive = ($filters['status'] ?? '') === 'returned';
            $isAllActive      = !$isOverdueActive && !$isActiveActive && !$isReturnedActive;
        @endphp
        <div style="margin-top:8px; padding-top:8px; border-top:1px solid #f3f4f6; display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
            <span style="font-size:11px; color:#9ca3af; min-width:64px;">Quick filter:</span>

            @if($isAllActive)
            <a href="{{ route('admin.reports.index', ['type' => 'borrowings']) }}" class="btn btn-sm" style="background:#7C3AED; color:#fff; border-radius:8px; font-size:12px;">All</a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'borrowings']) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; font-size:12px;">All</a>
            @endif

            @if($isOverdueActive)
            <a href="{{ route('admin.reports.index', ['type' => 'borrowings', 'overdue_only' => '1']) }}" class="btn btn-sm" style="background:#DC2626; color:#fff; border-radius:8px; font-size:12px;">⚠ Overdue
                @if(($overdueCount ?? 0) > 0)<span class="badge" style="background:rgba(255,255,255,0.25); font-size:10px; padding:1px 4px; border-radius:99px; margin-left:2px;">{{ $overdueCount }}</span>@endif
            </a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'borrowings', 'overdue_only' => '1']) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; font-size:12px;">⚠ Overdue
                @if(($overdueCount ?? 0) > 0)<span class="badge" style="background:rgba(0,0,0,0.1); font-size:10px; padding:1px 4px; border-radius:99px; margin-left:2px;">{{ $overdueCount }}</span>@endif
            </a>
            @endif

            @if($isActiveActive)
            <a href="{{ route('admin.reports.index', ['type' => 'borrowings', 'status' => 'collected']) }}" class="btn btn-sm" style="background:#7C3AED; color:#fff; border-radius:8px; font-size:12px;">Active</a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'borrowings', 'status' => 'collected']) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; font-size:12px;">Active</a>
            @endif

            @if($isReturnedActive)
            <a href="{{ route('admin.reports.index', ['type' => 'borrowings', 'status' => 'returned']) }}" class="btn btn-sm" style="background:#7C3AED; color:#fff; border-radius:8px; font-size:12px;">Returned</a>
            @else
            <a href="{{ route('admin.reports.index', ['type' => 'borrowings', 'status' => 'returned']) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; font-size:12px;">Returned</a>
            @endif

        </div>
        @endif
    </div>
</div>

{{-- SUMMARY CARDS --}}
<div class="row mb-3">
    @if($type === 'bookings')
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Total Bookings</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['total'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Confirmed</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['confirmed'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Completed</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['completed'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Cancelled</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['cancelled'] }}</div>
            </div></div>
        </div>
    @endif

    @if($type === 'borrowings')
        <div class="col-md-2 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Total Requests</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['total'] }}</div>
            </div></div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Pending</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['pending'] }}</div>
            </div></div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Reserved</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['reserved'] }}</div>
            </div></div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Collected</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['collected'] }}</div>
            </div></div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Returned</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['returned'] }}</div>
            </div></div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Rejected / Cancelled</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['rejected'] + $summary['cancelled'] }}</div>
            </div></div>
        </div>
        <div class="col-md-2 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Items Borrowed</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['items_borrowed'] }}</div>
            </div></div>
        </div>
    @endif

    @if($type === 'inventory')
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Total Items</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['total_items'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Total Quantity</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['total_quantity'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Available Quantity</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['total_available'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm"><div class="card-body py-3">
                <div style="font-size:12px;color:#888;">Low Stock (&le;20%)</div>
                <div style="font-size:22px;font-weight:700;">{{ $summary['low_stock'] }}</div>
            </div></div>
        </div>
    @endif
</div>

{{-- RESULTS TABLE --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">
            @if($type === 'bookings') 📅 Booking Report
            @elseif($type === 'borrowings') 📋 Borrowing Report
            @else 📦 Inventory Report
            @endif
        </span>
        <span class="text-muted" style="font-size:12px;">{{ $results->total() }} record(s)</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            @if($type === 'bookings')
                <thead class="table-light">
                    <tr>
                        <th style="font-size:12px;">#</th>
                        <th style="font-size:12px;">Date</th>
                        <th style="font-size:12px;">Time</th>
                        <th style="font-size:12px;">Studio</th>
                        <th style="font-size:12px;">Student</th>
                        <th style="font-size:12px;">Status</th>
                        <th style="font-size:12px;">Purpose</th>
                    </tr>
                </thead>
                <tbody style="font-size:13px;">
                    @forelse($results as $booking)
                        <tr>
                            <td>{{ $booking->booking_id }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d M Y') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('H:i') }} - {{ \Illuminate\Support\Carbon::parse($booking->end_time)->format('H:i') }}</td>
                            <td>{{ $booking->studio->studio_name ?? '-' }}</td>
                            <td>{{ $booking->student->full_name ?? '-' }}</td>
                            <td>
                                @if($booking->booking_status === 'confirmed')
                                <span class="badge" style="background:#D1FAE5; color:#065F46;">Confirmed</span>
                                @elseif($booking->booking_status === 'completed')
                                <span class="badge" style="background:#DBEAFE; color:#1E40AF;">Completed</span>
                                @elseif($booking->booking_status === 'cancelled')
                                <span class="badge" style="background:#FEE2E2; color:#991B1B;">Cancelled</span>
                                @else
                                <span class="badge" style="background:#eee; color:#555;">{{ ucfirst($booking->booking_status) }}</span>
                                @endif
                            </td>
                            <td>{{ Str::limit($booking->purpose ?? '-', 40) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No bookings found for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            @elseif($type === 'borrowings')
                <thead class="table-light">
                    <tr>
                        <th style="font-size:12px;">#</th>
                        <th style="font-size:12px;">Pickup Date</th>
                        <th style="font-size:12px;">Return Date</th>
                        <th style="font-size:12px;">Student</th>
                        <th style="font-size:12px;">Items</th>
                        <th style="font-size:12px;">Status</th>
                        <th style="font-size:12px;">Collected</th>
                        <th style="font-size:12px;">Returned</th>
                    </tr>
                </thead>
                <tbody style="font-size:13px;">
                    @forelse($results as $borrowing)
                        <tr>
                            <td>{{ $borrowing->borrow_id }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($borrowing->pickup_date)->format('d M Y') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($borrowing->return_date)->format('d M Y') }}</td>
                            <td>{{ $borrowing->student->full_name ?? '-' }}</td>
                            <td>{{ $borrowing->borrowingDetails->map(fn($d) => ($d->item->item_name ?? '-') . ' (x' . $d->quantity_borrowed . ')')->join(', ') }}</td>
                            <td>
                                <x-status-badge :status="$borrowing->borrow_status" />
                                @if($borrowing->is_overdue)
                                    <x-status-badge status="overdue" label="Overdue" />
                                @endif
                            </td>
                            <td>{{ $borrowing->collected_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $borrowing->returned_at?->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No borrowings found for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            @else
                <thead class="table-light">
                    <tr>
                        <th style="font-size:12px;">#</th>
                        <th style="font-size:12px;">Item</th>
                        <th style="font-size:12px;">Category</th>
                        <th style="font-size:12px;">Quantity</th>
                        <th style="font-size:12px;">Available</th>
                        <th style="font-size:12px;">Condition</th>
                        <th style="font-size:12px;">Status</th>
                        <th style="font-size:12px;">Date Added</th>
                    </tr>
                </thead>
                <tbody style="font-size:13px;">
                    @forelse($results as $item)
                        <tr>
                            <td>{{ $item->item_id }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->category->category_name ?? '-' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->available_quantity }}</td>
                            <td>{{ ucfirst($item->condition_status) }}</td>
                            <td>
                                @if($item->item_status === 'available')
                                    <span class="badge" style="background:#D1FAE5;color:#065F46;">Available</span>
                                @else
                                    <span class="badge" style="background:#FEE2E2;color:#991B1B;">Unavailable</span>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Carbon::parse($item->date_added)->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No items found for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            @endif
        </table>
    </div>
    @if($results->hasPages())
        <div class="card-footer bg-white">
            {{ $results->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- LOW STOCK ALERT SECTION (Inventory tab only)                           --}}
{{-- DB object: v_inventory_low_stock (PostgreSQL view)                     --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@if($type === 'inventory')
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center"
         style="border-top:3px solid #F59E0B; cursor:pointer;"
         data-bs-toggle="collapse" data-bs-target="#lowStockCollapse" aria-expanded="true" aria-controls="lowStockCollapse">
        <span style="font-weight:600; color:#92400E;">
            ⚠ Low Stock Alert
            @if(($lowStockItems ?? collect())->isNotEmpty())
                <span class="badge ms-1" style="background:#DC2626; color:#fff; font-size:11px;">{{ ($lowStockItems ?? collect())->count() }}</span>
            @endif
        </span>
        <div style="display:flex; align-items:center; gap:10px;">
            <small class="text-muted" style="font-size:10px;">items below availability threshold</small>
            <span style="font-size:12px; color:#888;">▾</span>
        </div>
    </div>
    <div class="collapse show" id="lowStockCollapse">
        @if(($lowStockItems ?? collect())->isEmpty())
            <div class="card-body text-center py-4">
                <div style="font-size:32px; margin-bottom:8px;">✅</div>
                <p class="text-muted mb-0" style="font-size:13px;">No items are currently low on stock.</p>
                <small class="text-muted" style="font-size:11px;">Items appear here when available quantity falls to ≤ 20% of total.</small>
            </div>
        @else
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="font-size:12px;">Item Name</th>
                            <th style="font-size:12px;">Category</th>
                            <th style="font-size:12px;">Total Qty</th>
                            <th style="font-size:12px;">Available</th>
                            <th style="font-size:12px; width:240px;">Availability</th>
                        </tr>
                    </thead>
                    <tbody style="font-size:13px;">
                        @foreach(($lowStockItems ?? collect()) as $lsItem)
                            @php $pct = (float) ($lsItem->availability_pct ?? 0); @endphp
                            <tr>
                                <td style="font-weight:500;">{{ $lsItem->item_name }}</td>
                                <td>{{ $lsItem->category_name }}</td>
                                <td>{{ $lsItem->quantity }}</td>
                                <td>{{ $lsItem->available_quantity }}</td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="flex:1; background:#f3f4f6; border-radius:4px; height:8px; min-width:100px;">
                                            @if($pct < 20)
                                            <div style="width:{{ min(100, max(0, $pct)) }}%; background:#DC2626; border-radius:4px; height:8px;"></div>
                                            @elseif($pct < 40)
                                            <div style="width:{{ min(100, max(0, $pct)) }}%; background:#F59E0B; border-radius:4px; height:8px;"></div>
                                            @else
                                            <div style="width:{{ min(100, max(0, $pct)) }}%; background:#10B981; border-radius:4px; height:8px;"></div>
                                            @endif
                                        </div>
                                        @if($pct < 20)
                                        <span style="font-size:12px; font-weight:600; min-width:44px; color:#DC2626;">{{ $pct }}%</span>
                                        @elseif($pct < 40)
                                        <span style="font-size:12px; font-weight:600; min-width:44px; color:#F59E0B;">{{ $pct }}%</span>
                                        @else
                                        <span style="font-size:12px; font-weight:600; min-width:44px; color:#10B981;">{{ $pct }}%</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- STUDIO UTILIZATION ANALYTICS                                            --}}
{{-- DB objects: sp_studio_utilization_rate(), v_monthly_booking_summary    --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@elseif($type === 'utilization')

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.reports.index') }}">
            <input type="hidden" name="type" value="utilization">
            {{-- DATE RANGE PRESETS --}}
            <div style="display:flex; gap:5px; align-items:center; flex-wrap:wrap; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #f3f4f6;">
                <span style="font-size:11px; color:#9ca3af; min-width:44px;">Preset:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="today"   style="border-radius:8px; font-size:12px; padding:2px 10px;">Today</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="week"    style="border-radius:8px; font-size:12px; padding:2px 10px;">This Week</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="month"   style="border-radius:8px; font-size:12px; padding:2px 10px;">This Month</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="3months" style="border-radius:8px; font-size:12px; padding:2px 10px;">Last 3 Months</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="year"    style="border-radius:8px; font-size:12px; padding:2px 10px;">This Year</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="custom"  style="border-radius:8px; font-size:12px; padding:2px 10px;">Custom</button>
            </div>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <div>
                    <label class="form-label" style="font-size:11px; margin-bottom:2px;">From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? $utilizationDateFrom }}" class="form-control" style="font-size:13px;">
                </div>
                <div>
                    <label class="form-label" style="font-size:11px; margin-bottom:2px;">To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? $utilizationDateTo }}" class="form-control" style="font-size:13px;">
                </div>
                <div>
                    <label class="form-label" style="font-size:11px; margin-bottom:2px;">
                        Operating hrs/day
                        <span class="text-muted" style="font-size:10px;">(p_daily_hours)</span>
                    </label>
                    <input type="number" name="operating_hours" value="{{ $operatingHours }}" min="1" max="24" class="form-control" style="font-size:13px; width:90px;">
                </div>
                <div style="margin-top:18px;">
                    <button type="submit" class="btn btn-sm" style="background:#7C3AED;color:#fff;border-radius:8px;">Apply</button>
                    <a href="{{ route('admin.reports.index', ['type' => 'utilization']) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Reset</a>
                </div>
            </div>
            @error('date_to')
                <div class="text-danger" style="font-size:12px; margin-top:6px;">{{ $message }}</div>
            @enderror
        </form>
    </div>
</div>

{{-- Summary KPI Cards --}}
<div style="display:grid; grid-template-columns: repeat(4,1fr); gap:12px; margin-bottom:20px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Top Studio</div>
            <div style="font-size:18px; font-weight:700; color:#7C3AED;">{{ $utilizationSummary['top_studio_name'] }}</div>
            <div style="font-size:12px; color:#888;">{{ $utilizationSummary['top_studio_rate'] }}% utilization</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Total Booked Hours</div>
            <div style="font-size:26px; font-weight:700;">{{ $utilizationSummary['total_booked_hours'] }}h</div>
            <div style="font-size:11px; color:#bbb;">across all studios</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Total Bookings</div>
            <div style="font-size:26px; font-weight:700;">{{ $utilizationSummary['total_bookings'] }}</div>
            <div style="font-size:11px; color:#bbb;">confirmed + completed</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Operating Hours/Day</div>
            <div style="font-size:26px; font-weight:700;">{{ $operatingHours }}h</div>
            <div style="font-size:11px; color:#bbb;">p_daily_hours parameter</div>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:20px;">

    {{-- Horizontal Bar: Utilization rate per studio --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <span style="font-weight:600;">📊 Studio Utilization Rate (%)</span>
        </div>
        <div class="card-body">
            @if($utilizationRows->isEmpty())
                <p class="text-muted text-center py-4">No booking data for the selected date range.</p>
            @else
                <canvas id="utilizationRateChart" height="220"></canvas>
            @endif
        </div>
    </div>

    {{-- Grouped Bar: Monthly bookings trend by studio --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <span style="font-weight:600;">📈 Monthly Booking Trend by Studio</span>
        </div>
        <div class="card-body">
            @if($utilizationTrendLabels->isEmpty())
                <p class="text-muted text-center py-4">No monthly data available for the selected range.</p>
            @else
                <canvas id="utilizationTrendChart" height="220"></canvas>
            @endif
        </div>
    </div>

</div>

{{-- Utilization Detail Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">🏢 Studio Utilization Detail</span>
        <span class="text-muted" style="font-size:12px;">{{ $utilizationRows->count() }} studio(s) &nbsp;·&nbsp; Period: {{ \Illuminate\Support\Carbon::parse($utilizationDateFrom)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($utilizationDateTo)->format('d M Y') }}</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">Studio</th>
                    <th style="font-size:12px;">Type</th>
                    <th style="font-size:12px;">Total Bookings</th>
                    <th style="font-size:12px;">Booked Hours</th>
                    <th style="font-size:12px;">Available Hours</th>
                    <th style="font-size:12px; width:220px;">Utilization Rate</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse($utilizationRows->sortByDesc('utilization_rate') as $row)
                    <tr>
                        <td style="font-weight:500;">
                            <a href="{{ route('admin.reports.index', ['type' => 'bookings', 'studio_id' => $row->studio_id]) }}" class="text-decoration-none" style="color:#7C3AED; font-weight:500;">{{ $row->studio_name }} ↗</a>
                        </td>
                        <td>
                            <span class="badge" style="background:#F3E8FF; color:#5B21B6;">{{ ucfirst($row->studio_type ?? '-') }}</span>
                        </td>
                        <td>{{ $row->total_bookings }}</td>
                        <td>{{ round((float)$row->booked_hours, 1) }}h</td>
                        <td>{{ round((float)$row->available_hours, 1) }}h</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="flex:1; background:#f3f4f6; border-radius:4px; height:8px; min-width:80px;">
                                    <div style="width:{{ min(100, max(0, (float)$row->utilization_rate)) }}%; background:#7C3AED; border-radius:4px; height:8px;"></div>
                                </div>
                                <span style="font-size:12px; font-weight:600; min-width:44px;">{{ $row->utilization_rate }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No studio data for the selected date range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MAINTENANCE & EQUIPMENT HEALTH                                          --}}
{{-- DB objects: sp_maintenance_alerts(), sp_maintenance_instruments_report  --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@elseif($type === 'maintenance')

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.reports.index') }}">
            <input type="hidden" name="type" value="maintenance">
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <div>
                    <label class="form-label" style="font-size:11px; margin-bottom:2px;">
                        Critical threshold (days)
                        <span class="text-muted" style="font-size:10px;">(p_overdue_days)</span>
                    </label>
                    <input type="number" name="overdue_days" value="{{ $overdueDays }}" min="1" max="90" class="form-control" style="font-size:13px; width:100px;">
                </div>
                <div style="margin-top:18px;">
                    <button type="submit" class="btn btn-sm" style="background:#7C3AED;color:#fff;border-radius:8px;">Apply</button>
                    <a href="{{ route('admin.reports.index', ['type' => 'maintenance']) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Summary KPI Cards --}}
<div style="display:grid; grid-template-columns: repeat(4,1fr); gap:12px; margin-bottom:20px;">
    <div class="card border-0 shadow-sm" style="border-left:4px solid #DC2626;">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Critical Alerts</div>
            @if($maintenanceCriticalCount > 0)
                <div style="font-size:26px; font-weight:700; color:#991B1B;">{{ $maintenanceCriticalCount }}</div>
            @else
                <div style="font-size:26px; font-weight:700; color:#1f2937;">{{ $maintenanceCriticalCount }}</div>
            @endif
            <div style="font-size:11px; color:#bbb;">open &gt; {{ $overdueDays }} days</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm" style="border-left:4px solid #F59E0B;">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Normal Alerts</div>
            <div style="font-size:26px; font-weight:700;">{{ $maintenanceNormalCount }}</div>
            <div style="font-size:11px; color:#bbb;">within threshold</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Total Open Issues</div>
            <div style="font-size:26px; font-weight:700;">{{ $maintenanceSummary['total_open'] }}</div>
            <div style="font-size:11px; color:#bbb;">pending maintenance</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Most Affected Category</div>
            <div style="font-size:16px; font-weight:700; color:#7C3AED;">{{ $maintenanceSummary['most_affected_category'] }}</div>
            <div style="font-size:11px; color:#bbb;">by open issue count</div>
        </div>
    </div>
</div>

@if($maintenanceAlerts->isEmpty())
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center py-5">
            <div style="font-size:40px; margin-bottom:12px;">✅</div>
            <p class="text-muted mb-0">No pending maintenance issues found.</p>
            <small class="text-muted">No pending maintenance issues found.</small>
        </div>
    </div>
@else

    {{-- Charts Row --}}
    <div style="display:grid; grid-template-columns: 1fr 2fr; gap:16px; margin-bottom:20px;">

        {{-- Doughnut: CRITICAL vs NORMAL --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <span style="font-weight:600;">🚨 Alert Level Breakdown</span>
            </div>
            <div class="card-body" style="display:flex; align-items:center; justify-content:center;">
                <div style="max-width:220px; width:100%;">
                    <canvas id="maintenanceAlertChart" height="220"></canvas>
                </div>
            </div>
            <div class="card-footer bg-white py-2">
                <small class="text-muted" style="font-size:10px;">CRITICAL = open &gt; {{ $overdueDays }} days</small>
            </div>
        </div>

        {{-- Bar: Issue type counts --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <span style="font-weight:600;">🔧 Open Issues by Type</span>
            </div>
            <div class="card-body">
                @if($maintenanceIssueTypeLabels->isEmpty())
                    <p class="text-muted text-center py-4">No issue type data available.</p>
                @else
                    <canvas id="maintenanceTypeChart" height="220"></canvas>
                @endif
            </div>
            <div class="card-footer bg-white py-2">
                <small class="text-muted" style="font-size:10px;">Grouped by issue type</small>
            </div>
        </div>

    </div>

    {{-- Maintenance Detail Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <span style="font-weight:600;">🔧 Pending Maintenance Issues</span>
            <span class="text-muted" style="font-size:12px;">{{ $maintenanceAlerts->count() }} issue(s) &nbsp;·&nbsp; Threshold: {{ $overdueDays }} days</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="font-size:12px;">#</th>
                        <th style="font-size:12px;">Item</th>
                        <th style="font-size:12px;">Category</th>
                        <th style="font-size:12px;">Issue Type</th>
                        <th style="font-size:12px;">Report Date</th>
                        <th style="font-size:12px;">Days Open</th>
                        <th style="font-size:12px;">Alert Level</th>
                        <th style="font-size:12px;">Reported By</th>
                    </tr>
                </thead>
                <tbody style="font-size:13px;">
                    @foreach($maintenanceAlerts->sortByDesc('days_open') as $alert)
                        @if($alert->alert_level === 'CRITICAL')
                        <tr style="background:#FFF5F5;">
                        @else
                        <tr>
                        @endif
                            <td>{{ $alert->maintenance_id }}</td>
                            <td style="font-weight:500;">{{ $alert->item_name }}</td>
                            <td>{{ $alert->category_name }}</td>
                            <td>
                                <span class="badge" style="background:#F3E8FF; color:#5B21B6;">{{ ucfirst($alert->issue_type) }}</span>
                            </td>
                            <td>{{ \Illuminate\Support\Carbon::parse($alert->report_date)->format('d M Y') }}</td>
                            @if($alert->alert_level === 'CRITICAL')
                            <td style="font-weight:700; color:#991B1B;">{{ $alert->days_open }}d</td>
                            @else
                            <td style="color:#374151;">{{ $alert->days_open }}d</td>
                            @endif
                            <td>
                                @if($alert->alert_level === 'CRITICAL')
                                    <span class="badge" style="background:#DC2626; color:#fff;">CRITICAL</span>
                                @else
                                    <span class="badge" style="background:#FEF3C7; color:#92400E;">NORMAL</span>
                                @endif
                            </td>
                            <td>{{ $alert->reported_by ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endif {{-- end maintenanceAlerts not empty --}}

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- PENDING RESERVATIONS QUEUE                                              --}}
{{-- DB objects: sp_pending_reservations(), sp_upcoming_pickups()           --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@elseif($type === 'pending-queue')

{{-- Summary KPI Cards --}}
<div style="display:grid; grid-template-columns: repeat(3,1fr); gap:12px; margin-bottom:20px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Total Pending</div>
            <div style="font-size:26px; font-weight:700;">{{ $pendingSummary['total_pending'] }}</div>
            <div style="font-size:10px; color:#bbb; margin-top:2px;">pending + reserved borrowings</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Pending Borrowings</div>
            <div style="font-size:26px; font-weight:700;">{{ $pendingSummary['pending_borrowings'] }}</div>
            <div style="font-size:11px; color:#bbb;">pending + reserved</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm" style="border-left:4px solid #10B981;">
        <div class="card-body py-3">
            <div style="font-size:11px; color:#888;">Upcoming Pickups (7d)</div>
            <div style="font-size:26px; font-weight:700; color:#065F46;">{{ $pendingSummary['upcoming_pickups'] }}</div>
            <div style="font-size:10px; color:#bbb; margin-top:2px;">due within 7 days</div>
        </div>
    </div>
</div>


{{-- Pending Borrowings Table --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">📋 Pending Borrowing Requests</span>
        <span class="text-muted" style="font-size:12px;">{{ $pendingBorrowings->count() }} request(s)</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">Borrow ID</th>
                    <th style="font-size:12px;">Requested By</th>
                    <th style="font-size:12px;">Pickup Date</th>
                    <th style="font-size:12px;">Status</th>
                    <th style="font-size:12px;">Action</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse($pendingBorrowings as $row)
                    <tr>
                        <td>{{ $row->reference_id }}</td>
                        <td>{{ $row->requested_by }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row->request_date)->format('d M Y') }}</td>
                        <td>
                            @if($row->status === 'pending')
                            <span class="badge" style="background:#FEF3C7; color:#92400E;">Pending</span>
                            @elseif($row->status === 'reserved')
                            <span class="badge" style="background:#DBEAFE; color:#1E40AF;">Reserved</span>
                            @else
                            <span class="badge" style="background:#eee; color:#555;">{{ ucfirst($row->status) }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('staff.borrowings.show', $row->reference_id) }}" class="btn btn-sm btn-outline-secondary" style="font-size:12px; border-radius:6px;">
                                Review →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No pending borrowing requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Upcoming Pickups Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">📦 Upcoming Pickups — Next 7 Days</span>
        <span class="text-muted" style="font-size:12px;">{{ count($upcomingPickups) }} item(s)</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">Borrow ID</th>
                    <th style="font-size:12px;">Student</th>
                    <th style="font-size:12px;">Matric No</th>
                    <th style="font-size:12px;">Item</th>
                    <th style="font-size:12px;">Qty</th>
                    <th style="font-size:12px;">Pickup Date</th>
                    <th style="font-size:12px;">Return Date</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse($upcomingPickups as $pickup)
                    @php
                        $isToday    = \Illuminate\Support\Carbon::parse($pickup->pickup_date)->isToday();
                        $isTomorrow = \Illuminate\Support\Carbon::parse($pickup->pickup_date)->isTomorrow();
                    @endphp
                    @if($isToday)
                    <tr style="background:#F0FDF4;">
                    @else
                    <tr>
                    @endif
                        <td>{{ $pickup->borrow_id }}</td>
                        <td>{{ $pickup->student_name }}</td>
                        <td><code style="font-size:11px;">{{ $pickup->matric_no }}</code></td>
                        <td>{{ $pickup->item_name }}</td>
                        <td>{{ $pickup->quantity }}</td>
                        <td>
                            {{ \Illuminate\Support\Carbon::parse($pickup->pickup_date)->format('d M Y') }}
                            @if($isToday)
                                <span class="badge" style="background:#D1FAE5; color:#065F46; font-size:10px;">Today</span>
                            @elseif($isTomorrow)
                                <span class="badge" style="background:#DBEAFE; color:#1E40AF; font-size:10px;">Tomorrow</span>
                            @endif
                        </td>
                        <td>{{ \Illuminate\Support\Carbon::parse($pickup->return_date)->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No upcoming pickups in the next 7 days.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@elseif($type === 'user-engagement')

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- USER ENGAGEMENT REPORT                                                   --}}
{{-- DB objects: sp_active_users_summary(), vw_booking_overview,              --}}
{{-- vw_borrowing_items                                                        --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}

{{-- Period Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <input type="hidden" name="type" value="user-engagement">
            <span style="font-size:13px; font-weight:600; color:#374151;">Period:</span>
            @if(($engagementDays ?? 30) == 7)
            <button type="submit" name="days" value="7" class="btn btn-sm" style="background:#7C3AED; color:#fff; border-radius:8px; font-size:12px;">Last 7 Days</button>
            @else
            <button type="submit" name="days" value="7" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; font-size:12px;">Last 7 Days</button>
            @endif
            @if(($engagementDays ?? 30) == 14)
            <button type="submit" name="days" value="14" class="btn btn-sm" style="background:#7C3AED; color:#fff; border-radius:8px; font-size:12px;">Last 14 Days</button>
            @else
            <button type="submit" name="days" value="14" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; font-size:12px;">Last 14 Days</button>
            @endif
            @if(($engagementDays ?? 30) == 30)
            <button type="submit" name="days" value="30" class="btn btn-sm" style="background:#7C3AED; color:#fff; border-radius:8px; font-size:12px;">Last 30 Days</button>
            @else
            <button type="submit" name="days" value="30" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; font-size:12px;">Last 30 Days</button>
            @endif
            @if(($engagementDays ?? 30) == 90)
            <button type="submit" name="days" value="90" class="btn btn-sm" style="background:#7C3AED; color:#fff; border-radius:8px; font-size:12px;">Last 90 Days</button>
            @else
            <button type="submit" name="days" value="90" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; font-size:12px;">Last 90 Days</button>
            @endif
            <span class="text-muted ms-2" style="font-size:12px;">{{ \Carbon\Carbon::parse($engagementDateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($engagementDateTo)->format('d M Y') }}</span>
        </form>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div style="font-size:11px; color:#888;">Active Students</div>
                <div style="font-size:26px; font-weight:700; color:#7C3AED;">{{ $engagementStudentActive }}</div>
                <div style="font-size:11px; color:#bbb; margin-top:2px;">of {{ $engagementStudentTotal }} total</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div style="font-size:11px; color:#888;">Student Engagement Rate</div>
                <div style="font-size:26px; font-weight:700; color:#059669;">{{ $engagementStudentRate }}%</div>
                <div style="font-size:11px; color:#bbb; margin-top:2px;">booked or borrowed</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div style="font-size:11px; color:#888;">Active Staff</div>
                <div style="font-size:26px; font-weight:700; color:#3B82F6;">{{ $engagementStaffActive }}</div>
                <div style="font-size:11px; color:#bbb; margin-top:2px;">of {{ $engagementStaffTotal }} total</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div style="font-size:11px; color:#888;">Staff Engagement Rate</div>
                <div style="font-size:26px; font-weight:700; color:#F59E0B;">{{ $engagementStaffRate }}%</div>
                <div style="font-size:11px; color:#bbb; margin-top:2px;">handled requests</div>
            </div>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom">
                <span style="font-weight:600; font-size:14px;">Top 10 Students by Activity</span>
            </div>
            <div class="card-body">
                <canvas id="topStudentsChart" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom">
                <span style="font-weight:600; font-size:14px;">Daily Activity Trend</span>
            </div>
            <div class="card-body">
                <canvas id="engagementTrendChart" height="280"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Top Students Detail Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">👥 Top Students by Engagement</span>
        <span class="text-muted" style="font-size:12px;">{{ $topStudents->count() }} student(s) active in period</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">#</th>
                    <th style="font-size:12px;">Student</th>
                    <th style="font-size:12px;">Bookings</th>
                    <th style="font-size:12px;">Borrowings</th>
                    <th style="font-size:12px;">Total Activity</th>
                    <th style="font-size:12px;">Drill-Down</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse($topStudents as $i => $student)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td style="font-weight:500;">{{ $student['student_name'] }}</td>
                    <td>
                        <a href="{{ route('admin.reports.index', ['type' => 'bookings', 'student_id' => $student['student_id']]) }}" class="text-decoration-none" style="color:#7C3AED;">{{ $student['booking_count'] }} ↗</a>
                    </td>
                    <td>
                        <a href="{{ route('admin.reports.index', ['type' => 'borrowings', 'student_id' => $student['student_id']]) }}" class="text-decoration-none" style="color:#3B82F6;">{{ $student['borrow_count'] }} ↗</a>
                    </td>
                    <td style="font-weight:600;">{{ $student['total_activity'] }}</td>
                    <td>
                        <a href="{{ route('admin.reports.index', ['type' => 'bookings', 'student_id' => $student['student_id']]) }}" class="btn btn-sm btn-outline-secondary" style="font-size:12px; border-radius:6px;">View Activity →</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No student activity in the selected period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@elseif($type === 'collections')

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- COLLECTIONS & RETURNS REPORT                                             --}}
{{-- DB objects: sp_items_collected_report(), sp_upcoming_pickups(),          --}}
{{-- borrowings table                                                          --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}

{{-- Date Range Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <input type="hidden" name="type" value="collections">
            <div class="d-flex gap-1 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="week" style="font-size:12px; border-radius:8px;">This Week</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="month" style="font-size:12px; border-radius:8px;">This Month</button>
                <button type="button" class="btn btn-sm btn-outline-secondary date-preset-btn" data-preset="3months" style="font-size:12px; border-radius:8px;">Last 3 Months</button>
            </div>
            <span class="text-muted" style="font-size:12px; padding:0 4px;">|</span>
            <label style="font-size:12px; color:#374151;">From:</label>
            <input type="date" name="date_from" class="form-control form-control-sm" style="width:150px; font-size:12px;" value="{{ $collectionsDateFrom }}">
            <label style="font-size:12px; color:#374151;">To:</label>
            <input type="date" name="date_to" class="form-control form-control-sm" style="width:150px; font-size:12px;" value="{{ $collectionsDateTo }}">
            <button type="submit" class="btn btn-sm" style="background:#7C3AED; color:#fff; border-radius:8px; font-size:12px;">Apply</button>
        </form>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #7C3AED;">
            <div class="card-body py-3">
                <div style="font-size:11px; color:#888;">Total Collected</div>
                <div style="font-size:26px; font-weight:700; color:#7C3AED;">{{ $collectionsTotalCollected }}</div>
                <div style="font-size:11px; color:#bbb; margin-top:2px;">items picked up in period</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #059669;">
            <div class="card-body py-3">
                <div style="font-size:11px; color:#888;">Total Returned</div>
                <div style="font-size:26px; font-weight:700; color:#059669;">{{ $collectionsTotalReturned }}</div>
                <div style="font-size:11px; color:#bbb; margin-top:2px;">returned in period</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #DC2626;">
            <div class="card-body py-3">
                <div style="font-size:11px; color:#888;">Currently Overdue</div>
                <div style="font-size:26px; font-weight:700; color:#DC2626;">{{ $collectionsTotalOverdue }}</div>
                <div style="font-size:11px; color:#bbb; margin-top:2px;">system-wide overdue</div>
            </div>
        </div>
    </div>
</div>

{{-- Chart + Upcoming Pickups --}}
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom">
                <span style="font-weight:600; font-size:14px;">Daily Collection Trend</span>
            </div>
            <div class="card-body">
                <canvas id="collectionsTrendChart" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <span style="font-weight:600; font-size:14px;">Upcoming Pickups (7 days)</span>
                <span class="badge" style="background:#7C3AED; color:#fff; font-size:10px; padding:3px 7px; border-radius:99px;">{{ $collectionsUpcomingPickups->count() }}</span>
            </div>
            <div class="card-body p-0" style="max-height:280px; overflow-y:auto;">
                <table class="table table-sm mb-0" style="font-size:12px;">
                    <thead class="table-light" style="position:sticky; top:0;">
                        <tr>
                            <th>Student</th>
                            <th>Item</th>
                            <th>Pickup</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($collectionsUpcomingPickups as $pickup)
                        <tr>
                            <td>{{ $pickup->student_name }}</td>
                            <td>{{ $pickup->item_name }} (×{{ $pickup->quantity }})</td>
                            <td>{{ \Carbon\Carbon::parse($pickup->pickup_date)->format('d M') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No upcoming pickups.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Collection Details Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">📦 Collection Details</span>
        <span class="text-muted" style="font-size:12px;">{{ $collectedItems->count() }} item(s)</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">Borrow ID</th>
                    <th style="font-size:12px;">Student</th>
                    <th style="font-size:12px;">Item</th>
                    <th style="font-size:12px;">Category</th>
                    <th style="font-size:12px;">Qty</th>
                    <th style="font-size:12px;">Collected At</th>
                    <th style="font-size:12px;">Collected By</th>
                    <th style="font-size:12px;">Action</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse($collectedItems as $item)
                <tr>
                    <td>{{ $item->borrow_id }}</td>
                    <td style="font-weight:500;">{{ $item->student_name }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->category_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->collected_at)->format('d M Y H:i') }}</td>
                    <td>{{ $item->collected_by ?? '-' }}</td>
                    <td>
                        <a href="{{ route('staff.borrowings.show', $item->borrow_id) }}" class="btn btn-sm btn-outline-secondary" style="font-size:12px; border-radius:6px;">View →</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No items collected in the selected period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endif {{-- end type switch --}}

</div>{{-- /main-content wrapper --}}
</div>{{-- /two-column layout --}}

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- DATE RANGE PRESET JAVASCRIPT                                             --}}
{{-- Handles all .date-preset-btn buttons across standard + utilization forms --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    function fmtDate(d) {
        var y  = d.getFullYear();
        var m  = String(d.getMonth() + 1).padStart(2, '0');
        var dy = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + dy;
    }

    document.querySelectorAll('.date-preset-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var preset    = this.dataset.preset;
            var form      = this.closest('form');
            if (!form) return;
            var fromInput = form.querySelector('[name="date_from"]');
            var toInput   = form.querySelector('[name="date_to"]');
            if (!fromInput || !toInput) return;

            if (preset === 'custom') {
                fromInput.value = '';
                toInput.value   = '';
                fromInput.focus();
                return;
            }

            var today    = new Date();
            today.setHours(0, 0, 0, 0);
            var todayStr = fmtDate(today);
            var from, to;

            if (preset === 'today') {
                from = to = todayStr;
            } else if (preset === 'week') {
                var dow = today.getDay();
                var mon = new Date(today);
                mon.setDate(today.getDate() - (dow === 0 ? 6 : dow - 1));
                from = fmtDate(mon);
                to   = todayStr;
            } else if (preset === 'month') {
                from = fmtDate(new Date(today.getFullYear(), today.getMonth(), 1));
                to   = todayStr;
            } else if (preset === '3months') {
                var d3m = new Date(today);
                d3m.setMonth(d3m.getMonth() - 3);
                from = fmtDate(d3m);
                to   = todayStr;
            } else if (preset === 'year') {
                from = fmtDate(new Date(today.getFullYear(), 0, 1));
                to   = todayStr;
            } else {
                return;
            }

            fromInput.value = from;
            toInput.value   = to;
            form.submit();
        });
    });
});
</script>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- CHART.JS — loaded only for analytics tabs                               --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
@if(in_array($type, ['utilization', 'maintenance', 'user-engagement', 'collections']))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const palette = ['#8B5CF6', '#3B82F6', '#F59E0B', '#10B981', '#6366F1', '#14B8A6'];

@if($type === 'utilization')

const utilizationRateLabels   = @json($utilizationChartLabels);
const utilizationRateData     = @json($utilizationChartData);
const utilizationTrendLabels  = @json($utilizationTrendLabels);
const utilizationTrendDatasets = @json($utilizationTrendDatasets);

if (utilizationRateLabels.length > 0 && document.getElementById('utilizationRateChart')) {
    new Chart(document.getElementById('utilizationRateChart'), {
        type: 'bar',
        data: {
            labels: utilizationRateLabels,
            datasets: [{
                label: 'Utilization Rate (%)',
                data: utilizationRateData,
                backgroundColor: utilizationRateLabels.map((_, i) => palette[i % palette.length]),
                borderRadius: 4,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: (v) => v + '%', font: { size: 11 } },
                },
                y: { ticks: { font: { size: 11 } } },
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => ctx.parsed.x.toFixed(2) + '%' } },
            },
        },
    });
}

if (utilizationTrendLabels.length > 0 && utilizationTrendDatasets.length > 0 && document.getElementById('utilizationTrendChart')) {
    new Chart(document.getElementById('utilizationTrendChart'), {
        type: 'bar',
        data: {
            labels: utilizationTrendLabels,
            datasets: utilizationTrendDatasets.map((ds, i) => ({
                label: ds.label,
                data: ds.data,
                backgroundColor: palette[i % palette.length],
                borderRadius: 3,
            })),
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
                x: { ticks: { font: { size: 11 } } },
            },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            },
        },
    });
}

@elseif($type === 'maintenance')

const criticalCount = {{ $maintenanceCriticalCount }};
const normalCount   = {{ $maintenanceNormalCount }};
const overdueDays   = {{ $overdueDays }};
const issueTypeLabels = @json($maintenanceIssueTypeLabels);
const issueTypeCounts = @json($maintenanceIssueTypeCounts);

if (document.getElementById('maintenanceAlertChart')) {
    new Chart(document.getElementById('maintenanceAlertChart'), {
        type: 'doughnut',
        data: {
            labels: ['Critical (>' + overdueDays + 'd)', 'Normal (≤' + overdueDays + 'd)'],
            datasets: [{
                data: [criticalCount, normalCount],
                backgroundColor: ['#DC2626', '#F59E0B'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            },
            cutout: '60%',
        },
    });
}

if (issueTypeLabels.length > 0 && document.getElementById('maintenanceTypeChart')) {
    new Chart(document.getElementById('maintenanceTypeChart'), {
        type: 'bar',
        data: {
            labels: issueTypeLabels.map((l) => l.charAt(0).toUpperCase() + l.slice(1)),
            datasets: [{
                label: 'Open Issues',
                data: issueTypeCounts,
                backgroundColor: issueTypeLabels.map((_, i) => palette[i % palette.length]),
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
                x: { ticks: { font: { size: 11 } } },
            },
            plugins: {
                legend: { display: false },
            },
        },
    });
}

@elseif($type === 'user-engagement')

const topStudentNames      = @json($topStudentNames);
const topStudentBookings   = @json($topStudentBookings);
const topStudentBorrowings = @json($topStudentBorrowings);
const engChartDates        = @json($engagementChartDates);
const engBookingData       = @json($engagementBookingData);
const engBorrowingData     = @json($engagementBorrowingData);

if (topStudentNames.length > 0 && document.getElementById('topStudentsChart')) {
    new Chart(document.getElementById('topStudentsChart'), {
        type: 'bar',
        data: {
            labels: topStudentNames,
            datasets: [
                {
                    label: 'Bookings',
                    data: topStudentBookings,
                    backgroundColor: '#8B5CF6',
                    borderRadius: 3,
                },
                {
                    label: 'Borrowings',
                    data: topStudentBorrowings,
                    backgroundColor: '#10B981',
                    borderRadius: 3,
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
                y: { ticks: { font: { size: 11 } } },
            },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            },
        },
    });
}

if (engChartDates.length > 0 && document.getElementById('engagementTrendChart')) {
    new Chart(document.getElementById('engagementTrendChart'), {
        type: 'line',
        data: {
            labels: engChartDates,
            datasets: [
                {
                    label: 'Bookings',
                    data: engBookingData,
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139,92,246,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                },
                {
                    label: 'Borrowings',
                    data: engBorrowingData,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16,185,129,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
                x: { ticks: { font: { size: 10 }, maxRotation: 45 } },
            },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            },
        },
    });
}

@elseif($type === 'collections')

const collectionsTrendLabels = @json($collectionsTrendLabels);
const collectionsTrendData   = @json($collectionsTrendData);

if (collectionsTrendLabels.length > 0 && document.getElementById('collectionsTrendChart')) {
    new Chart(document.getElementById('collectionsTrendChart'), {
        type: 'bar',
        data: {
            labels: collectionsTrendLabels,
            datasets: [{
                label: 'Items Collected',
                data: collectionsTrendData,
                backgroundColor: '#10B981',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
                x: { ticks: { font: { size: 11 } } },
            },
            plugins: {
                legend: { display: false },
            },
        },
    });
}

@endif {{-- type switch for charts --}}
</script>
@endif {{-- end Chart.js block --}}

@endsection
