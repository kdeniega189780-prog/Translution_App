@extends('layouts.app')
@section('title', 'Vehicle Service History')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">{{ $vehicle->plate_number }}</span>
            <h1>{{ $vehicle->make }} {{ $vehicle->model }}</h1>
            <p>{{ $vehicle->year }} · {{ $vehicle->transmission }} · {{ $vehicle->customer->name }}</p>
        </div><a class="button" href="{{ route('vehicles.edit', $vehicle) }}">Edit vehicle</a>
    </div>
    <div class="tabs"><a class="active" href="{{ route('vehicles.show', $vehicle) }}">Service history</a><a
            href="{{ route('bookings.create', ['vehicle' => $vehicle->id]) }}">Book a check-up</a></div>
    <div id="vehicle-history">
    {{ $history->links('pagination.simple', ['label' => 'Service history']) }}
    @forelse($history as $booking)
        <section class="card history-card">
            <div class="section-heading">
                <div><span class="eyebrow">{{ $booking->created_at->format('M j, Y') }}</span>
                    <h2>{{ $booking->service->name }}</h2>
                </div><x-badge :status="$booking->status" />
            </div>
            <p class="preserve">{{ $booking->diagnosis ?? 'Inspection pending.' }}</p>
            <dl class="details">
                <div>
                    <dt>Staff</dt>
                    <dd>{{ $booking->staff?->name ?? 'Not assigned' }}</dd>
                </div>
                <div>
                    <dt>Parts used</dt>
                    <dd>
                        @forelse($booking->parts as $line)
                            {{ $line->part->name }} × {{ $line->quantity }}{{ !$loop->last ? ', ' : '' }}@empty None
                            recorded
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt>Amount paid</dt>
                    <dd>{{ $booking->invoice?->paid_at ? '₱' . number_format($booking->invoice->total, 2) : 'Not paid' }}</dd>
                </div>
            </dl><a href="{{ route('bookings.show', $booking) }}">View booking and invoice →</a>
    </section>@empty<section class="card empty">
            <h2>No service history yet.</h2>
            <p>Your repairs will appear here as the shop updates your booking.</p>
        </section>
    @endforelse
    {{ $history->links('pagination.simple', ['label' => 'Service history']) }}
    </div>
@endsection
