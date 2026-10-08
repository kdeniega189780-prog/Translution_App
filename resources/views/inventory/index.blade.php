@extends('layouts.app')
@section('title', 'Inventory')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">EVERY PART ACCOUNTED FOR</span>
            <h1>Inventory</h1>
            <p>Stock received and parts used, recorded automatically.</p>
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
                        @if (auth()->user()->role === 'admin')
                            <th>Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($parts as $part)
                        <tr>
                            <td><strong>{{ $part->name }}</strong><small>{{ $part->supplier }}</small></td>
                            <td>{{ $part->category }}</td>
                            <td>{{ $part->quantity }} @if ($part->quantity <= $part->reorder_level)
                                    <span class="badge completed">Low stock</span>
                                @endif
                            </td>
                            <td>₱{{ number_format($part->unit_cost, 2) }}</td>
                            <td>{{ $part->reorder_level }}</td>
                            @if (auth()->user()->role === 'admin')
                                <td><a class="button compact" href="{{ route('inventory.edit', $part) }}">Edit</a></td>
                            @endif
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
                            <td>{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                            <td>{{ $m->balance }}</td>
                            <td>{{ $m->user->name }}<small>{{ $m->note }}</small></td>
                    </tr>@empty<tr>
                            <td colspan="5" class="empty">No stock movements yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $movements->links('pagination.simple', ['label' => 'Stock movements']) }}
</section>@endsection
