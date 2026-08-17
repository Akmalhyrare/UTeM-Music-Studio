<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBorrowingRequest;
use App\Models\Borrowing;
use App\Models\Item;
use App\Services\BorrowingService;
use App\Support\Search;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{
    public function __construct(private BorrowingService $borrowingService)
    {
    }

    // List the logged-in student's borrowing requests
    public function index(Request $request)
    {
        $query = Borrowing::with('borrowingDetails.item')->where('student_id', session('user_id'));

        Search::apply($query, $request->search, ['purpose'], [
            'borrowingDetails.item' => Item::searchableColumns(),
        ]);

        if ($request->status) {
            $query->where('borrow_status', $request->status);
        }

        // Workflow-priority ordering (see StaffBorrowingController::index
        // for the full explanation): status sequence pending -> reserved
        // -> collected -> returned -> rejected -> cancelled, then by
        // request date ascending, then pickup date ascending.
        $borrowings = $query->orderByRaw("
                                 CASE borrow_status
                                     WHEN 'pending'   THEN 1
                                     WHEN 'reserved'  THEN 2
                                     WHEN 'collected' THEN 3
                                     WHEN 'returned'  THEN 4
                                     WHEN 'rejected'  THEN 5
                                     WHEN 'cancelled' THEN 6
                                     ELSE 7
                                 END
                             ")
                             ->orderBy('created_at', 'asc')
                             ->orderBy('pickup_date', 'asc')
                             ->get();

        return view('borrowings.index', compact('borrowings'));
    }

    // Show borrowing request form
    public function create(Request $request)
    {
        $items = Item::with('category')->where('item_status', 'available')
            ->where('available_quantity', '>', 0)
            ->orderBy('item_name')
            ->get();

        $selectedItem = $request->query('item_id');

        return view('borrowings.create', compact('items', 'selectedItem'));
    }

    // Store a new borrowing request (pending staff approval)
    public function store(StoreBorrowingRequest $request)
    {
        try {
            $this->borrowingService->createBorrowing($request->validated(), session('user_id'));
        } catch (\App\Exceptions\InsufficientStockException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('borrowings.index')
                          ->with('success', 'Borrowing request submitted! Awaiting staff approval.');
    }

    // AJAX: how many units of an item are free for a given date range
    public function availability(Request $request)
    {
        $request->validate([
            'item_id'     => 'required|integer|exists:items,item_id',
            'pickup_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:pickup_date',
        ]);

        $available = $this->borrowingService->getAvailableQuantity(
            (int) $request->query('item_id'),
            $request->query('pickup_date'),
            $request->query('return_date')
        );

        return response()->json(['available' => $available]);
    }

    // Show a single borrowing request belonging to the logged-in student
    public function show($id)
    {
        $borrowing = Borrowing::with(['borrowingDetails.item', 'returnRecords.item', 'staff'])
                               ->where('student_id', session('user_id'))
                               ->findOrFail($id);

        return view('borrowings.show', compact('borrowing'));
    }

    // Cancel a pending or reserved borrowing request
    public function destroy($id)
    {
        $borrowing = Borrowing::where('student_id', session('user_id'))->findOrFail($id);

        if (!in_array($borrowing->borrow_status, ['pending', 'reserved'])) {
            return redirect()->route('borrowings.index')
                              ->with('error', 'Only pending or reserved requests can be cancelled.');
        }

        $this->borrowingService->cancelBorrowing($borrowing);

        return redirect()->route('borrowings.index')
                          ->with('success', 'Borrowing request cancelled.');
    }
}
