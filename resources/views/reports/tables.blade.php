<h2 id="report-sales">
    Payments received</h2>
@if ($sales instanceof \Illuminate\Pagination\LengthAwarePaginator)
    {{ $sales->links('pagination.simple', ['label' => 'Payments']) }}
@endif
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Paid on</th>
                <th>Customer</th>
                <th>Method</th>
                <th>Amount (PHP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $i)
                <tr>
                    <td>{{ $i->number }}</td>
                    <td>{{ $i->paid_at->format('M j, Y') }}</td>
                    <td>{{ $i->booking->customer->name }}</td>
                    <td>{{ $i->payment_method }}</td>
                    <td>{{ number_format($i->total, 2) }}</td>
            </tr>@empty<tr>
                    <td colspan="5">No payments in this period.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">Total sales</th>
                <th>{{ number_format($total, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</div>
@if ($sales instanceof \Illuminate\Pagination\LengthAwarePaginator)
    {{ $sales->links('pagination.simple', ['label' => 'Payments']) }}
@endif
<h2>Bookings created in this period</h2>
<table>
    <thead>
        <tr>
            <th>Current status</th>
            <th>Count</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($statuses as $label => $count)
            <tr>
                <td>{{ $label }}</td>
                <td>{{ $count }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<h2>Most-used parts in this period</h2>
<table>
    <thead>
        <tr>
            <th>Part</th>
            <th>Quantity used</th>
        </tr>
    </thead>
    <tbody>
        @forelse($usage as $p)
            <tr>
                <td>{{ $p['name'] }}</td>
                <td>{{ $p['quantity'] }}</td>
        </tr>@empty<tr>
                <td colspan="2">No parts used in this period.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<h2 id="report-low-stock">Current low-stock list</h2>
@if ($lowStock instanceof \Illuminate\Pagination\LengthAwarePaginator)
    {{ $lowStock->links('pagination.simple', ['label' => 'Low stock']) }}
@endif
<table>
    <thead>
        <tr>
            <th>Part</th>
            <th>In stock</th>
            <th>Reorder level</th>
        </tr>
    </thead>
    <tbody>
        @forelse($lowStock as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td>{{ $p->quantity }}</td>
                <td>{{ $p->reorder_level }}</td>
        </tr>@empty<tr>
                <td colspan="3">All parts are above their reorder levels.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@if ($lowStock instanceof \Illuminate\Pagination\LengthAwarePaginator)
    {{ $lowStock->links('pagination.simple', ['label' => 'Low stock']) }}
@endif
