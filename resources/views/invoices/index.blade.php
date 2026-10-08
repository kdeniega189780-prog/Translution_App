@extends('layouts.app')
@section('title', auth()->user()->role === 'customer' ? 'My Invoices' : 'Sales & Invoices')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">CLEAR RECORDS, EVERY TIME</span>
            <h1>{{ auth()->user()->role === 'customer' ? 'My invoices' : 'Sales & invoices' }}</h1>
            <p>Invoices are created when a repair is completed.</p>
        </div>
    </div>
    <section class="card" id="invoices-list">
        {{ $invoices->links('pagination.simple', ['label' => 'Invoices']) }}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer / Vehicle</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td><strong>{{ $invoice->number }}</strong><small>{{ $invoice->created_at->format('M j, Y') }}</small>
                            </td>
                            <td>{{ $invoice->booking->customer->name }}<small>{{ $invoice->booking->vehicle->plate_number }}</small>
                            </td>
                            <td>₱{{ number_format($invoice->total, 2) }}</td>
                            <td><x-badge :status="$invoice->paid_at ? 'paid' : 'unpaid'" />
                                @if ($invoice->paid_at)
                                    <small>{{ $invoice->payment_method }}</small>
                                @endif
                            </td>
                            <td><a class="button compact" href="{{ route('invoices.show', $invoice) }}">View invoice</a></td>
                    </tr>@empty<tr>
                            <td colspan="5" class="empty">No invoices yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $invoices->links('pagination.simple', ['label' => 'Invoices']) }}
</section>@endsection
