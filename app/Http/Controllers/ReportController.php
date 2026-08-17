<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\Booking;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Category;
use App\Models\Item;
use App\Models\Studio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportFilterRequest $request)
    {
        $filters = $request->validated();
        $type    = $filters['type'] ?? 'bookings';

        $data = match ($type) {
            'borrowings'      => $this->borrowingsReport($filters),
            'inventory'       => $this->inventoryReport($filters),
            'utilization'     => $this->utilizationReport($filters),
            'maintenance'     => $this->maintenanceReport($filters),
            'pending-queue'   => $this->pendingQueueReport($filters),
            'user-engagement' => $this->userEngagementReport($filters),
            'collections'     => $this->collectionsReport($filters),
            default           => $this->bookingsReport($filters),
        };

        return view('admin.reports.index', array_merge([
            'type'       => $type,
            'filters'    => $filters,
            'studios'    => Studio::orderBy('studio_name')->get(),
            'categories' => Category::orderBy('category_name')->get(),
            'atAGlance'  => $this->atAGlanceKpis(),
        ], $data));
    }

    public function exportPdf(ReportFilterRequest $request)
    {
        $filters = $request->validated();
        $type    = $filters['type'] ?? 'bookings';

        $data = match ($type) {
            'borrowings' => $this->borrowingsReport($filters, false),
            'inventory'  => $this->inventoryReport($filters, false),
            default      => $this->bookingsReport($filters, false),
        };

        $pdf = Pdf::loadView('admin.reports.pdf', array_merge([
            'type'    => $type,
            'filters' => $filters,
        ], $data))->setPaper('a4', 'portrait');

        return $pdf->download($this->exportFilename($type, 'pdf'));
    }

    public function exportCsv(ReportFilterRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $type    = $filters['type'] ?? 'bookings';

        $data = match ($type) {
            'borrowings' => $this->borrowingsReport($filters, false),
            'inventory'  => $this->inventoryReport($filters, false),
            default      => $this->bookingsReport($filters, false),
        };

        $results = $data['results'];

        $callback = function () use ($type, $results) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            match ($type) {
                'borrowings' => $this->writeBorrowingsCsv($handle, $results),
                'inventory'  => $this->writeInventoryCsv($handle, $results),
                default      => $this->writeBookingsCsv($handle, $results),
            };

            fclose($handle);
        };

        return response()->streamDownload($callback, $this->exportFilename($type, 'csv'), [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function exportFilename(string $type, string $extension): string
    {
        return $type . '-report-' . now()->format('Ymd_His') . '.' . $extension;
    }

    private function writeBookingsCsv($handle, $bookings): void
    {
        fputcsv($handle, ['Booking ID', 'Studio', 'Student', 'Date', 'Start Time', 'End Time', 'Status', 'Purpose']);

        foreach ($bookings as $booking) {
            fputcsv($handle, [
                $booking->booking_id,
                $booking->studio->studio_name ?? '-',
                $booking->student->full_name ?? '-',
                \Illuminate\Support\Carbon::parse($booking->booking_date)->format('Y-m-d'),
                \Illuminate\Support\Carbon::parse($booking->start_time)->format('H:i'),
                \Illuminate\Support\Carbon::parse($booking->end_time)->format('H:i'),
                ucfirst($booking->booking_status),
                $booking->purpose ?? '-',
            ]);
        }
    }

    private function writeBorrowingsCsv($handle, $borrowings): void
    {
        fputcsv($handle, ['Borrow ID', 'Student', 'Items', 'Pickup Date', 'Return Date', 'Status', 'Collected At', 'Returned At']);

        foreach ($borrowings as $borrowing) {
            $items = $borrowing->borrowingDetails
                ->map(fn ($d) => ($d->item->item_name ?? '-') . ' (x' . $d->quantity_borrowed . ')')
                ->join(', ');

            fputcsv($handle, [
                $borrowing->borrow_id,
                $borrowing->student->full_name ?? '-',
                $items,
                \Illuminate\Support\Carbon::parse($borrowing->pickup_date)->format('Y-m-d'),
                \Illuminate\Support\Carbon::parse($borrowing->return_date)->format('Y-m-d'),
                ucfirst($borrowing->borrow_status),
                $borrowing->collected_at?->format('Y-m-d H:i') ?? '-',
                $borrowing->returned_at?->format('Y-m-d H:i') ?? '-',
            ]);
        }
    }

    private function writeInventoryCsv($handle, $items): void
    {
        fputcsv($handle, ['Item ID', 'Item Name', 'Category', 'Quantity', 'Available', 'Condition', 'Status', 'Date Added']);

        foreach ($items as $item) {
            fputcsv($handle, [
                $item->item_id,
                $item->item_name,
                $item->category->category_name ?? '-',
                $item->quantity,
                $item->available_quantity,
                ucfirst($item->condition_status),
                ucfirst($item->item_status),
                \Illuminate\Support\Carbon::parse($item->date_added)->format('Y-m-d'),
            ]);
        }
    }

    private function bookingsQuery(array $filters): Builder
    {
        $query = Booking::with(['student', 'studio']);

        $this->applyDateRange($query, 'booking_date', $filters);

        if (!empty($filters['status'])) {
            $query->where('booking_status', $filters['status']);
        }

        if (!empty($filters['studio_id'])) {
            $query->where('studio_id', $filters['studio_id']);
        }

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        return $query;
    }

    private function bookingsReport(array $filters, bool $paginate = true): array
    {
        $query = $this->bookingsQuery($filters);

        $statusCounts = (clone $query)
            ->select('booking_status', DB::raw('count(*) as total'))
            ->groupBy('booking_status')
            ->pluck('total', 'booking_status');

        $ordered = (clone $query)->orderBy('booking_date', 'desc')->orderBy('start_time', 'desc');

        $results = $paginate
            ? $ordered->paginate(15)->withQueryString()
            : $ordered->get();

        return [
            'results' => $results,
            'summary' => [
                'total'     => (clone $query)->count(),
                'confirmed' => $statusCounts->get('confirmed', 0),
                'completed' => $statusCounts->get('completed', 0),
                'cancelled' => $statusCounts->get('cancelled', 0),
            ],
        ];
    }

    private function borrowingsQuery(array $filters): Builder
    {
        $query = Borrowing::with(['student', 'borrowingDetails.item']);

        $this->applyDateRange($query, 'pickup_date', $filters);

        if (!empty($filters['status'])) {
            $query->where('borrow_status', $filters['status']);
        }

        if (!empty($filters['overdue_only'])) {
            $query->where('is_overdue', true);
        }

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        return $query;
    }

    private function borrowingsReport(array $filters, bool $paginate = true): array
    {
        $query = $this->borrowingsQuery($filters);

        $statusCounts = (clone $query)
            ->select('borrow_status', DB::raw('count(*) as total'))
            ->groupBy('borrow_status')
            ->pluck('total', 'borrow_status');

        $ordered = (clone $query)->orderBy('pickup_date', 'desc');

        $results = $paginate
            ? $ordered->paginate(15)->withQueryString()
            : $ordered->get();

        $borrowIds = (clone $query)->pluck('borrow_id');

        $totalItemsBorrowed = BorrowingDetail::whereIn('borrow_id', $borrowIds)->sum('quantity_borrowed');

        // Global overdue count — all borrowings system-wide, regardless of date/status filters
        $overdueCount = DB::table('borrowings')->where('is_overdue', true)->count();

        return [
            'results'      => $results,
            'overdueCount' => (int) $overdueCount,
            'summary' => [
                'total'           => (clone $query)->count(),
                'pending'         => $statusCounts->get('pending', 0),
                'reserved'        => $statusCounts->get('reserved', 0),
                'collected'       => $statusCounts->get('collected', 0),
                'returned'        => $statusCounts->get('returned', 0),
                'rejected'        => $statusCounts->get('rejected', 0),
                'cancelled'       => $statusCounts->get('cancelled', 0),
                'items_borrowed'  => (int) $totalItemsBorrowed,
            ],
        ];
    }

    private function inventoryQuery(array $filters): Builder
    {
        $query = Item::with('category');

        $this->applyDateRange($query, 'date_added', $filters);

        if (!empty($filters['status'])) {
            $query->where('item_status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return $query;
    }

    private function inventoryReport(array $filters, bool $paginate = true): array
    {
        $query = $this->inventoryQuery($filters);

        $ordered = (clone $query)->orderBy('item_name');

        $results = $paginate
            ? $ordered->paginate(15)->withQueryString()
            : $ordered->get();

        $totals = (clone $query)
            ->without('category')
            ->select(
                DB::raw('count(*) as total_items'),
                DB::raw('coalesce(sum(quantity), 0) as total_quantity'),
                DB::raw('coalesce(sum(available_quantity), 0) as total_available')
            )
            ->first();

        $lowStock = (clone $query)
            ->without('category')
            ->where('item_status', 'available')
            ->whereRaw('available_quantity <= quantity * 0.2')
            ->count();

        // v_inventory_low_stock — items where available_quantity <= quantity * 0.2; only fetch on web views
        $lowStockItems = $paginate
            ? DB::table('v_inventory_low_stock')->orderBy('availability_pct')->get()
            : collect();

        return [
            'results'       => $results,
            'lowStockItems' => $lowStockItems,
            'summary' => [
                'total_items'     => $totals->total_items,
                'total_quantity'  => $totals->total_quantity,
                'total_available' => $totals->total_available,
                'low_stock'       => $lowStock,
            ],
        ];
    }

    private function applyDateRange(Builder $query, string $column, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }
    }

    // ── NEW ANALYTICS METHODS ─────────────────────────────────────────────

    private function atAGlanceKpis(): array
    {
        return Cache::remember('reports.at_a_glance.' . Carbon::today()->toDateString(), now()->addMinutes(5), function () {
            // sp_pending_reservations() — borrowings with pending/reserved status only
            // (bookings have no pending approval workflow; they are auto-confirmed on creation)
            $pendingCount = DB::selectOne("SELECT COUNT(*) AS cnt FROM sp_pending_reservations()")->cnt ?? 0;

            // is_overdue column maintained by trg_borrowings_set_overdue trigger (BEFORE INSERT OR UPDATE)
            $overdueCount = DB::table('borrowings')->where('is_overdue', true)->count();

            // v_inventory_low_stock view — items where available_quantity <= quantity * 0.2
            $lowStockCount = DB::table('v_inventory_low_stock')->count();

            // sp_maintenance_instruments_report() — all pending maintenance items
            $maintenanceCount = DB::selectOne("SELECT COUNT(*) AS cnt FROM sp_maintenance_instruments_report()")->cnt ?? 0;

            // sp_active_users_summary(30) — users who made a booking or borrowing in the last 30 days
            $activeUsersRows = DB::select("SELECT * FROM sp_active_users_summary(30)");
            $activeUsers30d  = collect($activeUsersRows)->sum('active_in_period');

            return [
                'kpi_pending_reservations' => (int) $pendingCount,
                'kpi_overdue_borrowings'   => (int) $overdueCount,
                'kpi_low_stock_items'      => (int) $lowStockCount,
                'kpi_pending_maintenance'  => (int) $maintenanceCount,
                'kpi_active_users_30d'     => (int) $activeUsers30d,
            ];
        });
    }

    private function utilizationReport(array $filters): array
    {
        $dateFrom       = !empty($filters['date_from']) ? $filters['date_from'] : Carbon::now()->startOfMonth()->toDateString();
        $dateTo         = !empty($filters['date_to'])   ? $filters['date_to']   : Carbon::today()->toDateString();
        $operatingHours = (int) ($filters['operating_hours'] ?? 12);

        // sp_studio_utilization_rate(p_start, p_end, p_daily_hours) — parameterised function
        $utilizationRows = collect(DB::select(
            "SELECT * FROM sp_studio_utilization_rate(?, ?, ?)",
            [$dateFrom, $dateTo, $operatingHours]
        ));

        // v_monthly_booking_summary — monthly booking counts per studio for trend chart
        $monthFrom    = Carbon::parse($dateFrom)->format('Y-m');
        $monthTo      = Carbon::parse($dateTo)->format('Y-m');
        $monthlyTrend = DB::table('v_monthly_booking_summary')
            ->where('report_month', '>=', $monthFrom)
            ->where('report_month', '<=', $monthTo)
            ->orderBy('report_month')
            ->orderBy('studio_name')
            ->get();

        $studios = $monthlyTrend->pluck('studio_name')->unique()->values();
        $months  = $monthlyTrend->pluck('report_month')->unique()->sort()->values();

        $trendDatasets = $studios->map(function ($studioName) use ($monthlyTrend, $months) {
            $byMonth = $monthlyTrend->where('studio_name', $studioName)->keyBy('report_month');
            return [
                'label' => $studioName,
                'data'  => $months->map(fn ($m) => (int) ($byMonth->get($m)->total_bookings ?? 0))->values()->toArray(),
            ];
        })->values();

        $trendLabels = $months->map(fn ($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'))->values();

        $topStudio = $utilizationRows->sortByDesc('utilization_rate')->first();

        return [
            'utilizationRows'          => $utilizationRows,
            'utilizationChartLabels'   => $utilizationRows->pluck('studio_name')->values(),
            'utilizationChartData'     => $utilizationRows->pluck('utilization_rate')->map(fn ($v) => (float) $v)->values(),
            'utilizationTrendLabels'   => $trendLabels,
            'utilizationTrendDatasets' => $trendDatasets,
            'utilizationSummary'       => [
                'total_booked_hours' => round((float) $utilizationRows->sum('booked_hours'), 1),
                'total_bookings'     => (int) $utilizationRows->sum('total_bookings'),
                'top_studio_name'    => $topStudio ? $topStudio->studio_name : 'N/A',
                'top_studio_rate'    => $topStudio ? (float) $topStudio->utilization_rate : 0.0,
            ],
            'operatingHours'           => $operatingHours,
            'utilizationDateFrom'      => $dateFrom,
            'utilizationDateTo'        => $dateTo,
        ];
    }

    private function maintenanceReport(array $filters): array
    {
        $overdueDays = (int) ($filters['overdue_days'] ?? 7);

        // sp_maintenance_alerts(p_overdue_days) — pending items with CRITICAL/NORMAL alert levels
        // CRITICAL when days_open > p_overdue_days, NORMAL otherwise
        $alerts           = collect(DB::select("SELECT * FROM sp_maintenance_alerts(?)", [$overdueDays]));
        $criticalCount    = $alerts->where('alert_level', 'CRITICAL')->count();
        $normalCount      = $alerts->where('alert_level', 'NORMAL')->count();

        $byIssueType = $alerts->groupBy('issue_type')
            ->map->count()
            ->sortDesc();

        $mostAffectedCategory = $alerts->groupBy('category_name')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();

        return [
            'maintenanceAlerts'          => $alerts,
            'maintenanceCriticalCount'   => $criticalCount,
            'maintenanceNormalCount'     => $normalCount,
            'maintenanceIssueTypeLabels' => $byIssueType->keys()->values(),
            'maintenanceIssueTypeCounts' => $byIssueType->values(),
            'maintenanceSummary'         => [
                'total_open'             => $alerts->count(),
                'critical_count'         => $criticalCount,
                'most_affected_category' => $mostAffectedCategory ?? 'N/A',
            ],
            'overdueDays'                => $overdueDays,
        ];
    }

    private function pendingQueueReport(array $filters): array
    {
        // sp_pending_reservations() — borrowings with pending/reserved status only.
        // Bookings have no pending approval workflow (auto-confirmed on creation),
        // so they are intentionally excluded from all pending counts here.
        $pendingBorrowings = collect(DB::select("SELECT * FROM sp_pending_reservations()"));

        // sp_upcoming_pickups(7) — reserved borrowings due for pickup in next 7 days
        $upcomingPickups = DB::select("SELECT * FROM sp_upcoming_pickups(7)");

        return [
            'pendingBorrowings' => $pendingBorrowings,
            'upcomingPickups'   => $upcomingPickups,
            'pendingSummary'    => [
                'total_pending'      => $pendingBorrowings->count(),
                'pending_borrowings' => $pendingBorrowings->count(),
                'upcoming_pickups'   => count($upcomingPickups),
            ],
        ];
    }

    private function userEngagementReport(array $filters): array
    {
        $days     = (int) ($filters['days'] ?? 30);
        $dateFrom = Carbon::today()->subDays($days)->toDateString();
        $dateTo   = Carbon::today()->toDateString();

        // sp_active_users_summary(p_days) — returns one row per user_type with total_active and active_in_period
        $summaryRows  = collect(DB::select("SELECT * FROM sp_active_users_summary(?)", [$days]));
        $studentRow   = $summaryRows->firstWhere('user_type', 'student');
        $staffRow     = $summaryRows->firstWhere('user_type', 'staff');
        $studentTotal  = (int) ($studentRow->total_active ?? 0);
        $studentActive = (int) ($studentRow->active_in_period ?? 0);
        $staffTotal    = (int) ($staffRow->total_active ?? 0);
        $staffActive   = (int) ($staffRow->active_in_period ?? 0);

        // Top 10 students — combine booking + borrowing counts from views
        // vw_booking_overview: one row per booking
        $bookingCounts = DB::table('vw_booking_overview')
            ->whereBetween('booking_date', [$dateFrom, $dateTo])
            ->select('student_id', 'student_name', DB::raw('count(*) as booking_count'))
            ->groupBy('student_id', 'student_name')
            ->get()
            ->keyBy('student_id');

        // vw_borrowing_items: one row per item-per-borrowing; count distinct borrow_id per student
        $borrowingCounts = DB::table('vw_borrowing_items')
            ->whereBetween('pickup_date', [$dateFrom, $dateTo])
            ->select('student_id', 'student_name', DB::raw('count(distinct borrow_id) as borrow_count'))
            ->groupBy('student_id', 'student_name')
            ->get()
            ->keyBy('student_id');

        $allStudentIds = $bookingCounts->keys()->merge($borrowingCounts->keys())->unique();
        $topStudents   = $allStudentIds->map(function ($id) use ($bookingCounts, $borrowingCounts) {
            $name = $bookingCounts->get($id)?->student_name ?? $borrowingCounts->get($id)?->student_name ?? 'Unknown';
            $b    = (int) ($bookingCounts->get($id)?->booking_count ?? 0);
            $r    = (int) ($borrowingCounts->get($id)?->borrow_count ?? 0);
            return [
                'student_id'     => $id,
                'student_name'   => $name,
                'booking_count'  => $b,
                'borrow_count'   => $r,
                'total_activity' => $b + $r,
            ];
        })->sortByDesc('total_activity')->take(10)->values();

        // Daily booking activity — group by booking_date
        $dailyBookings = DB::table('vw_booking_overview')
            ->whereBetween('booking_date', [$dateFrom, $dateTo])
            ->select('booking_date', DB::raw('count(*) as cnt'))
            ->groupBy('booking_date')
            ->orderBy('booking_date')
            ->get()
            ->keyBy('booking_date');

        // Daily borrowing activity — count distinct borrow_id per pickup_date
        $dailyBorrowings = DB::table('vw_borrowing_items')
            ->whereBetween('pickup_date', [$dateFrom, $dateTo])
            ->select('pickup_date', DB::raw('count(distinct borrow_id) as cnt'))
            ->groupBy('pickup_date')
            ->orderBy('pickup_date')
            ->get()
            ->keyBy('pickup_date');

        // Build full date series to fill gaps
        $dates = [];
        $d     = Carbon::parse($dateFrom);
        $end   = Carbon::parse($dateTo);
        while ($d->lte($end)) {
            $dates[] = $d->toDateString();
            $d->addDay();
        }
        $period = collect($dates);

        return [
            'engagementDays'          => $days,
            'engagementDateFrom'      => $dateFrom,
            'engagementDateTo'        => $dateTo,
            'engagementStudentTotal'  => $studentTotal,
            'engagementStudentActive' => $studentActive,
            'engagementStudentRate'   => $studentTotal > 0 ? round($studentActive / $studentTotal * 100, 1) : 0.0,
            'engagementStaffTotal'    => $staffTotal,
            'engagementStaffActive'   => $staffActive,
            'engagementStaffRate'     => $staffTotal > 0 ? round($staffActive / $staffTotal * 100, 1) : 0.0,
            'topStudents'             => $topStudents,
            'topStudentNames'         => $topStudents->pluck('student_name')->values(),
            'topStudentBookings'      => $topStudents->pluck('booking_count')->values(),
            'topStudentBorrowings'    => $topStudents->pluck('borrow_count')->values(),
            'engagementChartDates'    => $period->map(fn ($d) => Carbon::parse($d)->format('d M'))->values(),
            'engagementBookingData'   => $period->map(fn ($d) => (int) ($dailyBookings->get($d)?->cnt ?? 0))->values(),
            'engagementBorrowingData' => $period->map(fn ($d) => (int) ($dailyBorrowings->get($d)?->cnt ?? 0))->values(),
        ];
    }

    private function collectionsReport(array $filters): array
    {
        $dateFrom = !empty($filters['date_from']) ? $filters['date_from'] : Carbon::now()->startOfMonth()->toDateString();
        $dateTo   = !empty($filters['date_to'])   ? $filters['date_to']   : Carbon::today()->toDateString();

        // sp_items_collected_report(p_start, p_end) — borrowings collected/returned, filtered by collected_at
        $collected = collect(DB::select("SELECT * FROM sp_items_collected_report(?, ?)", [$dateFrom, $dateTo]));

        // Total returned in selected period (returned_at timestamp in range)
        $totalReturned = DB::table('borrowings')
            ->where('borrow_status', 'returned')
            ->whereDate('returned_at', '>=', $dateFrom)
            ->whereDate('returned_at', '<=', $dateTo)
            ->count();

        // Total currently overdue system-wide (is_overdue from trigger)
        $totalOverdue = DB::table('borrowings')->where('is_overdue', true)->count();

        // Daily trend — group by collected_at date
        $dailyTrend  = $collected->groupBy(fn ($r) => Carbon::parse($r->collected_at)->toDateString())
            ->map->count()
            ->sortKeys();

        // sp_upcoming_pickups(7) — reused from pending queue
        $upcomingPickups = collect(DB::select("SELECT * FROM sp_upcoming_pickups(7)"));

        return [
            'collectedItems'              => $collected,
            'collectionsTotalCollected'   => $collected->count(),
            'collectionsTotalReturned'    => (int) $totalReturned,
            'collectionsTotalOverdue'     => (int) $totalOverdue,
            'collectionsTrendLabels'      => $dailyTrend->keys()->map(fn ($d) => Carbon::parse($d)->format('d M'))->values(),
            'collectionsTrendData'        => $dailyTrend->values(),
            'collectionsUpcomingPickups'  => $upcomingPickups,
            'collectionsDateFrom'         => $dateFrom,
            'collectionsDateTo'           => $dateTo,
        ];
    }
}
