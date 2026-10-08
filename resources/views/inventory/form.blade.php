@extends('layouts.app')
@section('title', 'Part details')
@section('content')<div class="page-heading">
        <div>
            <h1>{{ $part->exists ? 'Edit part' : 'Add inventory part' }}</h1>
            <p>Quantities change only when orders are received or parts are used.</p>
        </div>
    </div>
    <section class="card form-card">
        <form method="post" action="{{ $part->exists ? route('inventory.update', $part) : route('inventory.store') }}">@csrf
            @if ($part->exists)
                @method('PUT')
            @endif
            <x-field name="name" label="Part name" :value="$part->name" maxlength="150" /><x-field name="category"
                label="Category" :value="$part->category" maxlength="80" />
            <div class="form-grid"><x-field name="unit_cost" label="Unit cost (PHP)" type="number" :value="$part->unit_cost"
                    min="0" max="9999999.99" step="0.01" /><x-field name="reorder_level"
                    label="Low-stock warning at" type="number" :value="$part->reorder_level" min="0" max="100000" /></div>
            <x-field name="supplier" label="Supplier" :value="$part->supplier" maxlength="150" />
            <div class="form-actions"><a href="{{ route('inventory.index') }}">Back</a><button class="button primary">Save
                    part</button></div>
        </form>
</section>@endsection
