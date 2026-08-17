@extends('layouts.student')

@section('content')

<style>
    .page-header {
        background-image: linear-gradient(rgba(33,28,54,0.75), rgba(33,28,54,0.75)), url("{{ asset('images/studio-control-room.jpg') }}");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
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
        background: #7C3AED; color: #fff;
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
        cursor: pointer;
    }
    .item-card:hover { transform: translateY(-4px); box-shadow: 0 10px 26px rgba(0,0,0,0.12); }
    .item-card:focus-visible { outline: 2px solid #7C3AED; outline-offset: 3px; }
    .item-img {
        width: 100%; height: 170px;
        background: #f5f5f5;
        display: flex; align-items: center;
        justify-content: center; font-size: 40px;
        overflow: hidden;
    }
    .item-img img { width: 100%; height: 100%; object-fit: cover; }
    .item-body { padding: 12px; }
    .item-name { font-size: 13px; font-weight: 600; color: #211C36; margin-bottom: 3px; }
    .item-cat { font-size: 11px; color: #888; margin-bottom: 4px; }
    .item-qty { font-size: 11px; color: #555; margin: 6px 0 8px; }
    .item-footer { display: flex; justify-content: space-between; align-items: center; gap: 6px; flex-wrap: wrap; }
    .badge-avail { background: #D1FAE5; color: #065F46; padding: 3px 8px; border-radius: 99px; font-size: 11px; font-weight: 500; }
    .badge-out { background: #FEE2E2; color: #991B1B; padding: 3px 8px; border-radius: 99px; font-size: 11px; font-weight: 500; }
    .badge-low { background: #FFF6E0; color: #8A6100; padding: 3px 8px; border-radius: 99px; font-size: 11px; font-weight: 500; }
    .badge-maintenance { background: #FFEDD5; color: #9A3412; padding: 3px 8px; border-radius: 99px; font-size: 11px; font-weight: 500; }
    .btn-borrow {
        background: #7C3AED; color: #fff;
        padding: 5px 12px; border-radius: 6px;
        font-size: 11px; font-weight: 500;
        text-decoration: none; transition: all 0.2s;
    }
    .btn-borrow:hover { background: #6D28D9; color: #fff; }
    .btn-borrow-dis {
        background: #e0e0e0; color: #999;
        padding: 5px 12px; border-radius: 6px;
        font-size: 11px; cursor: not-allowed;
    }

    /* ── ITEM DETAIL MODAL ───────────────────────── */
    .item-modal {
        position: fixed; inset: 0; z-index: 2000;
        background: rgba(0,0,0,0.6);
        display: none; align-items: center; justify-content: center;
        padding: 20px;
    }
    .item-modal.open { display: flex; }
    .item-modal-content {
        background: #fff; border-radius: 14px;
        width: 100%; max-width: 800px; max-height: 90vh;
        overflow-y: auto; position: relative;
        display: flex; flex-direction: column;
    }
    @media (min-width: 768px) {
        .item-modal-content { flex-direction: row; }
    }
    .item-modal-close {
        position: absolute; top: 10px; right: 10px; z-index: 10;
        width: 32px; height: 32px; border-radius: 50%;
        background: rgba(0,0,0,0.5); color: #fff; border: none;
        font-size: 18px; line-height: 1; cursor: pointer;
    }
    .item-modal-gallery {
        flex: 1 1 50%; background: #f0f0f0;
        display: flex; flex-direction: column;
    }
    .item-modal-mainimg-wrap {
        height: 260px; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        background: #f0f0f0; cursor: zoom-in;
    }
    @media (min-width: 768px) {
        .item-modal-mainimg-wrap { height: 100%; min-height: 420px; }
    }
    .item-modal-mainimg-wrap img {
        width: 100%; height: 100%; object-fit: contain;
        transition: transform 0.25s ease;
    }
    .item-modal-mainimg-wrap.zoomed img { transform: scale(1.8); cursor: zoom-out; }
    .item-modal-mainimg-wrap .item-modal-placeholder { font-size: 64px; }
    .item-modal-thumbs {
        display: flex; gap: 8px; padding: 10px; overflow-x: auto;
        background: #fff;
    }
    .item-modal-thumb {
        width: 56px; height: 56px; border-radius: 8px;
        object-fit: cover; cursor: pointer; flex-shrink: 0;
        border: 2px solid transparent; transition: border-color 0.15s;
    }
    .item-modal-thumb.active { border-color: #7C3AED; }
    .item-modal-body { flex: 1 1 50%; padding: 24px; }
    .item-modal-body h4 { font-size: 18px; font-weight: 700; color: #211C36; margin-bottom: 8px; }
    .item-modal-desc { font-size: 13px; color: #555; line-height: 1.6; margin: 14px 0; }
    .item-modal-details { margin-top: 6px; }
    .item-modal-detail-row {
        display: flex; justify-content: space-between; gap: 12px;
        padding: 8px 0; border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }
    .item-modal-detail-row .label { color: #888; }
    .item-modal-detail-row .value { color: #211C36; font-weight: 500; text-align: right; }
</style>

{{-- PAGE HEADER --}}
<div class="page-header">
    <h2>📦 Browse Equipment & Attire</h2>
    <p>Find and request items available at UTeM Music Studio</p>
</div>

{{-- FILTER BAR --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('items.browse') }}" data-instant-search style="display:flex; gap:10px; flex-wrap:wrap; width:100%; align-items:center;">
        <input type="text"
               name="search"
               placeholder="Search name, brand, model, tag..."
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
        @php
            $badge = $item->availabilityBadge();
            $thumb = $item->cardThumbnailUrl();
            $canBorrow = $item->available_quantity > 0 && $item->item_status === 'available';
        @endphp
        <div class="item-card"
             tabindex="0"
             role="button"
             aria-haspopup="dialog"
             aria-label="View details for {{ $item->item_name }}"
             data-modal-target="itemModal{{ $item->item_id }}">
            <div class="item-img">
                @if($thumb)
                    <img src="{{ $thumb }}" alt="{{ $item->item_name }}" loading="lazy">
                @else
                    📦
                @endif
            </div>
            <div class="item-body">
                <div class="item-name">{{ $item->item_name }}</div>
                <div class="item-cat">{{ $item->category->category_name ?? '-' }}</div>
                <div class="item-qty">{{ $item->available_quantity }} of {{ $item->quantity }} available</div>
                <div class="item-footer">
                    <span class="{{ $badge['class'] }}">{{ $badge['label'] }}</span>
                    @if($canBorrow)
                        @if(session('user_type') === 'student')
                            <a href="{{ route('borrowings.create', ['item_id' => $item->item_id]) }}" class="btn-borrow" onclick="event.stopPropagation()">Borrow</a>
                        @else
                            <a href="{{ route('login') }}" class="btn-borrow" onclick="event.stopPropagation()">Login to Borrow</a>
                        @endif
                    @else
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

{{-- ITEM DETAIL MODALS --}}
@foreach($items as $item)
@php
    $gallery = $item->galleryImages();
    $first = $gallery->first();
    $badge = $item->availabilityBadge();
    $canBorrow = $item->available_quantity > 0 && $item->item_status === 'available';
@endphp
<div class="item-modal" id="itemModal{{ $item->item_id }}" role="dialog" aria-modal="true" aria-labelledby="itemModalTitle{{ $item->item_id }}">
    <div class="item-modal-content">
        <button type="button" class="item-modal-close" data-modal-close aria-label="Close">×</button>

        <div class="item-modal-gallery">
            <div class="item-modal-mainimg-wrap" data-zoom-toggle>
                @if($first)
                    <img id="modalImg{{ $item->item_id }}" src="{{ $first->fullUrl() }}" alt="{{ $item->item_name }}">
                @else
                    <div class="item-modal-placeholder">📦</div>
                @endif
            </div>
            @if($gallery->count() > 1)
            <div class="item-modal-thumbs">
                @foreach($gallery as $index => $image)
                    <img src="{{ $image->thumbnailUrl() }}"
                         data-full="{{ $image->fullUrl() }}"
                         data-target="modalImg{{ $item->item_id }}"
                         class="item-modal-thumb {{ $index === 0 ? 'active' : '' }}"
                         alt="{{ $item->item_name }} photo {{ $index + 1 }}"
                         loading="lazy">
                @endforeach
            </div>
            @endif
        </div>

        <div class="item-modal-body">
            <h4 id="itemModalTitle{{ $item->item_id }}">{{ $item->item_name }}</h4>
            <span class="{{ $badge['class'] }}">{{ $badge['label'] }}</span>

            @if($item->item_description)
                <p class="item-modal-desc">{{ $item->item_description }}</p>
            @endif

            <div class="item-modal-details">
                <div class="item-modal-detail-row">
                    <span class="label">Category</span>
                    <span class="value">{{ $item->category->category_name ?? '-' }}</span>
                </div>
                <div class="item-modal-detail-row">
                    <span class="label">Condition</span>
                    <span class="value">{{ ucfirst($item->condition_status) }}</span>
                </div>
                <div class="item-modal-detail-row">
                    <span class="label">Quantity</span>
                    <span class="value">{{ $item->available_quantity }} of {{ $item->quantity }} available</span>
                </div>
            </div>

            <div class="mt-3" style="margin-top:16px;">
                @if($canBorrow)
                    @if(session('user_type') === 'student')
                        <a href="{{ route('borrowings.create', ['item_id' => $item->item_id]) }}" class="btn-borrow">Borrow This Item</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-borrow">Login to Borrow</a>
                    @endif
                @else
                    <span class="btn-borrow-dis">Currently Unavailable</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', () => {
    let activeModal = null;
    let lastFocused = null;

    function openModal(modal, trigger) {
        if (!modal) return;
        activeModal = modal;
        lastFocused = trigger;
        modal.classList.add('open');
        modal.querySelector('[data-modal-close]')?.focus();
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('open');
        modal.querySelector('.item-modal-mainimg-wrap')?.classList.remove('zoomed');
        document.body.style.overflow = '';
        if (modal === activeModal) {
            lastFocused?.focus();
            activeModal = null;
        }
    }

    // Cards: click and keyboard (Enter/Space) open the matching modal
    document.querySelectorAll('.item-card[data-modal-target]').forEach(card => {
        const modal = document.getElementById(card.dataset.modalTarget);

        card.addEventListener('click', () => openModal(modal, card));
        card.addEventListener('keydown', (e) => {
            if (e.target !== card) return;
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openModal(modal, card);
            }
        });
    });

    // Close button and backdrop click
    document.querySelectorAll('.item-modal').forEach(modal => {
        modal.querySelector('[data-modal-close]')?.addEventListener('click', () => closeModal(modal));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });
    });

    // ESC closes the open modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && activeModal) closeModal(activeModal);
    });

    // Click/tap the main image to zoom in and out
    document.querySelectorAll('.item-modal-mainimg-wrap[data-zoom-toggle]').forEach(wrap => {
        wrap.addEventListener('click', () => wrap.classList.toggle('zoomed'));
    });

    // Thumbnails swap the main image
    document.querySelectorAll('.item-modal-thumb').forEach(thumb => {
        thumb.addEventListener('click', (e) => {
            e.stopPropagation();

            const target = document.getElementById(thumb.dataset.target);
            if (target) target.src = thumb.dataset.full;

            thumb.parentElement.querySelectorAll('.item-modal-thumb').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');

            thumb.closest('.item-modal-gallery')?.querySelector('.item-modal-mainimg-wrap')?.classList.remove('zoomed');
        });
    });
});
</script>

@endsection
