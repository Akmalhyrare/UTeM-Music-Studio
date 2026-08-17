<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ ucfirst($type) }} Report</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1f2937; font-size: 11px; }

        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #7C3AED; padding-bottom: 10px; }
        .header .system-title { font-size: 16px; font-weight: 700; color: #7C3AED; margin: 0; }
        .header .report-title { font-size: 14px; font-weight: 600; margin: 4px 0 0; }
        .header .meta { font-size: 10px; color: #6b7280; margin-top: 4px; }

        .summary { margin: 14px 0; }
        .summary table { width: 100%; border-collapse: collapse; }
        .summary td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: center;
        }
        .summary .label { font-size: 9px; color: #6b7280; display: block; }
        .summary .value { font-size: 14px; font-weight: 700; }

        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td {
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
            font-size: 10px;
            text-align: left;
        }
        table.data th {
            background: #f3f4f6;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            color: #4b5563;
        }
        table.data tr:nth-child(even) td { background: #fafafa; }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
        }
        .badge-confirmed, .badge-reserved { background: #DBEAFE; color: #1E40AF; }
        .badge-completed, .badge-returned, .badge-collected { background: #D1FAE5; color: #065F46; }
        .badge-cancelled, .badge-rejected { background: #FEE2E2; color: #991B1B; }
        .badge-pending { background: #FEF3C7; color: #92400E; }
        .badge-available { background: #D1FAE5; color: #065F46; }
        .badge-unavailable { background: #FEE2E2; color: #991B1B; }

        .footer { margin-top: 16px; font-size: 9px; color: #9ca3af; text-align: right; }
    </style>
</head>
<body>

<div class="header">
    <p class="system-title">Music Studio Management System</p>
    <p class="report-title">
        @if($type === 'bookings') Booking Report
        @elseif($type === 'borrowings') Borrowing Report
        @else Inventory Report
        @endif
    </p>
    <p class="meta">
        @if(!empty($filters['date_from']) || !empty($filters['date_to']))
            Date Range:
            {{ !empty($filters['date_from']) ? \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d M Y') : 'Beginning' }}
            &ndash;
            {{ !empty($filters['date_to']) ? \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d M Y') : 'Present' }}
            &nbsp;|&nbsp;
        @endif
        Generated: {{ now()->format('d M Y, H:i') }}
        @if(!empty($filters['status']))
            &nbsp;|&nbsp; Status: {{ ucfirst($filters['status']) }}
        @endif
    </p>
</div>

{{-- SUMMARY --}}
<div class="summary">
    <table>
        @if($type === 'bookings')
            <tr>
                <td><span class="label">Total Bookings</span><span class="value">{{ $summary['total'] }}</span></td>
                <td><span class="label">Confirmed</span><span class="value">{{ $summary['confirmed'] }}</span></td>
                <td><span class="label">Completed</span><span class="value">{{ $summary['completed'] }}</span></td>
                <td><span class="label">Cancelled</span><span class="value">{{ $summary['cancelled'] }}</span></td>
            </tr>
        @elseif($type === 'borrowings')
            <tr>
                <td><span class="label">Total Requests</span><span class="value">{{ $summary['total'] }}</span></td>
                <td><span class="label">Pending</span><span class="value">{{ $summary['pending'] }}</span></td>
                <td><span class="label">Reserved</span><span class="value">{{ $summary['reserved'] }}</span></td>
                <td><span class="label">Collected</span><span class="value">{{ $summary['collected'] }}</span></td>
                <td><span class="label">Returned</span><span class="value">{{ $summary['returned'] }}</span></td>
                <td><span class="label">Rejected/Cancelled</span><span class="value">{{ $summary['rejected'] + $summary['cancelled'] }}</span></td>
                <td><span class="label">Items Borrowed</span><span class="value">{{ $summary['items_borrowed'] }}</span></td>
            </tr>
        @else
            <tr>
                <td><span class="label">Total Items</span><span class="value">{{ $summary['total_items'] }}</span></td>
                <td><span class="label">Total Quantity</span><span class="value">{{ $summary['total_quantity'] }}</span></td>
                <td><span class="label">Available Quantity</span><span class="value">{{ $summary['total_available'] }}</span></td>
                <td><span class="label">Low Stock (&le;20%)</span><span class="value">{{ $summary['low_stock'] }}</span></td>
            </tr>
        @endif
    </table>
</div>

{{-- DATA TABLE --}}
@if($type === 'bookings')
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>Studio</th>
                <th>Student</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Purpose</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $booking)
                <tr>
                    <td>{{ $booking->booking_id }}</td>
                    <td>{{ $booking->studio->studio_name ?? '-' }}</td>
                    <td>{{ $booking->student->full_name ?? '-' }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d M Y') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('H:i') }} - {{ \Illuminate\Support\Carbon::parse($booking->end_time)->format('H:i') }}</td>
                    <td><span class="badge badge-{{ $booking->booking_status }}">{{ ucfirst($booking->booking_status) }}</span></td>
                    <td>{{ Str::limit($booking->purpose ?? '-', 40) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;">No bookings found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@elseif($type === 'borrowings')
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>Items</th>
                <th>Student</th>
                <th>Pickup Date</th>
                <th>Return Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $borrowing)
                <tr>
                    <td>{{ $borrowing->borrow_id }}</td>
                    <td>{{ $borrowing->borrowingDetails->map(fn($d) => ($d->item->item_name ?? '-') . ' (x' . $d->quantity_borrowed . ')')->join(', ') }}</td>
                    <td>{{ $borrowing->student->full_name ?? '-' }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($borrowing->pickup_date)->format('d M Y') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($borrowing->return_date)->format('d M Y') }}</td>
                    <td>
                        <span class="badge badge-{{ $borrowing->borrow_status }}">{{ ucfirst($borrowing->borrow_status) }}</span>
                        @if($borrowing->is_overdue)
                            <span class="badge badge-cancelled">Overdue</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">No borrowings found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@else
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Available</th>
                <th>Condition</th>
                <th>Status</th>
                <th>Date Added</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $item)
                <tr>
                    <td>{{ $item->item_id }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->category->category_name ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->available_quantity }}</td>
                    <td>{{ ucfirst($item->condition_status) }}</td>
                    <td><span class="badge badge-{{ $item->item_status }}">{{ ucfirst($item->item_status) }}</span></td>
                    <td>{{ \Illuminate\Support\Carbon::parse($item->date_added)->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;">No items found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif

<div class="footer">
    Music Studio Management System &mdash; {{ count($results) }} record(s)
</div>

</body>
</html>
