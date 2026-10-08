@extends('layouts.app')
@section('title', $vehicle->exists ? 'Edit vehicle' : 'Add vehicle')
@section('content')<div class="page-heading">
        <div>
            <h1>{{ $vehicle->exists ? 'Edit vehicle' : 'Add vehicle' }}</h1>
            <p>These details will follow the vehicle through every repair.</p>
        </div>
    </div>
    <section class="card form-card">
        <form method="post" action="{{ $vehicle->exists ? route('vehicles.update', $vehicle) : route('vehicles.store') }}">
            @csrf @if ($vehicle->exists)
                @method('PUT')
            @endif
            @if (auth()->user()->hasRole('admin', 'secretary'))
                <div class="field"><label for="customer_id">Customer</label><select name="customer_id" id="customer_id"
                        required @if ($vehicle->exists) disabled aria-describedby="owner-hint" @endif>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id', $vehicle->customer_id ?? request('customer')) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @if ($vehicle->exists)
                        <input type="hidden" name="customer_id" value="{{ $vehicle->customer_id }}">
                    @endif
                    <small
                        id="owner-hint">{{ $vehicle->exists ? 'Ownership stays fixed to protect service history.' : 'Choose the vehicle owner.' }}
                        <a href="{{ route('customers.create') }}">Add customer</a></small>
                </div>
            @endif
            <x-field name="plate_number" label="Plate number" :value="$vehicle->plate_number" maxlength="20" />
            <div class="form-grid"><x-field name="make" label="Make" :value="$vehicle->make" maxlength="80" /><x-field
                    name="model" label="Model" :value="$vehicle->model" maxlength="80" /><x-field name="year" label="Year"
                    type="number" :value="$vehicle->year ?? date('Y')" min="1950" :max="date('Y') + 1" />
                <div class="field"><label for="transmission">Transmission type</label><select name="transmission"
                        id="transmission">
                        @foreach (['Automatic', 'Manual', 'CVT', 'DCT'] as $type)
                            <option @selected(old('transmission', $vehicle->transmission) === $type)>{{ $type }}</option>
                        @endforeach
                    </select></div>
            </div>
            <div class="form-actions"><a href="{{ route('vehicles.index') }}">Back</a><button class="button primary">Save
                    vehicle</button></div>
        </form>
</section>@endsection
