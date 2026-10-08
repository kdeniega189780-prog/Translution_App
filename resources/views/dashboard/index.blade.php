@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">{{ now()->format('l, F j, Y') }}</span>
            <h1>Hello, {{ explode(' ', auth()->user()->name)[0] }} <span class="greeting-dot">✦</span></h1>
            <p>{{ $role === 'customer' ? 'Your vehicle’s care, all in one place.' : ($role === 'purchaser' ? 'Keep the workshop stocked and ready for the week.' : 'Here’s what’s happening at the workshop today.') }}
            </p>
        </div><a class="button primary"
            href="{{ route($role === 'purchaser' ? 'orders.create' : 'bookings.create') }}"><x-icon
                name="plus" />{{ $role === 'purchaser' ? 'New parts order' : ($role === 'customer' ? 'Book a check-up' : 'New booking') }}</a>
    </div>
    @if ($role === 'purchaser')
        <div class="stats">
            <div class="stat card"><span class="stat-icon blue"><x-icon name="box" /></span>
                <p>Parts in catalog</p><strong>{{ $partCount }}</strong><small>All inventory items</small>
            </div>
            <div class="stat card"><span class="stat-icon lavender"><x-icon name="receipt" /></span>
                <p>Draft orders</p><strong>{{ $draftOrders }}</strong><small>Ready to review</small>
            </div>
            <div class="stat card"><span class="stat-icon green"><x-icon name="box" /></span>
                <p>Awaiting delivery</p><strong>{{ $openOrders }}</strong><a
                    href="{{ route('orders.index', ['receive' => 1]) }}">Receive stock →</a>
            </div>
            <div class="stat card"><span class="stat-icon yellow"><x-icon name="box" /></span>
                <p>Low-stock items</p><strong>{{ $lowStock->count() }}</strong><small>Time to replenish</small>
            </div>
        </div>
        <section class="card" id="recent-orders">
            <div class="section-heading">
                <h2>Recent parts orders</h2><a href="{{ route('orders.index') }}">View all →</a>
            </div>
            {{ $orders->links('pagination.simple', ['label' => 'Recent orders']) }}
            @forelse($orders as $order)
                <div class="list-row">
                    <div><strong>Order #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</strong><small>{{ $order->supplier }}
                            · {{ $order->items->count() }} parts</small></div><x-badge :status="$order->status" />
            </div>@empty<p class="empty">No orders yet. Create your first weekly order.</p>
            @endforelse
            {{ $orders->links('pagination.simple', ['label' => 'Recent orders']) }}
        </section>
    @else
        <div class="stats">
            <div class="stat card"><span class="stat-icon lavender"><x-icon name="calendar" /></span>
                <p>Today’s bookings</p><strong>{{ $todayCount }}</strong><small>{{ $pendingCount }} waiting for
                    confirmation</small>
            </div>
            <div class="stat card"><span class="stat-icon blue"><x-icon name="wrench" /></span>
                <p>Cars in progress</p><strong>{{ $processingCount }}</strong><small>Care is underway</small>
            </div>
            <div class="stat card"><span class="stat-icon green"><x-icon name="receipt" /></span>
                <p>{{ $role === 'admin' ? 'Sales today' : 'Completed repairs' }}</p>
                <strong>{{ $role === 'admin' ? '₱' . number_format($salesToday, 2) : $completedCount }}</strong><small>{{ $role === 'admin' ? 'Recorded payments' : 'Ready or released' }}</small>
            </div>
            <div class="stat card"><span class="stat-icon yellow"><x-icon name="box" /></span>
                <p>{{ $role === 'admin' ? 'Low-stock items' : 'Total bookings' }}</p>
                <strong>{{ $role === 'admin' ? $lowStock->count() : $totalCount }}</strong><small>{{ $role === 'admin' ? 'Keep your shelves ready' : 'Your care journey' }}</small>
            </div>
        </div>
        @if ($role === 'admin')
            @include('dashboard.sales-chart')
        @endif
        <div class="dashboard-grid">
            <section class="card schedule-card">
                <div class="section-heading">
                    <div>
                        <h2>{{ $role === 'customer' ? 'Your schedule' : 'Daily workshop schedule' }}</h2>
                        <p>A clear plan for the day ahead.</p>
                    </div><span class="badge scheduled">{{ $schedule->count() }} / 4 slots</span>
                </div>
                <form class="inline-form date-filter" method="get"><label for="date">Schedule for</label><input
                        type="date" name="date" id="date" value="{{ $date }}"><button
                        class="button compact">View day</button></form>
                @forelse($schedule as $booking)
                    <a class="schedule-row"
                        href="{{ route('bookings.show', $booking) }}"><time>{{ $booking->scheduled_at->format('h:i') }}<small>{{ $booking->scheduled_at->format('A') }}</small></time><span
                            class="schedule-line {{ $booking->status->value }}"></span>
                        <div class="grow"><strong>{{ $booking->vehicle->make }} {{ $booking->vehicle->model }} <span
                                    class="plate">{{ $booking->vehicle->plate_number }}</span></strong><small>{{ $booking->service->name }}</small><small>{{ $booking->customer->name }}
                                · {{ $booking->staff?->name ?? 'Mechanic not assigned yet' }}</small></div><x-badge
                            :status="$booking->status" />
                </a>@empty<div class="empty"><x-icon name="calendar" />
                        <h3>A little breathing room.</h3>
                        <p>No confirmed bookings for this day.</p><a href="{{ route('bookings.index') }}">View booking
                            requests →</a>
                    </div>
                @endforelse
            </section>
            <div class="stack">
                <section class="card progress-card">
                    <h2>Workshop progress</h2>
                    <p>Completed and released repairs</p>@php($progress = $totalCount ? round(($completedCount / $totalCount) * 100) : 0)<div class="progress-ring"
                        style="--progress:{{ $progress }}%" role="img"
                        aria-label="{{ $progress }} percent complete">
                        <div><strong>{{ $progress }}<small>%</small></strong><span>complete</span></div>
                    </div>
                    <div class="progress-summary"><span><i class="dot green"></i>{{ $completedCount }}
                            completed</span><span>{{ $totalCount }} total</span></div>
                </section>
                <section class="card quick-card"><span class="eyebrow">ONE LESS THING TO REMEMBER</span>
                    <h3>Every repair has a story.</h3>
                    <p>Find diagnoses, parts, and payments in your vehicle’s service history.</p><a
                        href="{{ route('vehicles.index') }}">View vehicles <x-icon name="arrow" /></a>
                </section>
            </div>
        </div>
        <section class="card" id="recent-bookings">
            <div class="section-heading">
                <h2>Recent bookings</h2><a href="{{ route('bookings.index') }}">View all bookings →</a>
            </div>
            {{ $recent->links('pagination.simple', ['label' => 'Recent bookings']) }}
            @include('bookings.table', ['bookings' => $recent])
            {{ $recent->links('pagination.simple', ['label' => 'Recent bookings']) }}
        </section>
    @endif
    @if ($lowStock->isNotEmpty())
        <section class="card">
            <div class="section-heading">
                <h2>Stock that needs attention</h2><a href="{{ route('inventory.index') }}">View inventory →</a>
            </div>
            <div class="stock-chips">
                @foreach ($lowStock->take(6) as $part)
                    <div><x-icon name="box" /><span>{{ $part->name }}<small>{{ $part->quantity }} left · Reorder at
                                {{ $part->reorder_level }}</small></span><span class="badge completed">Low stock</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
    <section class="card" id="notifications">
        <div class="section-heading">
            <h2>Notifications</h2>
            @if ($notifications->isNotEmpty())
                <form method="post" action="{{ route('notifications.read') }}">@csrf<button class="button compact">Mark as
                        read</button></form>
            @endif
        </div>
        @forelse($notifications as $notice)
            <a class="list-row"
            href="{{ route('bookings.show', $notice->data['booking_id']) }}"><span>{{ $notice->data['message'] }}</span><small>{{ $notice->created_at->diffForHumans() }}</small></a>@empty
            <p class="muted">You’re all caught up. Updates will appear here.</p>
        @endforelse
    </section>
@endsection
