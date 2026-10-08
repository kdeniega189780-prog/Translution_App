@extends('layouts.app')
@section('title', request()->routeIs('jobs.index') ? 'Repair Jobs' : 'Bookings')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">KEEP EVERY CAR MOVING</span>
            <h1>{{ request()->routeIs('jobs.index') ? 'Repair jobs' : (auth()->user()->role === 'mechanic' ? 'My assigned jobs' : 'Bookings') }}</h1>
            <p>From the first check-up to the final handover.</p>
        </div>@if (auth()->user()->role !== 'mechanic')<a class="button primary" href="{{ route('bookings.create') }}"><x-icon name="plus" />New booking</a>@endif
    </div>
    <section class="card" id="bookings-list">
        <form method="get" class="filters">
            <div><label for="q">Find customer or plate</label><input id="q" name="q"
                    value="{{ request('q') }}" placeholder="Search bookings" maxlength="100"></div>
            <div><label for="date">Confirmed date</label><input type="date" id="date" name="date"
                    value="{{ request('date') }}"></div>
            <div><label for="status">Status</label><select id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach (\App\Domain\Bookings\BookingStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select></div><button class="button">Apply</button><a href="{{ url()->current() }}">Clear</a>
        </form>
        {{ $bookings->links('pagination.simple', ['label' => 'Bookings']) }}
        @include('bookings.table')
        {{ $bookings->links('pagination.simple', ['label' => 'Bookings']) }}
</section><a href="{{ route('dashboard') }}">View the daily schedule →</a>@endsection
