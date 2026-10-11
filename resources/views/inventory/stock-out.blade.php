@extends('layouts.app')
@section('title', 'Stock Out')
@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">EVERY PART ACCOUNTED FOR</span>
            <h1>Stock Out</h1>
            <p>Record parts removed from inventory, such as damaged items or supplier returns.</p>
        </div>
    </div>
    <section class="card form-card">
        <h2>{{ $part->name }}</h2>
        <dl class="details">
            <div><dt>Category</dt><dd>{{ $part->category }}</dd></div>
            <div><dt>Available stock</dt><dd>{{ $part->quantity }} units</dd></div>
        </dl>
        <p>For parts used on a customer's vehicle, record them on the repair job so they appear on the invoice.</p>
        @if ($part->quantity > 0)
            <form method="post" action="{{ route('inventory.stock-out.store', $part) }}"
                data-confirm="Record this stock out and reduce the available quantity?">
                @csrf
                <input type="hidden" name="expected_quantity" value="{{ $part->quantity }}">
                <x-field name="quantity" label="Quantity to remove" type="number" min="1"
                    :max="min($part->quantity, 10000)" step="1" value="1" />
                <x-field name="reason" label="Reason for stock out" type="textarea" maxlength="255"
                    placeholder="e.g. Two damaged filters returned to the supplier"
                    hint="This reason and your name will appear in the stock movement history." />
                <div class="form-actions">
                    <a href="{{ route('inventory.index') }}">Cancel</a>
                    <button class="button primary" type="submit">Record stock out</button>
                </div>
            </form>
        @else
            <div class="alert">This part is out of stock. Receive stock before recording a stock out.</div>
            <a class="button" href="{{ route('inventory.index') }}">Back to inventory</a>
        @endif
    </section>
@endsection
