@extends('layouts.app')
@section('title', $role === 'mechanic' ? 'My Workbench' : 'Front Desk')
@section('content')
    @php($mechanic = $role === 'mechanic')
    <div class="page-heading">
        <div><span class="eyebrow">{{ $mechanic ? 'MECHANIC' : 'SECRETARY' }} WORKSPACE · {{ now()->format('l, M j') }}</span>
            <h1>{{ $mechanic ? 'Your workbench.' : 'A smoother day at the front desk.' }}</h1>
            <p>{{ $mechanic ? 'Your assigned vehicles, findings, and repairs in one place.' : 'Coordinate appointments, keep customers informed, and prepare every handover.' }}</p>
        </div>
        <a class="button primary" href="{{ route($mechanic ? 'jobs.index' : 'bookings.create') }}"><x-icon :name="$mechanic ? 'wrench' : 'plus'" />{{ $mechanic ? 'Open active repairs' : 'New booking' }}</a>
    </div>
    <div class="stats">
        <a class="stat card stat-blue" href="{{ route('bookings.index', ['status' => $mechanic ? 'processing' : 'pending']) }}"><span class="stat-icon blue"><x-icon :name="$mechanic ? 'wrench' : 'calendar'" /></span><p>{{ $mechanic ? 'My active repairs' : 'Awaiting scheduling' }}</p><strong>{{ $mechanic ? $processingCount : $pendingCount }}</strong><small>{{ $mechanic ? 'Assigned to you' : 'Confirm a date and time' }}</small></a>
        <div class="stat card stat-lavender"><span class="stat-icon lavender"><x-icon name="people" /></span><p>Awaiting customer approval</p><strong>{{ $approvalCount }}</strong><small>{{ $mechanic ? 'Wait for approval before using parts' : 'Follow up on repair decisions' }}</small></div>
        <a class="stat card stat-rose" href="{{ route('bookings.index', ['status' => 'completed']) }}"><span class="stat-icon green"><x-icon name="check" /></span><p>Ready for handover</p><strong>{{ $readyCount }}</strong><small>{{ $mechanic ? 'Completed repairs awaiting release' : 'Record payment and release' }}</small></a>
        <div class="stat card stat-yellow"><span class="stat-icon yellow"><x-icon name="car" /></span><p>{{ $mechanic ? 'My completed repairs' : 'Today’s appointments' }}</p><strong>{{ $mechanic ? $completedCount : $todayCount }}</strong><small>{{ $mechanic ? 'Ready or released' : 'Your daily workshop schedule' }}</small></div>
    </div>
    <div class="team-grid">
        <section class="card" id="work-queue">
            <div class="section-heading"><div><h2>{{ $mechanic ? 'On your repair bench' : 'Booking & assignment queue' }}</h2><p>{{ $mechanic ? 'Open a job to record findings, add parts, or complete a repair.' : 'Confirm requests, then assign an available mechanic to start each job.' }}</p></div></div>
            @forelse ($workQueue as $booking)
                <a class="team-job" href="{{ route('bookings.show', $booking) }}">
                    <span class="team-job-icon"><x-icon name="car" /></span>
                    <span class="grow"><strong>{{ $booking->vehicle->make }} {{ $booking->vehicle->model }}</strong><small>{{ $booking->vehicle->plate_number }} · {{ $booking->service->name }}</small><small>{{ $mechanic ? ($booking->diagnosis ? 'Approval: '.ucfirst($booking->consent) : 'Inspection and diagnosis needed') : $booking->customer->name.' · '.$booking->preferred_at->format('M j, h:i A') }}</small></span>
                    <x-badge :status="$booking->status" /><x-icon name="arrow" />
                </a>
            @empty
                <div class="empty"><x-icon :name="$mechanic ? 'wrench' : 'calendar'" /><h3>{{ $mechanic ? 'Your bench is clear.' : 'The booking queue is clear.' }}</h3><p>{{ $mechanic ? 'Jobs will appear here when the secretary assigns them to you.' : 'New customer requests will appear here.' }}</p></div>
            @endforelse
            {{ $workQueue->links('pagination.simple', ['label' => 'Work queue']) }}
        </section>
        <div class="stack">
            <section class="card quick-card"><span class="eyebrow">{{ $mechanic ? 'REPAIR CHECKLIST' : 'FRONT DESK SHORTCUTS' }}</span>
                @if ($mechanic)
                    <h2>Every detail counts.</h2><ol class="repair-checklist"><li>Inspect and record the diagnosis.</li><li>Wait for the customer’s approval.</li><li>Record every part used.</li><li>Complete the repair for handover.</li></ol><p>The secretary handles customer decisions, payment, and release.</p>
                @else
                    <h2>Keep the day moving.</h2><a class="list-row" href="{{ route('customers.index') }}">Customers & vehicles <x-icon name="people" /></a><a class="list-row" href="{{ route('invoices.index') }}">Invoices & payments <x-icon name="receipt" /></a><a class="list-row" href="{{ route('bookings.index', ['status' => 'processing']) }}">Check repair progress <x-icon name="wrench" /></a>
                @endif
            </section>
            <section class="card"><h2>{{ $mechanic ? 'My schedule' : 'Daily schedule' }}</h2><form class="inline-form date-filter" method="get"><label for="date">Date</label><input id="date" name="date" type="date" value="{{ $date }}"><button class="button compact">View</button></form>
                @forelse ($schedule as $booking)
                    <a class="list-row" href="{{ route('bookings.show', $booking) }}"><span><strong>{{ $booking->scheduled_at->format('h:i A') }}</strong><small>{{ $booking->vehicle->plate_number }} · {{ $booking->staff?->name ?? 'Mechanic not assigned' }}</small></span><x-badge :status="$booking->status" /></a>
                @empty <p class="muted">No appointments for this day.</p> @endforelse
            </section>
        </div>
    </div>
    <section class="card" id="notifications"><h2>Notifications</h2>
        @if ($notifications->isNotEmpty())<form method="post" action="{{ route('notifications.read') }}">@csrf<button class="button compact">Mark as read</button></form>@endif
        @forelse ($notifications as $notice)<p>{{ $notice->data['message'] }}</p>@empty<p class="muted">You’re all caught up.</p>@endforelse
    </section>
@endsection
