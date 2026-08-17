@extends('layouts.student')

@section('content')

<style>
    .page-header {
        background-image: linear-gradient(rgba(33,28,54,0.75), rgba(33,28,54,0.75)), url("{{ asset('images/studio-control-room.jpg') }}");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: #fff;
        padding: 40px; text-align: center;
    }
    .page-header h2 { font-size: 26px; font-weight: 700; margin-bottom: 6px; }
    .page-header p { color: rgba(255,255,255,0.6); font-size: 14px; }

    .studios-container { max-width: 1000px; margin: 40px auto; padding: 0 40px; }
    .studio-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
    .studio-card {
        background: #fff; border-radius: 16px;
        overflow: hidden; text-align: center;
        border: 1px solid #eee;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex; flex-direction: column;
    }
    .studio-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
    .studio-image { width: 100%; height: 140px; object-fit: cover; }
    .studio-emoji { font-size: 48px; padding-top: 24px; }
    .studio-body { padding: 20px; }
    .studio-name { font-size: 15px; font-weight: 700; color: #211C36; margin-bottom: 6px; }
    .studio-desc { font-size: 12px; color: #888; margin-bottom: 10px; min-height: 32px; }
    .studio-meta { font-size: 12px; color: #555; margin-bottom: 12px; display: flex; justify-content: space-between; }
    .studio-status { margin-bottom: 16px; }
    .badge-status { padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 500; }
    .badge-available { background: #D1FAE5; color: #065F46; }
    .badge-maintenance { background: #FFEDD5; color: #9A3412; }
    .badge-blocked { background: #F3F4F6; color: #4B5563; }
    .btn-book, .btn-view {
        border: none; padding: 9px 16px;
        border-radius: 8px; font-size: 13px;
        font-weight: 500; cursor: pointer;
        text-decoration: none; display: inline-block;
        transition: all 0.2s; flex: 1;
    }
    .btn-book { background: #7C3AED; color: #fff; }
    .btn-book:hover { background: #6D28D9; color: #fff; }
    .btn-view { background: #f0f0f0; color: #211C36; }
    .btn-view:hover { background: #e0e0e0; color: #211C36; }
    .studio-actions { display: flex; gap: 8px; }
    .login-note {
        text-align: center; margin-top: 30px;
        font-size: 13px; color: #888;
    }
    .login-note a { color: #7C3AED; font-weight: 500; text-decoration: none; }
</style>

<div class="page-header">
    <h2>📅 Studio Booking</h2>
    <p>Reserve a studio session at UTeM Music Studio</p>
</div>

<div class="studios-container">

    @if(!session('user_type'))
    <div style="background:#E6F1FB; border-radius:10px; padding:14px 20px; margin-bottom:24px; font-size:13px; color:#0C447C; text-align:center;">
        ℹ️ Please <a href="{{ route('login') }}" style="color:#7C3AED;font-weight:600;">login</a> or
        <a href="{{ route('register') }}" style="color:#7C3AED;font-weight:600;">register</a>
        to book a studio session.
    </div>
    @endif

    <div class="studio-grid">
        @forelse($studios as $studio)
        <div class="studio-card">
            @if($studio->primaryImage)
                <img src="{{ asset('storage/' . $studio->primaryImage->image_path) }}"
                     alt="{{ $studio->studio_name }}" class="studio-image">
            @else
                <div class="studio-emoji">
                    @if(str_contains(strtolower($studio->studio_name), 'music')) 🎸
                    @elseif(str_contains(strtolower($studio->studio_name), 'dance')) 💃
                    @elseif(str_contains(strtolower($studio->studio_name), 'gamelan')) 🥁
                    @else 🎶
                    @endif
                </div>
            @endif
            <div class="studio-body">
                <div class="studio-name">{{ $studio->studio_name }}</div>
                <div class="studio-desc">{{ Str::limit($studio->description ?? 'Available for booking', 60) }}</div>
                <div class="studio-meta">
                    <span>👥 Capacity: {{ $studio->capacity ?? '-' }}</span>
                </div>
                <div class="studio-status">
                    @if($studio->status == 'available')
                        <span class="badge-status badge-available">Available</span>
                    @elseif($studio->status == 'maintenance')
                        <span class="badge-status badge-maintenance">Maintenance</span>
                    @else
                        <span class="badge-status badge-blocked">Blocked</span>
                    @endif
                </div>
                <div class="studio-actions">
                    <a href="{{ route('studios.show', $studio->studio_id) }}" class="btn-view">View Details</a>
                    @if(session('user_type') === 'student' && $studio->status === 'available')
                        <a href="{{ route('bookings.create', ['studio_id' => $studio->studio_id]) }}" class="btn-book">Book Now</a>
                    @elseif(!session('user_type'))
                        <a href="{{ route('login') }}" class="btn-book">🔒 Login</a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <p class="text-center text-muted">No studios available at the moment.</p>
        @endforelse
    </div>

    @if(!session('user_type'))
    <div class="login-note">
        Don't have an account? <a href="{{ route('register') }}">Register here</a>
    </div>
    @endif

</div>

@endsection
