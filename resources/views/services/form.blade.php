@extends('layouts.app')
@section('title', 'Service details')
@section('content')<div class="page-heading">
        <div>
            <h1>Service details</h1>
            <p>Existing bookings keep their quoted service price.</p>
        </div>
    </div>
    <section class="card form-card">
        <form method="post" action="{{ $service->exists ? route('services.update', $service) : route('services.store') }}">
            @csrf @if ($service->exists)
                @method('PUT')
            @endif
            <x-field name="name" label="Service name" :value="$service->name" maxlength="150" /><x-field name="price"
                label="Starting price (PHP)" type="number" :value="$service->price" min="0" step="0.01"
                max="9999999.99" /><input type="hidden" name="active" value="0"><label class="toggle-row"><input
                    type="checkbox" name="active" value="1" @checked(old('active', $service->active))><span>Available for new
                    bookings</span></label>
            <div class="form-actions"><a href="{{ route('services.index') }}">Back</a><button class="button primary">Save
                    service</button></div>
        </form>
</section>@endsection
