<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#090735">
    <title>@yield('title', 'Dashboard') · Transolutions</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>

<body><a href="#main" class="skip-link">Skip to content</a>
    @php
        $role = auth()->user()->role;
        $menus = [
            'admin' => [
                ['dashboard', 'Dashboard', 'grid'],
                ['bookings.index', 'Bookings', 'calendar'],
                ['customers.index', 'Customers & Vehicles', 'people'],
                ['services.index', 'Services & Prices', 'wrench'],
                ['inventory.index', 'Inventory', 'box'],
                ['invoices.index', 'Sales & Invoices', 'receipt'],
                ['reports.index', 'Reports', 'chart'],
                ['users.index', 'Users', 'people'],
            ],
            'secretary' => [
                ['dashboard', 'Front Desk', 'grid'],
                ['bookings.index', 'Bookings', 'calendar'],
                ['customers.index', 'Customers & Vehicles', 'people'],
                ['jobs.index', 'Repair Progress', 'wrench'],
                ['invoices.index', 'Invoices', 'receipt'],
            ],
            'mechanic' => [
                ['dashboard', 'My Workbench', 'grid'],
                ['jobs.index', 'Active Repairs', 'wrench'],
                ['bookings.index', 'My Assigned Jobs', 'car'],
            ],
            'purchaser' => [
                ['dashboard', 'Dashboard', 'grid'],
                ['orders.index', 'Parts Orders', 'box'],
                ['orders.index?receive=1', 'Receive Stock', 'plus'],
                ['inventory.index', 'Inventory', 'box'],
            ],
            'customer' => [
                ['dashboard', 'Dashboard', 'grid'],
                ['bookings.create', 'Book a Check-up', 'plus'],
                ['bookings.index', 'My Bookings', 'calendar'],
                ['vehicles.index', 'My Vehicles', 'car'],
                ['invoices.index', 'My Invoices', 'receipt'],
            ],
        ];
    @endphp
    <aside class="sidebar" id="sidebar"><a class="brand" href="{{ route('dashboard') }}"><span
                class="brand-mark"><x-icon name="wrench" /></span><span>TRANSOLUTIONS<small>AUTO CARE,
                    SIMPLIFIED.</small></span></a>
        <div class="workspace-label">{{ ucfirst($role) }} workspace</div>
        <nav aria-label="Main navigation">
            @foreach ($menus[$role] as [$route, $label, $icon])
                @php($receive = str_contains($route, '?'))
                @php($baseRoute = explode('?', $route)[0])
                @php($active = $receive ? request()->routeIs('orders.index') && request('receive') : ($baseRoute === 'bookings.index' ? request()->routeIs('bookings.index', 'bookings.show') : ($baseRoute === 'bookings.create' ? request()->routeIs('bookings.create') : request()->routeIs(str_replace('.index', '.*', $baseRoute)))) && !request('receive'))
                <a class="nav-item {{ $active ? 'active' : '' }}"
                    href="{{ route($baseRoute, $receive ? ['receive' => 1] : []) }}"
                    @if ($active) aria-current="page" @endif><x-icon
                        :name="$icon" /><span>{{ $label }}</span></a>
            @endforeach
        </nav>
        <div class="sidebar-bottom">
            <div class="shop-note"><x-icon name="car" /><strong>A little care.<br>A smoother
                    journey.</strong><small>Transolutions Automatic<br>Transmission Overhaul<br>Davao City,
                    Philippines</small></div><a class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                href="{{ route('profile.edit') }}"><x-icon name="people" />My Profile</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="nav-item" type="submit"><x-icon
                        name="logout" />Sign out</button></form>
        </div>
    </aside>
    <div class="shell">
        <header class="topbar"><button type="button" class="button compact mobile-menu" data-menu
                aria-controls="sidebar" aria-expanded="false">☰ Menu</button>
            <div class="breadcrumb">Workspace <span>/</span> <strong>@yield('title', 'Dashboard')</strong></div>
            <div class="topbar-actions">
                <form class="search-form" method="get"
                    action="{{ route($role === 'purchaser' ? 'inventory.index' : 'bookings.index') }}"><x-icon
                        name="search" /><input type="search" name="q"
                        placeholder="{{ $role === 'purchaser' ? 'Find a part…' : 'Find a booking…' }}"
                        aria-label="Search" maxlength="100"><button class="search-submit" aria-label="Search"><x-icon
                            name="arrow" /></button></form><a class="notification-link"
                    href="{{ route('dashboard') }}#notifications" aria-label="Notifications"><x-icon name="bell" />
                    @if (auth()->user()->unreadNotifications()->exists())
                        <span class="notification-dot"></span>
                    @endif
                </a><a class="profile-chip" href="{{ route('profile.edit') }}"><span
                        class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span>{{ explode(' ', auth()->user()->name)[0] }}<small>{{ ucfirst($role) }}</small></span></a>
            </div>
        </header>
        <main id="main"><x-alerts />@yield('content')</main>
        <footer>Transolutions Vehicle Maintenance Management System <span>Made for a smoother workday.</span></footer>
    </div>
</body>

</html>
