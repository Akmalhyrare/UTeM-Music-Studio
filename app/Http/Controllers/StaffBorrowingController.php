<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\ProcessReturnRequest;
use App\Models\Borrowing;
use App\Models\Item;
use App\Models\Student;
use App\Services\BorrowingService;
use App\Support\Search;
use Illuminate\Http\Request;

class StaffBorrowingController extends Controller
{
    public function __construct(private BorrowingService $borrowingService)
    {
    }

    // List all borrowing requests with optional status filter
    public function index(Request $request)
    {
        $query = Borrowing::with(['student', 'borrowingDetails.item']);

        Search::apply($query, $request->search, ['purpose'], [
            'student'                 => Student::searchableColumns(),
            'borrowingDetails.item'   => Item::searchableColumns(),
        ]);

        if ($request->status) {
            $query->where('borrow_status', $request->status);
        }

        // Workflow-priority ordering:
        // 1. Status sequence: pending -> reserved -> collected -> returned
        //    -> rejected -> cancelled.
        // 2. Within each status: request date (created_at) ascending,
        //    then pickup date ascending.
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
                             ->paginate(15)
                             ->withQueryString();
        $pendingCount = Borrowing::where('borrow_status', 'pending')->count();

        return view('staff.borrowings.index', compact('borrowings', 'pendingCount'));
    }

    // Show a single borrowing request
    public function show($id)
    {
        $borrowing = Borrowing::with(['student', 'staff', 'borrowingDetails.item', 'returnRecords.item'])
                               ->findOrFail($id);

        return view('staff.borrowings.show', compact('borrowing'));
    }

    // Approve a pending request — moves it to 'reserved' (availability is computed live from active reservations, not decremented on a stored column)
    public function approve($id)
    {
        $borrowing = Borrowing::findOrFail($id);

        if ($borrowing->borrow_status !== 'pending') {
            return redirect()->route('staff.borrowings.show', $borrowing->borrow_id)
                              ->with('error', 'Only pending requests can be approved.');
        }

        try {
            $this->borrowingService->approveBorrowing($borrowing, session('user_id'));
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('staff.borrowings.show', $borrowing->borrow_id)
                          ->with('success', 'Borrowing request approved and items issued.');
    }

    // Reject a pending request
    public function reject($id)
    {
        $borrowing = Borrowing::findOrFail($id);

        if ($borrowing->borrow_status !== 'pending') {
            return redirect()->route('staff.borrowings.show', $borrowing->borrow_id)
                              ->with('error', 'Only pending requests can be rejected.');
        }

        $this->borrowingService->rejectBorrowing($borrowing, session('user_id'));

        return redirect()->route('staff.borrowings.show', $borrowing->borrow_id)
                          ->with('success', 'Borrowing request rejected.');
    }

    // Mark a reserved borrowing's items as collected
    public function collect($id)
    {
        $borrowing = Borrowing::findOrFail($id);

        if ($borrowing->borrow_status !== 'reserved') {
            return redirect()->route('staff.borrowings.show', $borrowing->borrow_id)
                              ->with('error', 'Only reserved requests can be marked as collected.');
        }

        $this->borrowingService->collectBorrowing($borrowing, session('user_id'));

        return redirect()->route('staff.borrowings.show', $borrowing->borrow_id)
                          ->with('success', 'Items marked as collected.');
    }

    // Process item returns for a collected borrowing
    public function processReturn(ProcessReturnRequest $request, $id)
    {
        $borrowing = Borrowing::findOrFail($id);

        if ($borrowing->borrow_status !== 'collected') {
            return redirect()->route('staff.borrowings.show', $borrowing->borrow_id)
                              ->with('error', 'Only collected borrowings can be returned.');
        }

        $this->borrowingService->processReturn($borrowing, $request->validated()['returns'], session('user_id'));

        return redirect()->route('staff.borrowings.show', $borrowing->borrow_id)
                          ->with('success', 'Return processed successfully.');
    }
}
