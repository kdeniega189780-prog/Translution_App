@extends('layouts.app')
@section('title', request('receive') ? 'Receive Stock' : 'Parts Orders')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">READY FOR THE WEEK AHEAD</span>
            <h1>{{ request('receive') ? 'Receive stock' : 'Parts orders' }}</h1>
            <p>Draft your weekly order, place it, then record the delivery.</p>
        </div><a class="button primary" href="{{ route('orders.create') }}"><x-icon name="plus" />New parts order</a>
    </div>
    <div class="tabs"><a href="{{ route('orders.index') }}" class="{{ !request('receive') ? 'active' : '' }}">All
            orders</a><a href="{{ route('orders.index', ['receive' => 1]) }}"
            class="{{ request('receive') ? 'active' : '' }}">Awaiting delivery</a></div>
    <div id="orders-list">
    {{ $orders->links('pagination.simple', ['label' => 'Orders']) }}
    @forelse($orders as $order)
        <section class="card">
            <div class="section-heading">
                <div><span class="eyebrow">{{ $order->created_at->format('M j, Y') }}</span>
                    <h2>Order #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</h2>
                    <p>{{ $order->supplier }}</p>
                </div><x-badge :status="$order->status" />
            </div>
            <details class="order-parts" @if ($order->status !== 'received') open @endif>
            <summary>{{ $order->items->count() }} {{ $order->items->count() === 1 ? 'part' : 'parts' }} in this order · View details</summary>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Part</th>
                            <th>Quantity</th>
                            <th>Unit cost</th>
                            <th>Line total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>{{ $item->part->name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>₱{{ number_format($item->unit_cost, 2) }}</td>
                                <td>₱{{ number_format($item->quantity * $item->unit_cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </details>
            <div class="form-actions">
                <span>{{ $order->received_at ? 'Received ' . $order->received_at->format('M j, Y h:i A') : ($order->ordered_at ? 'Ordered ' . $order->ordered_at->format('M j, Y') : 'Review before placing the order.') }}</span>
                @if ($order->status === 'draft')
                    <a class="button" href="{{ route('orders.edit', $order) }}">Edit draft</a>
                    @endif @if ($order->status !== 'received')
                        <form method="post" action="{{ route('orders.advance', $order) }}"
                            data-confirm="{{ $order->status === 'draft' ? 'Mark this order as placed with the supplier?' : 'Confirm all listed items and quantities have arrived? Stock will be added once.' }}">
                            @csrf<input type="hidden" name="expected_status" value="{{ $order->status }}"><button
                                class="button primary">{{ $order->status === 'draft' ? 'Mark as ordered' : 'Receive all items' }}
                                <x-icon name="arrow" /></button></form>
                    @endif
            </div>
    </section>@empty<section class="card empty">
            <h2>No {{ request('receive') ? 'deliveries waiting' : 'orders yet' }}.</h2>
            <p>Your weekly parts orders will appear here.</p>
        </section>
    @endforelse
    {{ $orders->links('pagination.simple', ['label' => 'Orders']) }}
    </div>
@endsection
