<?php

namespace App\Http\Controllers;

use App\Application\InventoryWorkflow;
use App\Http\Requests\ListRequest;
use App\Http\Requests\OrderAdvanceRequest;
use App\Http\Requests\OrderRequest;
use App\Models\Part;
use App\Models\PartsOrder;

class OrderController extends Controller
{
    public function index(ListRequest $r)
    {
        return view('orders.index', ['orders' => PartsOrder::with('items.part')->when($r->receive, fn ($q) => $q->where('status', 'ordered'))->latest()->orderByDesc('id')->paginate(config('workshop.card_page_size'))->withQueryString()->fragment('orders-list')]);
    }

    public function create()
    {
        return view('orders.create', ['parts' => Part::orderBy('name')->get(), 'order' => new PartsOrder(['supplier' => config('workshop.supplier')])]);
    }

    public function store(OrderRequest $r, InventoryWorkflow $flow)
    {
        $flow->createOrder($r->user(), $r->validated());

        return to_route('orders.index')->with('status', 'Draft order created.');
    }

    public function advance(OrderAdvanceRequest $r, PartsOrder $order, InventoryWorkflow $flow)
    {
        $flow->advance($r->user(), $order, $r->expected_status);

        return back()->with('status', $r->expected_status === 'draft' ? 'Order marked as placed with the supplier.' : 'Delivery received. Items are now in stock.');
    }

    public function edit(PartsOrder $order)
    {
        abort_unless($order->status === 'draft', 403);

        return view('orders.create', ['parts' => Part::orderBy('name')->get(), 'order' => $order->load('items')]);
    }

    public function update(OrderRequest $r, PartsOrder $order, InventoryWorkflow $flow)
    {
        $flow->updateDraft($r->user(), $order, $r->validated());

        return to_route('orders.index')->with('status', 'Draft order updated.');
    }
}
