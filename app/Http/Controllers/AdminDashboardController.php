<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminDashboardFilterRequest;
use App\Models\Booking;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Category;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Studio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(AdminDashboardFilterRequest $request)
    {
        $filters = $request->validated();

        $dateTo   = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : Carbon::today();
        $dateFrom = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : $dateTo->copy()->subMonths(5)->startOfMonth();
        $categoryId = $filters['category_id'] ?? null;

        return view('admin.dashboard', array_merge([
            'filters'         => $filters,
            'dateFrom'        => $dateFrom->toDateString(),
            'dateTo'          => $dateTo->toDateString(),
            'categories'      => Category::where('type', 'equipment')->orderBy('category_name')->get(),
            'recentActivities'=> $this->recentActivities(),
        ], $this->kpis(), $this->reservationKpis(), $this->mostBorrowedInstrumentsChart($dateFrom, $dateTo, $categoryId), $this->studioUtilizationChart($dateFrom, $dateTo), $this->instrumentHealthChart()));
    }

    /**
     * High-level KPI widgets for the top of the admin dashboard.
     */
    private function kpis(): array
    {
        return Cache::remember('admin.dashboard.kpis.' . Carbon::today()->toDateString(), now()->addMinutes(10), function () {
            $monthStart = Carbon::now()->startOfMonth();
            $monthEnd   = Carbon::now()->endOfMonth();

            $activeBookingsThisMonth = Booking::whereBetween('booking_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->whereIn('booking_status', ['confirmed', 'completed'])
                ->count();

            $totalStudios = Studio::count();

            // Utilization rate: active bookings this month vs. one booking slot per studio per day.
            $maxPossibleBookings = $totalStudios * $monthStart->daysInMonth;
            $utilizationRate = $maxPossibleBookings > 0
                ? round(($activeBookingsThisMonth / $maxPossibleBookings) * 100, 1)
                : 0.0;

            $damagedInstruments = Maintenance::where('maintenance_status', 'pending')
                ->distinct('item_id')
                ->count('item_id');

            $activeUsers = Student::where('status', 'active')->count() + Staff::where('status', 'active')->count();

            return [
                'kpiActiveBookings'    => $activeBookingsThisMonth,
                'kpiUtilizationRate'   => $utilizationRate,
                'kpiDamagedInstruments'=> $damagedInstruments,
                'kpiActiveUsers'       => $activeUsers,
            ];
        });
    }

    /**
     * Reservation-based borrowing KPIs for the admin dashboard.
     */
    private function reservationKpis(): array
    {
        return Cache::remember('admin.dashboard.reservation_kpis.' . Carbon::today()->toDateString(), now()->addMinutes(10), function () {
            $today = Carbon::today()->toDateString();

            return [
                'kpiPendingReservations' => Borrowing::where('borrow_status', 'pending')->count(),
                'kpiUpcomingPickups'     => Borrowing::where('borrow_status', 'reserved')
                    ->where('pickup_date', '>=', $today)
                    ->count(),
                'kpiItemsCollected'      => Borrowing::where('borrow_status', 'collected')->count(),
                'kpiOverdueReturns'      => Borrowing::where('borrow_status', 'collected')
                    ->where('return_date', '<', $today)
                    ->count(),
            ];
        });
    }

    /**
     * Bar chart data: total quantity borrowed per month for the top 5 most-borrowed items.
     */
    private function mostBorrowedInstrumentsChart(Carbon $dateFrom, Carbon $dateTo, ?int $categoryId): array
    {
        $cacheKey = "admin.dashboard.most_borrowed.{$dateFrom->toDateString()}.{$dateTo->toDateString()}." . ($categoryId ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($dateFrom, $dateTo, $categoryId) {
        $baseQuery = BorrowingDetail::query()
            ->join('borrowings', 'borrowing_details.borrow_id', '=', 'borrowings.borrow_id')
            ->join('items', 'borrowing_details.item_id', '=', 'items.item_id')
            ->whereBetween('borrowings.pickup_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        if ($categoryId) {
            $baseQuery->where('items.category_id', $categoryId);
        }

        $topItems = (clone $baseQuery)
            ->select('items.item_id', 'items.item_name', DB::raw('SUM(borrowing_details.quantity_borrowed) as total_borrowed'))
            ->groupBy('items.item_id', 'items.item_name')
            ->orderByDesc('total_borrowed')
            ->limit(5)
            ->get();

        $months = [];
        $cursor = $dateFrom->copy()->startOfMonth();
        $end = $dateTo->copy()->startOfMonth();
        while ($cursor->lessThanOrEqualTo($end)) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $monthlyTotals = collect();
        if ($topItems->isNotEmpty()) {
            $monthlyTotals = (clone $baseQuery)
                ->whereIn('items.item_id', $topItems->pluck('item_id'))
                ->select(
                    'items.item_id',
                    DB::raw("to_char(borrowings.pickup_date, 'YYYY-MM') as month"),
                    DB::raw('SUM(borrowing_details.quantity_borrowed) as total')
                )
                ->groupBy('items.item_id', 'month')
                ->get()
                ->groupBy('item_id');
        }

        $datasets = $topItems->map(function ($item) use ($monthlyTotals, $months) {
            $itemMonths = $monthlyTotals->get($item->item_id, collect())->keyBy('month');

            return [
                'label' => $item->item_name,
                'data'  => array_map(fn ($month) => (int) ($itemMonths->get($month)->total ?? 0), $months),
            ];
        })->values();

        return [
            'borrowedChartLabels'   => array_map(fn ($month) => Carbon::createFromFormat('Y-m', $month)->format('M Y'), $months),
            'borrowedChartDatasets' => $datasets,
        ];
        });
    }

    /**
     * Pie/doughnut chart data: share of confirmed/completed bookings per studio in the selected range.
     */
    private function studioUtilizationChart(Carbon $dateFrom, Carbon $dateTo): array
    {
        $cacheKey = "admin.dashboard.studio_utilization.{$dateFrom->toDateString()}.{$dateTo->toDateString()}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($dateFrom, $dateTo) {
        $bookingCounts = Booking::whereBetween('booking_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereIn('booking_status', ['confirmed', 'completed'])
            ->select('studio_id', DB::raw('count(*) as total'))
            ->groupBy('studio_id')
            ->pluck('total', 'studio_id');

        $studios = Studio::orderBy('studio_name')->get();
        $totalBookings = $bookingCounts->sum();

        $utilizationTable = $studios->map(function ($studio) use ($bookingCounts, $totalBookings) {
            $count = (int) ($bookingCounts->get($studio->studio_id) ?? 0);

            return [
                'studio_name' => $studio->studio_name,
                'bookings'    => $count,
                'percentage'  => $totalBookings > 0 ? round(($count / $totalBookings) * 100, 1) : 0.0,
            ];
        });

        return [
            'utilizationChartLabels' => $studios->pluck('studio_name')->values(),
            'utilizationChartData'   => $utilizationTable->pluck('bookings')->values(),
        ];
        });
    }

    /**
     * Doughnut chart data: functional vs damaged/under-maintenance instruments.
     */
    private function instrumentHealthChart(): array
    {
        return Cache::remember('admin.dashboard.instrument_health', now()->addMinutes(15), function () {
        $totalItems = Item::count();

        $damagedItemIds = Maintenance::where('maintenance_status', 'pending')->pluck('item_id')
            ->merge(Item::where('condition_status', 'poor')->pluck('item_id'))
            ->unique();

        $damagedCount = $damagedItemIds->count();
        $functionalCount = max($totalItems - $damagedCount, 0);

        return [
            'healthChartLabels'  => ['Functional', 'Damaged / Under Maintenance'],
            'healthChartData'    => [$functionalCount, $damagedCount],
        ];
        });
    }

    /**
     * Unified "Recent Activities" feed: merges the latest bookings and the latest
     * pending maintenance reports into a single chronologically-sorted list.
     */
    private function recentActivities()
    {
        $bookings = Booking::with(['student', 'studio'])
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->limit(8)
            ->get()
            ->map(function ($booking) {
                return [
                    'type'        => 'Booking',
                    'date'        => Carbon::parse($booking->booking_date),
                    'description' => ($booking->student->full_name ?? 'Unknown student')
                        . ' booked ' . ($booking->studio->studio_name ?? 'a studio'),
                    'status'      => ucfirst($booking->booking_status),
                ];
            });

        $maintenance = Maintenance::with(['item', 'staff'])
            ->where('maintenance_status', 'pending')
            ->orderByDesc('report_date')
            ->limit(8)
            ->get()
            ->map(function ($maintenance) {
                return [
                    'type'        => 'Maintenance',
                    'date'        => Carbon::parse($maintenance->report_date),
                    'description' => ucfirst($maintenance->issue_type ?? 'Issue') . ' reported for '
                        . ($maintenance->item->item_name ?? 'an item')
                        . ' by ' . ($maintenance->staff->full_name ?? 'unknown staff'),
                    'status'      => ucfirst($maintenance->maintenance_status),
                ];
            });

        return $bookings->concat($maintenance)
            ->sortByDesc('date')
            ->take(8)
            ->values();
    }
}
