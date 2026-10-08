@extends('layouts.app')
@section('title', auth()->user()->role === 'customer' ? 'My Vehicles' : 'Vehicles & History')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">CARE THAT GOES THE DISTANCE</span>
            <h1>{{ auth()->user()->role === 'customer' ? 'My vehicles' : 'Vehicles & history' }}</h1>
            <p>Your vehicle details and every visit, together.</p>
        </div><a class="button primary" href="{{ route('vehicles.create') }}"><x-icon name="plus" />Add vehicle</a>
    </div>
    @if (auth()->user()->hasRole('admin', 'secretary'))
        <div class="tabs"><a href="{{ route('customers.index') }}">Customers</a><a class="active"
                href="{{ route('vehicles.index') }}">Vehicles & history</a></div>
    @endif
    <section class="card" id="vehicles-list">
        <form method="get" class="filters"><label for="q">Find plate</label><input name="q" id="q"
                value="{{ request('q') }}" placeholder="Plate number"><button class="button compact">Search</button></form>
        {{ $vehicles->links('pagination.simple', ['label' => 'Vehicles']) }}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Plate</th>
                        <th>Vehicle</th>
                        <th>Owner</th>
                        <th>Transmission</th>
                        <th>History</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                        <tr>
                            <td><strong>{{ $vehicle->plate_number }}</strong></td>
                            <td>{{ $vehicle->make }} {{ $vehicle->model }}<small>{{ $vehicle->year }}</small></td>
                            <td>{{ $vehicle->customer->name }}</td>
                            <td>{{ $vehicle->transmission }}</td>
                            <td><a class="button compact" href="{{ route('vehicles.show', $vehicle) }}">View history <x-icon
                                        name="arrow" /></a></td>
                    </tr>@empty<tr>
                            <td colspan="5" class="empty">No vehicles yet. Add one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $vehicles->links('pagination.simple', ['label' => 'Vehicles']) }}
</section>@endsection
