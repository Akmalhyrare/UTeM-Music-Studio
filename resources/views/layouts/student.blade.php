<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTeM Music Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }

        /* NAVBAR */
        .navbar {
            background: #1a1a2e;
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .navbar-brand {
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-menu {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .nav-item {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: #ffffff;
        }
        .nav-item.active {
            background: #1D9E75;
            color: #ffffff;
        }
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-nav-login {
            color: rgba(255,255,255,0.8);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-nav-login:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-nav-register {
            background: #1D9E75;
            color: #ffffff;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-nav-register:hover { background: #0F6E56; color: #fff; }
        .user-pill {
            background: rgba(29,158,117,0.2);
            color: #5DCAA5;
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-logout {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-logout:hover { background: rgba(255,255,255,0.2); color: #fff; }
    </style>
</head>
<body>

{{-- NAVBAR --}}
<nav class="navbar">
    <a href="{{ route('landing') }}" class="navbar-brand">
        🎵 UTeM Music Studio
    </a>

    <div class="navbar-menu">
        <a href="{{ route('landing') }}"
           class="nav-item {{ request()->routeIs('landing') ? 'active' : '' }}">
            🏠 Home
        </a>
        <a href="{{ route('items.browse') }}"
           class="nav-item {{ request()->routeIs('items*') ? 'active' : '' }}">
            📦 Browse Items
        </a>
        <a href="{{ route('studios.browse') }}"
           class="nav-item {{ request()->routeIs('studios*') ? 'active' : '' }}">
            📅 Studios
        </a>
        @if(session('user_type') === 'student')
        <a href="{{ route('student.dashboard') }}"
           class="nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
            👤 My Dashboard
        </a>
        @endif
    </div>

    <div class="navbar-right">
        @if(session('user_type') === 'student')
            <div class="user-pill">
                👤 {{ session('user_name') }}
            </div>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="btn-nav-login">Login</a>
            <a href="{{ route('register') }}" class="btn-nav-register">Register</a>
        @endif
    </div>
</nav>

{{-- PAGE CONTENT --}}
@yield('content')

{{-- FOOTER --}}
<div style="background:#1a1a2e; color:rgba(255,255,255,0.5); text-align:center; padding:20px; font-size:13px; margin-top:60px;">
    © {{ date('Y') }} UTeM Music Studio Management System
</div>

</body>
</html>