@extends('layouts.student')

@section('content')

<style>
    .page-header {
        background-image: linear-gradient(rgba(33,28,54,0.75), rgba(33,28,54,0.75)), url("{{ asset('images/studio-control-room.jpg') }}");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: #fff; padding: 36px 40px;
    }
    .page-header h2 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
    .page-header p { color: rgba(255,255,255,0.6); font-size: 14px; }

    .page-body { max-width: 900px; margin: 0 auto; padding: 32px 40px; }

    /* Gallery */
    .gallery { position: relative; border-radius: 14px; overflow: hidden; background: #f0f0f0; margin-bottom: 24px; }
    .gallery-track { display: flex; transition: transform 0.3s ease; }
    .gallery-slide { flex: 0 0 100%; height: 320px; }
    .gallery-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .gallery-placeholder { height: 320px; display: flex; align-items: center; justify-content: center; font-size: 64px; }
    .gallery-nav {
        position: absolute; top: 50%; transform: translateY(-50%);
        background: rgba(0,0,0,0.4); color: #fff; border: none;
        width: 36px; height: 36px; border-radius: 50%; font-size: 16px; cursor: pointer;
    }
    .gallery-prev { left: 12px; }
    .gallery-next { right: 12px; }
    .gallery-dots { position: absolute; bottom: 12px; left: 0; right: 0; text-align: center; }
    .gallery-dot {
        display: inline-block; width: 8px; height: 8px; border-radius: 50%;
        background: rgba(255,255,255,0.5); margin: 0 3px; cursor: pointer;
    }
    .gallery-dot.active { background: #fff; }

    /* Info panel */
    .badge-status { padding: 5px 14px; border-radius: 99px; font-size: 12px; font-weight: 600; }
    .badge-available { background: #D1FAE5; color: #065F46; }
    .badge-maintenance { background: #FFEDD5; color: #9A3412; }
    .badge-blocked { background: #F3F4F6; color: #4B5563; }

    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin: 20px 0; }
    .info-item { background: #f8f9fa; border-radius: 10px; padding: 14px 16px; }
    .info-item .label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
    .info-item .value { font-size: 15px; font-weight: 700; color: #211C36; }

    .equipment-list { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; }
    .equipment-list li { font-size: 13px; color: #444; padding: 6px 10px; background: #f8f9fa; border-radius: 8px; }
    .equipment-list li:before { content: "🎛️ "; }
</style>

<div class="page-header">
    <h2>{{ $studio->studio_name }}</h2>
    <p>{{ ucfirst($studio->studio_type) }} Studio</p>
</div>

<div class="page-body">

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('studios.browse') }}" class="btn btn-sm btn-outline-secondary">← Back to Studios</a>
    </div>

    {{-- IMAGE GALLERY --}}
    <div class="gallery">
        @if($studio->images->isEmpty())
            <div class="gallery-placeholder">🎚️</div>
        @else
            <div class="gallery-track" id="gallery-track">
                @foreach($studio->images as $image)
                <div class="gallery-slide">
                    <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $studio->studio_name }}">
                </div>
                @endforeach
            </div>
            @if($studio->images->count() > 1)
            <button type="button" class="gallery-nav gallery-prev" id="gallery-prev">‹</button>
            <button type="button" class="gallery-nav gallery-next" id="gallery-next">›</button>
            <div class="gallery-dots" id="gallery-dots">
                @foreach($studio->images as $index => $image)
                    <span class="gallery-dot {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}"></span>
                @endforeach
            </div>
            @endif
        @endif
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <h5 class="fw-bold mb-0">About this Studio</h5>
                @if($studio->status == 'available')
                    <span class="badge-status badge-available">Available</span>
                @elseif($studio->status == 'maintenance')
                    <span class="badge-status badge-maintenance">Maintenance</span>
                @else
                    <span class="badge-status badge-blocked">Blocked</span>
                @endif
            </div>
            <p class="text-muted mb-0" style="font-size:13px;">{{ $studio->description ?? 'No description provided.' }}</p>

            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Capacity</div>
                    <div class="value">{{ $studio->capacity ? $studio->capacity . ' people' : '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Location</div>
                    <div class="value">{{ $studio->location ?? '-' }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Studio Size</div>
                    <div class="value">{{ $studio->size ?? '-' }}</div>
                </div>
            </div>

            @if(!empty($studio->equipmentList()))
            <h6 class="fw-bold mt-3 mb-2" style="font-size:13px;">Equipment &amp; Facilities</h6>
            <ul class="equipment-list">
                @foreach($studio->equipmentList() as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    {{-- AVAILABILITY PANEL --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Availability</h5>

            @if($studio->status == 'maintenance')
                <div class="alert alert-warning" style="font-size:13px;">
                    🔧 Studio is under maintenance.
                </div>
            @elseif($studio->status == 'blocked')
                <div class="alert alert-secondary" style="font-size:13px;">
                    🚫 Studio is temporarily blocked.
                </div>
            @else
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Available Today</div>
                        <div class="value">{{ $availableToday ? 'Yes' : 'No' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Next Available Slot</div>
                        <div class="value" style="font-size:13px;">
                            @if($nextAvailable)
                                {{ \Illuminate\Support\Carbon::parse($nextAvailable['date'])->format('d M Y') }},
                                {{ $nextAvailable['slot']['label'] }}
                            @else
                                Not available in the next 14 days
                            @endif
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="label">Current Status</div>
                        <div class="value">Available</div>
                    </div>
                </div>
            @endif

            <div class="mt-3">
                @if($studio->status === 'available' && session('user_type') === 'student')
                    <a href="{{ route('bookings.create', ['studio_id' => $studio->studio_id]) }}"
                       class="btn" style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">
                        Book Now
                    </a>
                @elseif($studio->status === 'available' && !session('user_type'))
                    <a href="{{ route('login') }}" class="btn" style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">
                        🔒 Login to Book
                    </a>
                @endif
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    const track = document.getElementById('gallery-track');
    if (!track) return;

    const slides = track.children.length;
    const dots = document.querySelectorAll('.gallery-dot');
    let current = 0;

    function show(index) {
        current = (index + slides) % slides;
        track.style.transform = `translateX(-${current * 100}%)`;
        dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
    }

    document.getElementById('gallery-prev')?.addEventListener('click', () => show(current - 1));
    document.getElementById('gallery-next')?.addEventListener('click', () => show(current + 1));
    dots.forEach(dot => dot.addEventListener('click', () => show(parseInt(dot.dataset.index, 10))));
})();
</script>

@endsection
