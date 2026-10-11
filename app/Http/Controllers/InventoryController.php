<?php

namespace App\Http\Controllers;

use App\Application\Audit;
use App\Application\InventoryWorkflow;
use App\Http\Requests\ListRequest;
use App\Http\Requests\PartRequest;
use App\Http\Requests\StockOutRequest;
use App\Models\Part;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(ListRequest $r)
    {
        return view('inventory.index', [
            'parts' => Part::when($r->q, fn ($q, $s) => $q->where('name', 'like', "%$s%"))->orderBy('name')->paginate(config('workshop.table_page_size'))->withQueryString()->fragment('inventory-list'),
            'movements' => StockMovement::with('part', 'user')->latest()->orderByDesc('id')->paginate(config('workshop.table_page_size'), ['*'], 'movements_page')->withQueryString()->fragment('stock-movements'),
        ]);
    }

    public function create()
    {
        return view('inventory.form', ['part' => new Part(['supplier' => config('workshop.supplier'), 'reorder_level' => 5])]);
    }

    public function stockOutForm(Part $part)
    {
        return view('inventory.stock-out', compact('part'));
    }

    public function stockOut(StockOutRequest $r, Part $part, InventoryWorkflow $flow)
    {
        $flow->stockOut($r->user(), $part, $r->validated());

        return to_route('inventory.index')->with('status', 'Stock out recorded. Inventory quantity updated.');
    }

    public function store(PartRequest $r)
    {
        DB::transaction(function () use ($r) {
            $p = Part::create([...$r->validated(), 'quantity' => 0]);
            Audit::record($r->user(), 'part.created', $p);
        });

        return to_route('inventory.index')->with('status', 'Part added. Use a parts order to receive stock.');
    }

    public function edit(Part $part)
    {
        return view('inventory.form', compact('part'));
    }

    public function update(PartRequest $r, Part $part)
    {
        DB::transaction(function () use ($r, $part) {
            $p = Part::lockForUpdate()->findOrFail($part->id);
            $p->update($r->validated());
            Audit::record($r->user(), 'part.edited', $p);
        });

        return to_route('inventory.index')->with('status', 'Part details updated.');
    }
}
