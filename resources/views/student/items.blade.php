@extends('layouts.student')

@section('content')

<style>
    .page-header {
        background: #1a1a2e;
        color: #fff;
        padding: 40px;
        text-align: center;
    }
    .page-header h2 { font-size: 26px; font-weight: 700; margin-bottom: 6px; }
    .page-header p { color: rgba(255,255,255,0.6); font-size: 14px; }

    .filter-bar {
        background: #fff;
        padding: 16px 40px;
        border-bottom: 1px solid #eee;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .filter-bar input, .filter-bar select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 13px;
        color: #333;
    }
    .filter-bar input { flex: 1; min-width: 200px; }
    .btn-search {
        background: #1D9E75; color: #fff;
        border: none; padding: 8px 20px;
        border-radius: 8px; font-size: 13px;
        font-weight: 500; cursor: pointer;
        text-decoration: none;
    }
    .btn-reset {
        background: #f0f0f0; color: #555;
        border: none; padding: 8px 16px;
        border-radius: 8px; font-size: 13px;
        cursor: pointer; text-decoration: none;
    }

    .items-container { max-width: 1100px; margin: 0 auto; padding: 40px; }
    .items-count { font-size: 13px; color: #888; margin-bottom: 20px; }
    .items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
    .item-card {
        background: #fff; border-radius: 12px;
        overflow: hidden; border: 1px solid #eee;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .item-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
    .item-img {
        width: 100%; height: 140px;
        background: #f5f5f5;
        display: flex; align-items: center;
        justify-content: center; font-size: 40px;
        overflow: hidden;
    }
    .item-img img { width: 100%; height: 100%; object-fit: cover; }
    .item-body { padding: 12px; }
    .item-name { font-size: 13px; font-weight: 600; color: #1a1a2e; margin-bottom: 3px; }
    .item-cat { font-size: 11px; color: #888; margin-bottom: 8px; }
    .item-qty { font-size: 11px; color: #555; margin-bottom: 8px; }
    .item-footer { display: flex; justify-content: space-between; align-items: center; }
    .badge-avail { background: #E1F5EE; color: #085041; padding: 3px 8px; border-radius: 99px; font-size: 11px; font-weight: 500; }
    .badge-unavail { background: #FAECE7; color: #712B13; padding: 3px 8px; border-radius: 99px; font-size: 11px; font-weight: 500; }
    .btn-borrow {
        background: #1D9E75; color: #fff;
        padding: 5px 12px; border-radius: 6px;
        font-size: 11px; font-weight: 500;
        text-decoration: none; transition: all 0.2s;
    }
    .btn-borrow:hover { background: #0F6E56; color: #fff; }
    .btn-borrow-dis {
        background: #e0e0e0; color: #999;
        padding: 5px 12px; border-radius: 6px;
        font-size: 11px; cursor: not-allowed;
    }
</style>

{{-- PAGE HEADER --}}
<div class="page-header">
    <h2>📦 Browse Equipment & Attire</h2>
    <p>Find and request items available at UTeM Music Studio</p>
</div>

{{-- FILTER BAR --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('items.browse') }}" style="display:flex; gap:10px; flex-wrap:wrap; width:100%; align-items:center;">
        <input type="text"
               name="search"
               placeholder="Search item name..."
               value="{{ request('search') }}">
        <select name="type">
            <option value="">All types</option>
            <option value="equipment" {{ request('type') == 'equipment' ? 'selected' : '' }}>Equipment</option>
            <option value="attire" {{ request('type') == 'attire' ? 'selected' : '' }}>Attire</option>
        </select>
        <select name="category">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->category_id }}" {{ request('category') == $cat->category_id ? 'selected' : '' }}>
                    {{ $cat->category_name }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn-search">🔍 Search</button>
        <a href="{{ route('items.browse') }}" class="btn-reset">Reset</a>
    </form>
</div>

{{-- ITEMS GRID --}}
<div class="items-container">
    <div class="items-count">Showing {{ $items->count() }} items</div>
    <div class="items-grid">
        @forelse($items as $item)
        <div class="item-card">
            <div class="item-img">
                @if($item->image)
                    <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->item_name }}">
                @else
                    📦
                @endif
            </div>
            <div class="item-body">
                <div class="item-name">{{ $item->item_name }}</div>
                <div class="item-cat">{{ $item->category->category_name ?? '-' }}</div>
                <div class="item-qty">{{ $item->available_quantity }} of {{ $item->quantity }} available</div>
                <div class="item-footer">
                    @if($item->available_quantity > 0)
                        <span class="badge-avail">Available</span>
                        @if(session('user_type') === 'student')
                            <a href="#" class="btn-borrow">Borrow</a>
                        @else
                            <a href="{{ route('login') }}" class="btn-borrow">Login to Borrow</a>
                        @endif
                    @else
                        <span class="badge-unavail">Unavailable</span>
                        <span class="btn-borrow-dis">Unavailable</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div style="grid-column:1/-1; text-align:center; color:#888; padding:60px;">
            <div style="font-size:40px; margin-bottom:12px;">📦</div>
            <p>No items found.</p>
        </div>
        @endforelse
    </div>
</div>

@endsection