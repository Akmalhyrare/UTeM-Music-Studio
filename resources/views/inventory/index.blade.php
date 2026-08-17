@extends('layouts.app')

@section('page-title', 'Inventory Management')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    ✅ {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    ❌ {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- STAT CARDS --}}
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#F3E8FF;">
                <span style="font-size:20px;">📦</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $totalItems }}</h4>
                <small class="text-muted">Total items</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#E6F1FB;">
                <span style="font-size:20px;">🎸</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $totalEquipment }}</h4>
                <small class="text-muted">Equipment</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FAEEDA;">
                <span style="font-size:20px;">👔</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $totalAttire }}</h4>
                <small class="text-muted">Attire</small>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="p-2 rounded" style="background:#FAECE7;">
                <span style="font-size:20px;">⚠️</span>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">{{ $lowStock }}</h4>
                <small class="text-muted">Low stock</small>
            </div>
        </div>
    </div>
</div>

{{-- FILTER & SEARCH --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('inventory.index') }}" data-instant-search>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <input type="text"
                       name="search"
                       class="form-control"
                       style="font-size:13px; max-width:250px;"
                       placeholder="Search name, brand, model, tag..."
                       value="{{ request('search') }}">
                <select name="type" class="form-control" style="font-size:13px; max-width:160px;">
                    <option value="">All types</option>
                    <option value="equipment" {{ request('type') == 'equipment' ? 'selected' : '' }}>Equipment</option>
                    <option value="attire" {{ request('type') == 'attire' ? 'selected' : '' }}>Attire</option>
                </select>
                <select name="status" class="form-control" style="font-size:13px; max-width:160px;">
                    <option value="">All status</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="unavailable" {{ request('status') == 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                </select>
                <button type="submit" class="btn btn-sm" style="background:#7C3AED;color:#fff;border-radius:8px;">
                    🔍 Search
                </button>
                <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

{{-- ITEMS TABLE --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">📦 Inventory Items</span>
        <span class="text-muted" style="font-size:12px;">{{ $items->total() }} item(s)</span>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('inventory.categories') }}"
               class="btn btn-sm btn-outline-secondary"
               style="border-radius:8px; font-size:13px;">
                🗂️ Categories
            </a>
            <a href="{{ route('inventory.create') }}"
               class="btn btn-sm"
               style="background:#7C3AED;color:#fff;border-radius:8px; font-size:13px;">
                + Add Item
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">#</th>
                    <th style="font-size:12px;">Image</th>
                    <th style="font-size:12px;">Item Name</th>
                    <th style="font-size:12px;">Category</th>
                    <th style="font-size:12px;">Type</th>
                    <th style="font-size:12px;">Quantity</th>
                    <th style="font-size:12px;">Available</th>
                    <th style="font-size:12px;">Condition</th>
                    <th style="font-size:12px;">Status</th>
                    <th style="font-size:12px;">Actions</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse ($items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                    @if($item->image)
                    <img src="{{ asset('storage/' . $item->image) }}"
                        alt="{{ $item->item_name }}"
                        data-src="{{ asset('storage/' . $item->image) }}"
                        data-name="{{ $item->item_name }}"
                        onclick="openFullImg(this.dataset.src, this.dataset.name)"
                        style="width:55px; height:55px; object-fit:contain; border-radius:8px; border:1px solid #eee; background:#f8f8f8; padding:4px; cursor:zoom-in;">
                    @else
                        <div style="width:55px; height:55px; background:#f0f0f0; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:22px;">
                            📦
                        </div>
                    @endif
                    </td>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->item_description)
                            <br><small class="text-muted">{{ Str::limit($item->item_description, 40) }}</small>
                        @endif
                    </td>
                    <td>{{ $item->category->category_name ?? '-' }}</td>
                    <td>
                        @if($item->category->type == 'equipment')
                            <span class="badge" style="background:#E6F1FB;color:#0C447C;">Equipment</span>
                        @else
                            <span class="badge" style="background:#FAEEDA;color:#633806;">Attire</span>
                        @endif
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>
                        @if($item->available_quantity <= $item->quantity * 0.3)
                            <span style="color:#A32D2D;font-weight:500;">{{ $item->available_quantity }}</span>
                        @else
                            {{ $item->available_quantity }}
                        @endif
                    </td>
                    <td>
                        @if($item->condition_status == 'good')
                            <span class="badge" style="background:#D1FAE5;color:#065F46;">Good</span>
                        @elseif($item->condition_status == 'fair')
                            <span class="badge" style="background:#FAEEDA;color:#633806;">Fair</span>
                        @else
                            <span class="badge" style="background:#FEE2E2;color:#991B1B;">Poor</span>
                        @endif
                    </td>
                    <td>
                        @if($item->item_status == 'available')
                            <span class="badge" style="background:#D1FAE5;color:#065F46;">Available</span>
                        @else
                            <span class="badge" style="background:#FEE2E2;color:#991B1B;">Unavailable</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('inventory.edit', $item->item_id) }}"
                           class="btn btn-sm btn-outline-secondary">✏️ Edit</a>
                        <form method="POST"
                              action="{{ route('inventory.destroy', $item->item_id) }}"
                              style="display:inline;"
                              onsubmit="return confirm('Delete this item?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No items found. <a href="{{ route('inventory.create') }}">Add your first item</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($items->hasPages())
        <div class="card-footer bg-white">
            {{ $items->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<style>
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
@keyframes zoomIn { from { transform:scale(0.85); opacity:0; } to { transform:scale(1); opacity:1; } }
@keyframes fadeOut { from { opacity:1; } to { opacity:0; } }
</style>

<script>
function openFullImg(src, name) {
    const overlay = document.createElement('div');
    overlay.id = 'fs-overlay';
    overlay.style.cssText = `
        position:fixed; top:0; left:0;
        width:100%; height:100%;
        background:rgba(0,0,0,0.95);
        z-index:99999;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        cursor:zoom-out;
        animation:fadeIn 0.2s ease;
    `;

    const img = document.createElement('img');
    img.src = src;
    img.alt = name;
    img.style.cssText = `
        max-width:90%;
        max-height:82vh;
        object-fit:contain;
        border-radius:12px;
        box-shadow:0 30px 80px rgba(0,0,0,0.8);
        animation:zoomIn 0.25s ease;
    `;

    const caption = document.createElement('div');
    caption.textContent = name;
    caption.style.cssText = `
        color:rgba(255,255,255,0.7);
        font-size:14px;
        font-weight:600;
        margin-top:16px;
        font-family:sans-serif;
    `;

    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '✕';
    closeBtn.style.cssText = `
        position:fixed;
        top:20px; right:24px;
        background:rgba(255,255,255,0.15);
        border:1px solid rgba(255,255,255,0.3);
        color:#fff;
        width:40px; height:40px;
        border-radius:50%;
        font-size:18px;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:center;
        z-index:100000;
        transition:all 0.2s;
    `;

    const hint = document.createElement('div');
    hint.textContent = 'Click anywhere or press ESC to close';
    hint.style.cssText = `
        position:fixed;
        bottom:24px;
        left:50%;
        transform:translateX(-50%);
        color:rgba(255,255,255,0.35);
        font-size:12px;
        font-family:sans-serif;
    `;

    overlay.appendChild(img);
    overlay.appendChild(caption);
    overlay.appendChild(closeBtn);
    overlay.appendChild(hint);
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    const closeFS = () => {
        overlay.style.animation = 'fadeOut 0.2s ease';
        setTimeout(() => {
            if (document.getElementById('fs-overlay')) {
                document.body.removeChild(overlay);
                document.body.style.overflow = 'auto';
            }
        }, 200);
    };

    overlay.addEventListener('click', e => {
        if (e.target === overlay || e.target === img) closeFS();
    });
    closeBtn.addEventListener('click', closeFS);
    closeBtn.addEventListener('mouseover', () => closeBtn.style.background = 'rgba(255,255,255,0.25)');
    closeBtn.addEventListener('mouseout',  () => closeBtn.style.background = 'rgba(255,255,255,0.15)');

    document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') {
            closeFS();
            document.removeEventListener('keydown', escHandler);
        }
    });
}
</script>

@endsection