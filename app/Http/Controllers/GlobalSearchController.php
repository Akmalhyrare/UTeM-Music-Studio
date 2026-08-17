<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Borrowing;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Staff;
use App\Models\Student;
use App\Support\Search;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /** Max results returned per category. */
    private const LIMIT = 10;

    // Unified search across items, users, bookings, borrowings and maintenance
    public function index(Request $request)
    {
        $term    = trim((string) $request->query('q', ''));
        $isStaff = session('user_type') === 'staff';
        $results = [];

        if ($term !== '') {
            $itemsQuery = Item::with('category');
            Search::apply($itemsQuery, $term, Item::searchableColumns(), [
                'category' => ['category_name'],
            ]);
            $results['items'] = $itemsQuery->limit(self::LIMIT)->get();

            if ($isStaff) {
                $studentsQuery = Student::query();
                Search::apply($studentsQuery, $term, Student::searchableColumns());
                $results['students'] = $studentsQuery->limit(self::LIMIT)->get();

                $staffQuery = Staff::query();
                Search::apply($staffQuery, $term, Staff::searchableColumns());
                $results['staff'] = $staffQuery->limit(self::LIMIT)->get();

                $bookingsQuery = Booking::with(['student', 'studio']);
                Search::apply($bookingsQuery, $term, ['purpose'], [
                    'student' => Student::searchableColumns(),
                    'studio'  => ['studio_name'],
                ]);
                $results['bookings'] = $bookingsQuery->limit(self::LIMIT)->get();

                $borrowingsQuery = Borrowing::with(['student', 'borrowingDetails.item']);
                Search::apply($borrowingsQuery, $term, ['purpose'], [
                    'student'               => Student::searchableColumns(),
                    'borrowingDetails.item' => Item::searchableColumns(),
                ]);
                $results['borrowings'] = $borrowingsQuery->limit(self::LIMIT)->get();

                $maintenanceQuery = Maintenance::with(['item', 'staff']);
                Search::apply($maintenanceQuery, $term, ['issue_type', 'description'], [
                    'item'  => Item::searchableColumns(),
                    'staff' => Staff::searchableColumns(),
                ]);
                $results['maintenance'] = $maintenanceQuery->limit(self::LIMIT)->get();
            } else {
                $studentId = session('user_id');

                $bookingsQuery = Booking::with('studio')->where('student_id', $studentId);
                Search::apply($bookingsQuery, $term, ['purpose'], [
                    'studio' => ['studio_name'],
                ]);
                $results['bookings'] = $bookingsQuery->limit(self::LIMIT)->get();

                $borrowingsQuery = Borrowing::with('borrowingDetails.item')->where('student_id', $studentId);
                Search::apply($borrowingsQuery, $term, ['purpose'], [
                    'borrowingDetails.item' => Item::searchableColumns(),
                ]);
                $results['borrowings'] = $borrowingsQuery->limit(self::LIMIT)->get();
            }
        }

        $layout = $isStaff ? 'layouts.app' : 'layouts.student';

        return view('search.results', compact('term', 'results', 'isStaff', 'layout'));
    }
}
