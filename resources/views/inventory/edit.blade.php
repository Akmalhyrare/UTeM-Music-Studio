@extends('layouts.app')

@section('page-title', 'Edit Inventory Item')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary">← Back</a>
    <h5 class="mb-0 fw-bold">Edit Item</h5>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card border-0 shadow-sm" style="max-width:600px;">
    <div class="card-body p-4">
        <form method="POST"
              action="{{ route('inventory.update', $item->item_id) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Item Name <span class="text-danger">*</span></label>
                <input type="text"
                       name="item_name"
                       class="form-control"
                       style="font-size:13px;"
                       value="{{ old('item_name', $item->item_name) }}"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Category <span class="text-danger">*</span></label>
                <select name="category_id" class="form-control" style="font-size:13px;" required>
                    <option value="">-- Select category --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->category_id }}"
                            {{ old('category_id', $item->category_id) == $category->category_id ? 'selected' : '' }}>
                            {{ $category->category_name }} ({{ ucfirst($category->type) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Description</label>
                <textarea name="item_description"
                          class="form-control"
                          style="font-size:13px;"
                          rows="3">{{ old('item_description', $item->item_description) }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Item Image</label>
                @if($item->image)
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $item->image) }}"
                             alt="{{ $item->item_name }}"
                             style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:1px solid #ddd;">
                        <small class="text-muted d-block mt-1">Current image</small>
                    </div>
                @endif
                <input type="file"
                       name="image"
                       class="form-control"
                       style="font-size:13px;"
                       accept="image/jpg,image/jpeg,image/png">
                <small class="text-muted">Leave empty to keep current image. JPG or PNG, max 2MB</small>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Total Quantity <span class="text-danger">*</span></label>
                <input type="number"
                       name="quantity"
                       class="form-control"
                       style="font-size:13px;"
                       value="{{ old('quantity', $item->quantity) }}"
                       min="1"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Available Quantity</label>
                <input type="number"
                       name="available_quantity"
                       class="form-control"
                       style="font-size:13px;"
                       value="{{ old('available_quantity', $item->available_quantity) }}"
                       min="0">
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Condition <span class="text-danger">*</span></label>
                <select name="condition_status" class="form-control" style="font-size:13px;" required>
                    <option value="good" {{ old('condition_status', $item->condition_status) == 'good' ? 'selected' : '' }}>Good</option>
                    <option value="fair" {{ old('condition_status', $item->condition_status) == 'fair' ? 'selected' : '' }}>Fair</option>
                    <option value="poor" {{ old('condition_status', $item->condition_status) == 'poor' ? 'selected' : '' }}>Poor</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label" style="font-size:13px;">Status <span class="text-danger">*</span></label>
                <select name="item_status" class="form-control" style="font-size:13px;" required>
                    <option value="available" {{ old('item_status', $item->item_status) == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="unavailable" {{ old('item_status', $item->item_status) == 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit"
                        class="btn"
                        style="background:#1D9E75;color:#fff;font-size:13px;border-radius:8px;">
                    ✅ Update Item
                </button>
                <a href="{{ route('inventory.index') }}"
                   class="btn btn-outline-secondary"
                   style="font-size:13px;border-radius:8px;">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

@endsection