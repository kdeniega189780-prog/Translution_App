@extends('layouts.app')
@section('title', 'Inventory')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">EVERY PART ACCOUNTED FOR</span>
            <h1>Inventory</h1>
            <p>Track stock received, parts used, and stock out in one place.</p>
        </div>
        @if (auth()->user()->role === 'admin')
        <a class="button primary" href="{{ route('inventory.create') }}"><x-icon name="plus" />Add part</a>@else<a
                class="button primary" href="{{ route('orders.create') }}"><x-icon name="plus" />Order parts</a>
        @endif
    </div>
    <section class="card" id="inventory-list">
        <form method="get" class="filters"><label for="q">Find a part</label><input id="q" name="q"
                value="{{ request('q') }}" placeholder="Part name"><button class="button compact">Search</button></form>
        {{ $parts->links('pagination.simple', ['label' => 'Inventory']) }}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Part / Supplier</th>
                        <th>Category</th>
                        <th>In stock</th>
                        <th>Unit cost</th>
                        <th>Reorder at</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parts as $part)
                        <tr>
                            <td><strong>{{ $part->name }}</strong><small>{{ $part->supplier }}</small></td>
                            <td>{{ $part->category }}</td>
                            <td>{{ $part->quantity }} @if ($part->quantity === 0)
                                    <span class="badge declined">Out of stock</span>
                                @elseif ($part->quantity <= $part->reorder_level)
                                    <span class="badge completed">Low stock</span>
                                @endif
                            </td>
                            <td>₱{{ number_format($part->unit_cost, 2) }}</td>
                            <td>{{ $part->reorder_level }}</td>
                            <td><div class="inline-form">
                                @if (auth()->user()->role === 'admin')
                                    <a class="button compact" href="{{ route('inventory.edit', $part) }}">Edit</a>
                                @endif
                                @if ($part->quantity > 0)
                                    <a class="button compact" href="{{ route('inventory.stock-out.create', $part) }}"
                                        aria-label="Stock Out: {{ $part->name }}">Stock Out</a>
                                @else
                                    <button class="button compact" disabled>Stock Out</button>
                                @endif
                            </div></td>
                        </tr>
                    @empty<tr>
                            <td colspan="6" class="empty">No parts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $parts->links('pagination.simple', ['label' => 'Inventory']) }}
    </section>
    <section class="card" id="stock-movements">
        <div class="section-heading">
            <h2>Recent stock movements</h2><a href="{{ route('orders.index') }}">Parts orders →</a>
        </div>
        {{ $movements->links('pagination.simple', ['label' => 'Stock movements']) }}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Part</th>
                        <th>Movement</th>
                        <th>Change</th>
                        <th>Balance</th>
                        <th>Recorded by / Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        <tr>
                            <td>{{ $m->created_at->format('M j, h:i A') }}</td>
                            <td>{{ $m->part->name }}</td>
                            <td>{{ match ($m->type) { 'stock_out' => 'Stock Out', 'order_received' => 'Stock In', 'job_usage' => 'Repair usage', default => ucfirst(str_replace('_', ' ', $m->type)) } }}</td>
                            <td>{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                            <td>{{ $m->balance }}</td>
                            <td>{{ $m->user->name }}<small>{{ $m->note }}</small></td>
                    </tr>@empty<tr>
                            <td colspan="6" class="empty">No stock movements yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $movements->links('pagination.simple', ['label' => 'Stock movements']) }}
</section>@endsection
