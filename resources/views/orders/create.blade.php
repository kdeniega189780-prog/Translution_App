@extends('layouts.app')
@section('title', 'New parts order')
@section('content')<div class="page-heading">
        <div>
            <h1>{{ $order->exists ? 'Edit draft order' : 'New parts order' }}</h1>
            <p>Build this week’s list for your supplier.</p>
        </div>
    </div>
    <section class="card">
        <form method="post" action="{{ $order->exists ? route('orders.update', $order) : route('orders.store') }}">@csrf
            @if ($order->exists)
                @method('PUT')
            @endif
            <x-field name="supplier" label="Supplier" :value="$order->supplier" maxlength="150" />
            <div id="order-lines">
                @foreach (old('items', $order->exists ? $order->items->toArray() : [['part_id' => '', 'quantity' => 1, 'unit_cost' => '']]) as $n => $item)
                    <div class="order-line">
                        <div class="field"><label for="part-{{ $n }}">Part</label><select
                                id="part-{{ $n }}" name="items[{{ $n }}][part_id]" required
                                data-order-part>
                                <option value="">Select a part</option>
                                @foreach ($parts as $p)
                                    <option value="{{ $p->id }}" data-cost="{{ $p->unit_cost }}"
                                        @selected($item['part_id'] == $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label for="qty-{{ $n }}">Quantity</label><input
                                id="qty-{{ $n }}" name="items[{{ $n }}][quantity]" type="number"
                                value="{{ $item['quantity'] }}" min="1" max="10000" required></div>
                        <div class="field"><label for="cost-{{ $n }}">Unit cost (PHP)</label><input
                                id="cost-{{ $n }}" name="items[{{ $n }}][unit_cost]" type="number"
                                value="{{ $item['unit_cost'] }}" min="0" max="9999999.99" step="0.01" required
                                data-order-cost></div><button type="button" class="button compact"
                            data-remove-line>Remove</button>
                    </div>
                @endforeach
            </div><button type="button" class="button" data-add-line><x-icon name="plus" />Add another part</button>
            <div class="form-actions"><a href="{{ route('orders.index') }}">Back</a><button class="button primary">Save
                    draft order</button></div>
        </form>
</section>@endsection
