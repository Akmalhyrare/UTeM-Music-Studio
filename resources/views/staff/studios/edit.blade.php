@extends('layouts.app')

@section('page-title', 'Manage Studio')

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('staff.studios.index') }}" class="btn btn-sm btn-outline-secondary">← Back</a>
    <h5 class="mb-0 fw-bold">Manage Studio — {{ $studio->studio_name }}</h5>
</div>

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

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- STUDIO DETAILS --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom" style="font-weight:600;">📝 Studio Details</div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('staff.studios.update', $studio->studio_id) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Studio Name <span class="text-danger">*</span></label>
                <input type="text" name="studio_name" class="form-control" style="font-size:13px;"
                       value="{{ old('studio_name', $studio->studio_name) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Studio Type <span class="text-danger">*</span></label>
                <input type="text" name="studio_type" class="form-control" style="font-size:13px;"
                       value="{{ old('studio_type', $studio->studio_type) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Description</label>
                <textarea name="description" class="form-control" style="font-size:13px;" rows="3">{{ old('description', $studio->description) }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Capacity</label>
                <input type="number" name="capacity" class="form-control" style="font-size:13px; max-width:200px;"
                       min="1" value="{{ old('capacity', $studio->capacity) }}">
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size:13px;">Equipment Available</label>
                <textarea name="equipment" class="form-control" style="font-size:13px;" rows="3"
                          placeholder="One item per line">{{ old('equipment', $studio->equipment) }}</textarea>
                <small class="text-muted">Enter one item per line.</small>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="font-size:13px;">Location</label>
                    <input type="text" name="location" class="form-control" style="font-size:13px;"
                           value="{{ old('location', $studio->location) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="font-size:13px;">Studio Size</label>
                    <input type="text" name="size" class="form-control" style="font-size:13px;"
                           value="{{ old('size', $studio->size) }}">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="font-size:13px;">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-control" style="font-size:13px;" required>
                    <option value="available" {{ old('status', $studio->status) == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="maintenance" {{ old('status', $studio->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="blocked" {{ old('status', $studio->status) == 'blocked' ? 'selected' : '' }}>Blocked</option>
                    <option value="inactive" {{ old('status', $studio->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <small class="text-muted">
                    Available = bookable. Maintenance/Blocked = visible but not bookable. Inactive = hidden from public.
                </small>
            </div>

            <button type="submit" class="btn" style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">
                ✅ Save Changes
            </button>
        </form>
    </div>
</div>

{{-- IMAGE GALLERY --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom" style="font-weight:600;">🖼️ Image Gallery</div>
    <div class="card-body p-4">

        <form method="POST" action="{{ route('staff.studios.images.store', $studio->studio_id) }}"
              enctype="multipart/form-data" class="mb-3">
            @csrf
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <input type="file" name="images[]" class="form-control" style="font-size:13px; max-width:400px;"
                       accept="image/jpeg,image/jpg,image/png,image/webp" multiple>
                <button type="submit" class="btn btn-sm" style="background:#7C3AED;color:#fff;border-radius:8px;">
                    ⬆️ Upload
                </button>
            </div>
            <small class="text-muted">JPG, JPEG, PNG or WEBP. Max 5MB each. Up to 10 images total ({{ $studio->images->count() }}/10 used).</small>
        </form>

        @if($studio->images->isEmpty())
            <p class="text-muted mb-0">No images uploaded yet.</p>
        @else
        <div id="image-gallery" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:14px;">
            @foreach($studio->images as $image)
            <div class="card border-0 shadow-sm" data-image-id="{{ $image->image_id }}" style="overflow:hidden;">
                <div style="position:relative;">
                    <img src="{{ asset('storage/' . $image->image_path) }}"
                         style="width:100%; height:110px; object-fit:cover; display:block;">
                    @if($image->is_primary)
                        <span class="badge" style="position:absolute; top:6px; left:6px; background:#7C3AED; color:#fff;">Primary</span>
                    @endif
                </div>
                <div class="card-body p-2 d-flex flex-wrap gap-1 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary move-up" title="Move up" style="font-size:11px; padding:2px 6px;">↑</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary move-down" title="Move down" style="font-size:11px; padding:2px 6px;">↓</button>
                    @if(!$image->is_primary)
                    <form method="POST" action="{{ route('staff.studios.images.primary', [$studio->studio_id, $image->image_id]) }}" style="display:inline;">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-sm btn-outline-success" style="font-size:11px; padding:2px 6px;">Set Primary</button>
                    </form>
                    @endif
                    @if(session('is_admin'))
                    <form method="POST" action="{{ route('staff.studios.images.destroy', [$studio->studio_id, $image->image_id]) }}"
                          style="display:inline;" onsubmit="return confirm('Delete this image?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:11px; padding:2px 6px;">🗑️</button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- MAINTENANCE / BLOCKED PERIODS --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span style="font-weight:600;">🛠️ Maintenance &amp; Blocked Periods</span>
        <a href="{{ route('staff.studios.calendar', $studio->studio_id) }}"
           class="btn btn-sm btn-outline-secondary" style="font-size:13px;">📅 Open Calendar</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="font-size:12px;">Type</th>
                    <th style="font-size:12px;">From</th>
                    <th style="font-size:12px;">To</th>
                    <th style="font-size:12px;">Reason</th>
                    <th style="font-size:12px;">Set By</th>
                    <th style="font-size:12px;">Actions</th>
                </tr>
            </thead>
            <tbody style="font-size:13px;">
                @forelse($studio->unavailabilities as $period)
                <tr>
                    <td>
                        @if($period->type == 'maintenance')
                            <span class="badge" style="background:#FFEDD5;color:#9A3412;">Maintenance</span>
                        @else
                            <span class="badge" style="background:#F3F4F6;color:#4B5563;">Blocked</span>
                        @endif
                    </td>
                    <td>{{ $period->start_at->format('d M Y, H:i') }}</td>
                    <td>{{ $period->end_at->format('d M Y, H:i') }}</td>
                    <td>{{ $period->reason ?? '-' }}</td>
                    <td>{{ $period->staff->staff_name ?? '-' }}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-period" data-period-id="{{ $period->unavailability_id }}">🗑️ Remove</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No maintenance or blocked periods scheduled.
                        <a href="{{ route('staff.studios.calendar', $studio->studio_id) }}">Schedule one via the calendar</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
const studioId = {{ $studio->studio_id }};

// Image reordering
const gallery = document.getElementById('image-gallery');
if (gallery) {
    function persistOrder() {
        const ids = Array.from(gallery.children).map(el => el.dataset.imageId);
        fetch(`/staff/studios/${studioId}/images/reorder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ image_ids: ids })
        });
    }

    gallery.addEventListener('click', function (e) {
        const card = e.target.closest('[data-image-id]');
        if (!card) return;

        if (e.target.classList.contains('move-up')) {
            const prev = card.previousElementSibling;
            if (prev) {
                gallery.insertBefore(card, prev);
                persistOrder();
            }
        } else if (e.target.classList.contains('move-down')) {
            const next = card.nextElementSibling;
            if (next) {
                gallery.insertBefore(next, card);
                persistOrder();
            }
        }
    });
}

// Delete unavailability period
document.querySelectorAll('.delete-period').forEach(btn => {
    btn.addEventListener('click', function () {
        if (!confirm('Remove this period?')) return;

        fetch(`/staff/studios/${studioId}/unavailability/${this.dataset.periodId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        }).then(res => res.json()).then(data => {
            if (data.success) {
                this.closest('tr').remove();
            }
        });
    });
});
</script>

@endsection
