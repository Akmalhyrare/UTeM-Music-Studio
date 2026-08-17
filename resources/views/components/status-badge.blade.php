@php
    $map = [
        // Green — available / success / approved
        'available'   => ['bg' => '#D1FAE5', 'text' => '#065F46'],
        'approved'    => ['bg' => '#D1FAE5', 'text' => '#065F46'],
        'reserved'    => ['bg' => '#D1FAE5', 'text' => '#065F46'],
        'confirmed'   => ['bg' => '#D1FAE5', 'text' => '#065F46'],
        'active'      => ['bg' => '#D1FAE5', 'text' => '#065F46'],
        'good'        => ['bg' => '#D1FAE5', 'text' => '#065F46'],
        'resolved'    => ['bg' => '#D1FAE5', 'text' => '#065F46'],

        // Yellow / amber — pending / awaiting action
        'pending'     => ['bg' => '#FEF3C7', 'text' => '#92400E'],
        'fair'        => ['bg' => '#FEF3C7', 'text' => '#92400E'],
        'low_stock'   => ['bg' => '#FEF3C7', 'text' => '#92400E'],

        // Red — rejected / cancelled / unavailable / negative
        'rejected'    => ['bg' => '#FEE2E2', 'text' => '#991B1B'],
        'cancelled'   => ['bg' => '#FEE2E2', 'text' => '#991B1B'],
        'unavailable' => ['bg' => '#FEE2E2', 'text' => '#991B1B'],
        'poor'        => ['bg' => '#FEE2E2', 'text' => '#991B1B'],
        'damaged'     => ['bg' => '#FEE2E2', 'text' => '#991B1B'],
        'lost'        => ['bg' => '#FEE2E2', 'text' => '#991B1B'],
        'overdue'     => ['bg' => '#FEE2E2', 'text' => '#991B1B'],
        'out_of_stock'=> ['bg' => '#FEE2E2', 'text' => '#991B1B'],

        // Orange — maintenance
        'maintenance' => ['bg' => '#FFEDD5', 'text' => '#9A3412'],

        // Grey — blocked
        'blocked'     => ['bg' => '#F3F4F6', 'text' => '#4B5563'],

        // Dark grey — inactive
        'inactive'    => ['bg' => '#E5E7EB', 'text' => '#374151'],

        // Blue — informational / completed / returned
        'completed'   => ['bg' => '#DBEAFE', 'text' => '#1E40AF'],
        'returned'    => ['bg' => '#DBEAFE', 'text' => '#1E40AF'],
        'collected'   => ['bg' => '#DBEAFE', 'text' => '#1E40AF'],
        'equipment'   => ['bg' => '#DBEAFE', 'text' => '#1E40AF'],
        'info'        => ['bg' => '#DBEAFE', 'text' => '#1E40AF'],
    ];

    $key = strtolower(str_replace(' ', '_', $status ?? ''));
    $colors = $map[$key] ?? ['bg' => '#F3F4F6', 'text' => '#4B5563'];
@endphp
<span {{ $attributes->merge(['class' => 'badge']) }} style="background:{{ $colors['bg'] }};color:{{ $colors['text'] }};">{{ $label ?? ucfirst($status ?? '') }}</span>
