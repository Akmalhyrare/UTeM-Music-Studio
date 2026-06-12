<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTeM Music Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background-color: #f5f5f5;
        }
        .sidebar {
            width: 220px;
            min-height: 100vh;
            background-color: #1a1a2e;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            padding-top: 20px;
        }
        .sidebar-brand {
            padding: 10px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 10px;
        }
        .sidebar-brand h5 {
            color: #ffffff;
            font-size: 14px;
            margin: 0;
            font-weight: 600;
        }
        .sidebar-brand p {
            color: rgba(255,255,255,0.5);
            font-size: 11px;
            margin: 0;
        }
        .sidebar a.nav-link,
        .sidebar button.nav-link {
            color: rgba(255,255,255,0.7) !important;
            padding: 10px 20px !important;
            font-size: 13px;
            display: flex !important;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.2s;
            width: 100%;
            background: transparent;
            border: none;
            text-align: left;
            cursor: pointer;
        }
        .sidebar a.nav-link:hover,
        .sidebar button.nav-link:hover {
            background-color: rgba(255,255,255,0.1) !important;
            color: #ffffff !important;
        }
        .sidebar a.nav-link.active {
            background-color: #1D9E75 !important;
            color: #ffffff !important;
        }
        .sidebar .nav-section {
            font-size: 10px;
            color: rgba(255,255,255,0.3);
            padding: 15px 20px 5px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .topbar {
            position: fixed;
            top: 0;
            left: 220px;
            right: 0;
            z-index: 999;
            background: #ffffff;
            border-bottom: 1px solid #e5e5e5;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
        }
        .topbar-title {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0;
        }
        .user-badge {
            background: #E1F5EE;
            color: #085041;
            padding: 5px 12px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 500;
        }
        .main-content {
            margin-left: 220px;
            margin-top: 80px;
            padding: 25px;
            min-height: calc(100vh - 80px);
        }
    </style>
</head>
<body>

    {{-- SIDEBAR --}}
    <div class="sidebar">
        <div class="sidebar-brand">
            <h5>🎵 UTeM Music Studio</h5>
            <p>Management System</p>
        </div>

        <div class="nav-section">Main</div>

        <a href="{{ session('user_type') === 'student' 
            ? route('student.dashboard') 
            : route('staff.dashboard') }}"
        class="nav-link {{ request()->routeIs('staff.dashboard') || request()->routeIs('student.dashboard') ? 'active' : '' }}">
            📊 Dashboard
        </a>

        @if(session('user_type') === 'staff')
        <a href="{{ route('users.index') }}"
        class="nav-link {{ request()->routeIs('users*') ? 'active' : '' }}">
            👥 Users
        </a>
        @endif

        <a href="{{ route('inventory.index') }}"
        class="nav-link {{ request()->routeIs('inventory*') ? 'active' : '' }}">
            📦 Inventory
        </a>

        <a href="#"
           class="nav-link {{ request()->routeIs('borrowing*') ? 'active' : '' }}">
            📋 Borrowing
        </a>

        <a href="#"
           class="nav-link {{ request()->routeIs('booking*') ? 'active' : '' }}">
            📅 Bookings
        </a>

        <a href="#"
           class="nav-link {{ request()->routeIs('reports*') ? 'active' : '' }}">
            📈 Reports
        </a>

        <div class="nav-section">Account</div>

        <a href="#" class="nav-link">
            ⚙️ Settings
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link">
                🚪 Logout
            </button>
        </form>
    </div>

    {{-- TOPBAR --}}
    <div class="topbar">
        <h6 class="topbar-title">@yield('page-title', 'Dashboard')</h6>
        <div class="d-flex align-items-center gap-3">
            <span class="user-badge">{{ session('user_name') ?? 'User' }}</span>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="main-content">
        @yield('content')
    </div>

</body>
</html>