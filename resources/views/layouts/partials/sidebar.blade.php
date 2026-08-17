@php
    $isAdmin = session('user_type') === 'staff' && session('is_admin');

    $mainDashboardRoute = session('user_type') === 'student'
        ? 'student.dashboard'
        : ($isAdmin ? 'admin.dashboard' : 'staff.dashboard');

    $navSections = [
        [
            'title' => 'Dashboard',
            'items' => [
                ['label' => 'Dashboard', 'icon' => '📊', 'route' => $mainDashboardRoute, 'pattern' => $mainDashboardRoute],
            ],
        ],
        [
            'title' => 'Studio & Bookings',
            'items' => [
                ['label' => 'Studios',  'icon' => '🎚️', 'route' => 'staff.studios.index',   'pattern' => 'staff.studios*'],
                ['label' => 'Bookings', 'icon' => '📅', 'route' => 'staff.bookings.index',   'pattern' => 'staff.bookings*'],
            ],
        ],
        [
            'title' => 'Equipment & Assets',
            'items' => [
                ['label' => 'Inventory',    'icon' => '📦', 'route' => 'inventory.index',         'pattern' => 'inventory*'],
                ['label' => 'Borrowing',    'icon' => '📋', 'route' => 'staff.borrowings.index',  'pattern' => 'staff.borrowings*'],
                ['label' => 'Maintenance',  'icon' => '🔧', 'route' => 'staff.maintenance.index', 'pattern' => 'staff.maintenance*'],
            ],
        ],
    ];

    if ($isAdmin) {
        $navSections[] = [
            'title' => 'Administration',
            'items' => [
                ['label' => 'Users',   'icon' => '👥', 'route' => 'users.index',         'pattern' => 'users*'],
                ['label' => 'Reports', 'icon' => '📈', 'route' => 'admin.reports.index', 'pattern' => 'admin.reports*'],
            ],
        ];
    }

    $navSections[] = [
        'title' => 'Account',
        'items' => [
            ['label' => 'Settings', 'icon' => '⚙️', 'route' => 'staff.settings', 'pattern' => 'staff.settings*'],
        ],
    ];
@endphp

@foreach ($navSections as $section)
    @php
        $sectionActive = collect($section['items'])->contains(fn ($item) => request()->routeIs($item['pattern']));
        $sectionId = 'nav-' . \Illuminate\Support\Str::slug($section['title']);
    @endphp

    <button type="button"
            class="nav-section nav-section-toggle {{ $sectionActive ? 'open' : '' }}"
            data-target="{{ $sectionId }}">
        <span>{{ $section['title'] }}</span>
        <span class="nav-section-caret">▾</span>
    </button>

    <div class="nav-group {{ $sectionActive ? 'open' : '' }}" id="{{ $sectionId }}">
        @foreach ($section['items'] as $item)
            <a href="{{ route($item['route']) }}"
               class="nav-link {{ request()->routeIs($item['pattern']) ? 'active' : '' }}">
                {{ $item['icon'] }} {{ $item['label'] }}
            </a>
        @endforeach
    </div>
@endforeach

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="nav-link">
        🚪 Logout
    </button>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nav-section-toggle').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            toggle.classList.toggle('open');
            document.getElementById(toggle.dataset.target).classList.toggle('open');
        });
    });
});
</script>
