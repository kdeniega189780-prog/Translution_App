<?php

namespace App\Http\Controllers;

use App\Application\BookingWorkflow;
use App\Http\Requests\ListRequest;
use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    public function index(ListRequest $r)
    {
        return view('invoices.index', ['invoices' => Invoice::with('booking.customer', 'booking.vehicle')->whereHas('booking', fn ($q) => $q->visibleTo($r->user()))->latest()->orderByDesc('id')->paginate(config('workshop.table_page_size'))->withQueryString()->fragment('invoices-list')]);
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return view('invoices.show', ['invoice' => $invoice->load(['items', 'booking.customer', 'booking.vehicle'])]);
    }

    public function pay(PaymentRequest $r, Invoice $invoice, BookingWorkflow $flow)
    {
        $flow->pay($r->user(), $invoice, $r->payment_method);

        return back()->with('status', 'Payment recorded. Vehicle is paid and released.');
    }
}
