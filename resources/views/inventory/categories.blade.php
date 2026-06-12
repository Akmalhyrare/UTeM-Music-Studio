@extends('layouts.app')

@section('page-title', 'Inventory Categories')

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

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary">← Back</a>
    <h5 class="mb-0 fw-bold">Manage Categories</h5>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">

    {{-- ADD CATEGORY FORM --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <span style="font-weight:600;">➕ Add New Category</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.categories.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;">Category Name <span class="text-danger">*</span></label>
                    <input type="text"
                           name="category_name"
                           class="form-control"
                           style="font-size:13px;"
                           placeholder="e.g. String Instruments"
                           value="{{ old('category_name') }}"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-control" style="font-size:13px;" required>
                        <option value="">-- Select type --</option>
                        <option value="equipment" {{ old('type') == 'equipment' ? 'selected' : '' }}>Equipment</option>
                        <option value="attire" {{ old('type') == 'attire' ? 'selected' : '' }}>Attire</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label" style="font-size:13px;">Description</label>
                    <textarea name="description"
                              class="form-control"
                              style="font-size:13px;"
                              rows="2"
                              placeholder="Optional">{{ old('description') }}</textarea>
                </div>

                <button type="submit"
                        class="btn w-100"
                        style="background:#1D9E75;color:#fff;font-size:13px;border-radius:8px;">
                    ✅ Save Category
                </button>
            </form>
        </div>
    </div>

    {{-- CATEGORIES LIST --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <span style="font-weight:600;">🗂️ All Categories</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="font-size:12px;">Name</th>
                        <th style="font-size:12px;">Type</th>
                        <th style="font-size:12px;">Items</th>
                        <th style="font-size:12px;">Action</th>
                    </tr>
                </thead>
                <tbody style="font-size:13px;">
                    @forelse($categories as $category)
                    <tr>
                        <td>{{ $category->category_name }}</td>
                        <td>
                            @if($category->type == 'equipment')
                                <span class="badge" style="background:#E6F1FB;color:#0C447C;">Equipment</span>
                            @else
                                <span class="badge" style="background:#FAEEDA;color:#633806;">Attire</span>
                            @endif
                        </td>
                        <td>{{ $category->items_count }}</td>
                        <td>
                            <form method="POST"
                                  action="{{ route('inventory.categories.destroy', $category->category_id) }}"
                                  style="display:inline;"
                                  onsubmit="return confirm('Delete this category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        {{ $category->items_count > 0 ? 'disabled' : '' }}>
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">No categories yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection