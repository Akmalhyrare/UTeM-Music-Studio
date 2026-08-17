@extends($layout)

@section('page-title', 'Search Results')

@section('content')

<style>
    .search-section { margin-bottom: 24px; }
    .search-section h6 { font-size:13px; font-weight:700; color:#211C36; margin-bottom:10px; text-transform:uppercase; letter-spacing:0.04em; }
    .search-result-card {
        background:#fff; border:1px solid #eee; border-radius:10px;
        padding:12px 16px; margin-bottom:8px;
        display:flex; justify-content:space-between; align-items:center; gap:12px;
        text-decoration:none; color:#333; transition:all 0.15s;
    }
    .search-result-card:hover { border-color:#7C3AED; box-shadow:0 2px 8px rgba(0,0,0,0.06); color:#333; }
    .search-result-title { font-size:13px; font-weight:600; color:#211C36; }
    .search-result-meta { font-size:12px; color:#888; }
    .search-empty { text-align:center; color:#888; padding:60px 20px; font-size:14px; }
</style>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ $isStaff ? route('staff.search') : route('student.search') }}" data-instant-search>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <input type="text"
                       name="q"
                       class="form-control"
                       style="font-size:13px; max-width:350px;"
                       placeholder="Search everything: items, names, references..."
                       value="{{ $term }}"
                       autofocus>
                <button type="submit" class="btn btn-sm" style="background:#7C3AED;color:#fff;border-radius:8px;">
                    🔍 Search
                </button>
            </div>
        </form>
    </div>
</div>

@if($term === '')
    <div class="search-empty">
        <div style="font-size:40px; margin-bottom:12px;">🔍</div>
        <p>Type something above to search across the whole system.</p>
    </div>
@elseif(collect($results)->every(fn ($r) => $r->isEmpty()))
    <div class="search-empty">
        <div style="font-size:40px; margin-bottom:12px;">🤷</div>
        <p>No results found for "<strong>{{ $term }}</strong>".</p>
        <p style="font-size:12px;">Try a different keyword — partial words and small typos are okay.</p>
    </div>
@else

    {{-- ITEMS --}}
    @if(isset($results['items']) && $results['items']->isNotEmpty())
    <div class="search-section">
        <h6>📦 Items ({{ $results['items']->count() }})</h6>
        @foreach($results['items'] as $item)
            <a href="{{ $isStaff ? route('inventory.edit', $item->item_id) : route('items.browse') }}" class="search-result-card">
                <div>
                    <div class="search-result-title">{{ $item->item_name }}</div>
                    <div class="search-result-meta">
                        {{ $item->category->category_name ?? '-' }}
                    </div>
                </div>
                <x-status-badge :status="$item->item_status" />
            </a>
        @endforeach
    </div>
    @endif

    {{-- STUDENTS --}}
    @if(isset($results['students']) && $results['students']->isNotEmpty())
    <div class="search-section">
        <h6>🎓 Students ({{ $results['students']->count() }})</h6>
        @foreach($results['students'] as $student)
            <a href="{{ session('is_admin') ? route('users.student.edit', $student->student_id) : '#' }}" class="search-result-card">
                <div>
                    <div class="search-result-title">{{ $student->full_name }}</div>
                    <div class="search-result-meta">{{ $student->email }} · {{ $student->matric_no }}</div>
                </div>
                <x-status-badge :status="$student->status" />
            </a>
        @endforeach
    </div>
    @endif

    {{-- STAFF --}}
    @if(isset($results['staff']) && $results['staff']->isNotEmpty())
    <div class="search-section">
        <h6>👷 Staff ({{ $results['staff']->count() }})</h6>
        @foreach($results['staff'] as $member)
            <a href="{{ session('is_admin') ? route('users.staff.edit', $member->staff_id) : '#' }}" class="search-result-card">
                <div>
                    <div class="search-result-title">{{ $member->full_name }}</div>
                    <div class="search-result-meta">{{ $member->email }} · {{ $member->position ?? '-' }}</div>
                </div>
                <span class="badge" style="background:{{ $member->is_admin ? '#F3E8FF' : '#FAEEDA' }};color:{{ $member->is_admin ? '#5B21B6' : '#633806' }};">
                    {{ $member->is_admin ? 'Admin' : 'Staff' }}
                </span>
            </a>
        @endforeach
    </div>
    @endif

    {{-- BOOKINGS --}}
    @if(isset($results['bookings']) && $results['bookings']->isNotEmpty())
    <div class="search-section">
        <h6>📅 Bookings ({{ $results['bookings']->count() }})</h6>
        @foreach($results['bookings'] as $booking)
            <a href="{{ $isStaff ? route('staff.bookings.show', $booking->booking_id) : route('bookings.show', $booking->booking_id) }}" class="search-result-card">
                <div>
                    <div class="search-result-title">
                        Booking #{{ $booking->booking_id }}
                        @if($isStaff && $booking->student) — {{ $booking->student->full_name }} @endif
                    </div>
                    <div class="search-result-meta">
                        {{ $booking->studio->studio_name ?? '-' }} ·
                        {{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d M Y') }}
                        @if($booking->purpose) · {{ Str::limit($booking->purpose, 40) }} @endif
                    </div>
                </div>
                <x-status-badge :status="$booking->booking_status" />
            </a>
        @endforeach
    </div>
    @endif

    {{-- BORROWINGS --}}
    @if(isset($results['borrowings']) && $results['borrowings']->isNotEmpty())
    <div class="search-section">
        <h6>📋 Borrowings ({{ $results['borrowings']->count() }})</h6>
        @foreach($results['borrowings'] as $borrowing)
            <a href="{{ $isStaff ? route('staff.borrowings.show', $borrowing->borrow_id) : route('borrowings.show', $borrowing->borrow_id) }}" class="search-result-card">
                <div>
                    <div class="search-result-title">
                        Loan #{{ $borrowing->borrow_id }}
                        @if($isStaff && $borrowing->student) — {{ $borrowing->student->full_name }} @endif
                    </div>
                    <div class="search-result-meta">
                        @foreach($borrowing->borrowingDetails as $detail)
                            {{ $detail->item->item_name ?? '-' }}@if(!$loop->last), @endif
                        @endforeach
                    </div>
                </div>
                <x-status-badge :status="$borrowing->borrow_status" />
            </a>
        @endforeach
    </div>
    @endif

    {{-- MAINTENANCE --}}
    @if(isset($results['maintenance']) && $results['maintenance']->isNotEmpty())
    <div class="search-section">
        <h6>🔧 Maintenance & Damage Reports ({{ $results['maintenance']->count() }})</h6>
        @foreach($results['maintenance'] as $maintenance)
            <a href="{{ route('staff.maintenance.index') }}" class="search-result-card">
                <div>
                    <div class="search-result-title">{{ $maintenance->item->item_name ?? '-' }} — {{ ucfirst($maintenance->issue_type ?? '-') }}</div>
                    <div class="search-result-meta">{{ Str::limit($maintenance->description ?? '-', 60) }}</div>
                </div>
                <x-status-badge :status="$maintenance->maintenance_status" />
            </a>
        @endforeach
    </div>
    @endif

@endif

@endsection
