<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Staff;
use App\Services\BorrowingService;
use App\Support\Search;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function __construct(private BorrowingService $borrowingService)
    {
    }

    // List maintenance records with optional status filter
    public function index(Request $request)
    {
        $query = Maintenance::with(['item', 'staff', 'returnRecord']);

        Search::apply($query, $request->search, ['issue_type', 'description'], [
            'item'  => Item::searchableColumns(),
            'staff' => Staff::searchableColumns(),
        ]);

        if ($request->status) {
            $query->where('maintenance_status', $request->status);
        }

        $maintenances = $query->orderBy('report_date', 'desc')->paginate(15)->withQueryString();

        return view('staff.maintenance.index', compact('maintenances'));
    }

    // Mark a maintenance issue as resolved and return the item to available stock
    public function resolve($id)
    {
        $maintenance = Maintenance::findOrFail($id);

        if ($maintenance->maintenance_status === 'resolved') {
            return redirect()->route('staff.maintenance.index')
                              ->with('error', 'This issue has already been resolved.');
        }

        $this->borrowingService->resolveMaintenance($maintenance);

        return redirect()->route('staff.maintenance.index')
                          ->with('success', 'Maintenance issue marked as resolved. Item returned to stock.');
    }
}
