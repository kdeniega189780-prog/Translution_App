@extends('layouts.app')
@section('title', $invoice->number)
@section('content')<div class="page-heading no-print">
        <div>
            <h1>Invoice {{ $invoice->number }}</h1>
            <p>A complete breakdown of services and parts.</p>
        </div><button class="button" data-print>Print invoice</button>
    </div>
    <section class="card invoice">
        <div class="section-heading">
            <div>
                <h2>TRANSOLUTIONS</h2>
                <p>Automatic Transmission Overhaul<br>Davao City, Philippines</p>
            </div>
            <div class="text-right">
                <h2>INVOICE</h2><strong>{{ $invoice->number }}</strong>
                <p>{{ $invoice->created_at->format('F j, Y') }}</p><x-badge :status="$invoice->paid_at ? 'paid' : 'unpaid'" />
            </div>
        </div>
        <hr>
        <div class="form-grid">
            <div><span class="eyebrow">BILL TO</span>
                <h3>{{ $invoice->booking->customer->name }}</h3>
                <p>{{ $invoice->booking->customer->mobile }}<br>{{ $invoice->booking->customer->email }}</p>
            </div>
            <div><span class="eyebrow">VEHICLE</span>
                <h3>{{ $invoice->booking->vehicle->plate_number }}</h3>
                <p>{{ $invoice->booking->vehicle->make }} {{ $invoice->booking->vehicle->model }}<br>Booking
                    #{{ $invoice->booking_id }}</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Service / Part</th>
                        <th>Quantity</th>
                        <th>Unit price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>₱{{ number_format($item->unit_price, 2) }}</td>
                            <td>₱{{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="invoice-total"><span>Total (PHP)</span><strong>₱{{ number_format($invoice->total, 2) }}</strong></div>
        @if ($invoice->paid_at)
            <p class="alert success">Paid via {{ $invoice->payment_method }} on
                {{ $invoice->paid_at->format('M j, Y h:i A') }}. Vehicle released.</p>
        @endif
        <p class="invoice-thanks">
            Thank you for trusting us with your journey.</p>
    </section>
    @can('pay', $invoice) @unless ($invoice->paid_at)
    <section class="card form-card no-print">
        <h2>Record payment</h2>
        <p>Confirm the full amount has been received. This releases the vehicle.</p>
        <form method="post" action="{{ route('invoices.pay', $invoice) }}"
            data-confirm="Confirm payment of PHP {{ number_format($invoice->total, 2) }} has been received? The vehicle will be marked Paid / Released.">
            @csrf<div class="field"><label for="payment_method">Payment method</label><select id="payment_method"
                    name="payment_method" required>
                    <option value="">Choose payment method</option>
                    @foreach (['Cash', 'Bank Transfer', 'GCash', 'PayMaya'] as $method)
                        <option>{{ $method }}</option>
                    @endforeach
                </select>
            </div><button class="button primary">Mark paid & release vehicle</button></form>
    </section>
    @endunless @endcan
<div class="no-print"><a href="{{ route('bookings.show', $invoice->booking) }}">← Back to booking</a></div>@endsection
