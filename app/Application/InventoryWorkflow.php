<?php

namespace App\Application;

use App\Models\Part;
use App\Models\PartsOrder;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryWorkflow
{
    public function stockOut(User $actor, Part $part, array $data): void
    {
        abort_unless($actor->hasRole('admin', 'purchaser'), 403);

        DB::transaction(function () use ($actor, $part, $data) {
            $part = Part::lockForUpdate()->findOrFail($part->id);
            if ($part->quantity !== (int) $data['expected_quantity']) {
                throw ValidationException::withMessages(['quantity' => 'Stock has changed since you opened this form. Review the current quantity and try again.']);
            }

            $quantity = (int) $data['quantity'];
            if ($quantity < 1 || $quantity > $part->quantity) {
                throw ValidationException::withMessages(['quantity' => 'Enter a quantity between 1 and the available stock ('.$part->quantity.').']);
            }

            $part->decrement('quantity', $quantity);
            $movement = StockMovement::create([
                'part_id' => $part->id,
                'user_id' => $actor->id,
                'type' => 'stock_out',
                'quantity' => -$quantity,
                'balance' => $part->quantity,
                'note' => $data['reason'],
            ]);
            Audit::record($actor, 'stock.out', $part, [
                'movement_id' => $movement->id,
                'quantity' => $quantity,
                'balance' => $part->quantity,
                'reason' => $data['reason'],
            ]);
        }, 3);
    }

    public function createOrder(User $actor, array $data): PartsOrder
    {
        abort_unless($actor->hasRole('admin', 'purchaser'), 403);

        return DB::transaction(function () use ($actor, $data) {
            $order = PartsOrder::create(['supplier' => $data['supplier'], 'created_by' => $actor->id]);
            foreach ($data['items'] as $item) {
                $order->items()->create($item);
            }
            Audit::record($actor, 'order.created', $order);

            return $order;
        });
    }

    public function advance(User $actor, PartsOrder $order, string $expected): void
    {
        abort_unless($actor->hasRole('admin', 'purchaser'), 403);
        DB::transaction(function () use ($actor, $order, $expected) {
            $o = PartsOrder::lockForUpdate()->findOrFail($order->id);
            if ($o->status !== $expected || $o->status === 'received') {
                throw ValidationException::withMessages(['order' => 'This order changed or has already been received. Refresh the page.']);
            }
            if ($o->status === 'draft') {
                $o->update(['status' => 'ordered', 'ordered_at' => now()]);
            } else {
                foreach ($o->items()->orderBy('part_id')->get() as $item) {
                    $p = Part::lockForUpdate()->findOrFail($item->part_id);
                    $p->increment('quantity', $item->quantity);
                    $p->update(['unit_cost' => $item->unit_cost]);
                    StockMovement::create(['part_id' => $p->id, 'user_id' => $actor->id, 'parts_order_id' => $o->id, 'type' => 'order_received', 'quantity' => $item->quantity, 'balance' => $p->quantity, 'note' => 'Received order #'.$o->id]);
                }
                $o->update(['status' => 'received', 'received_at' => now()]);
            }
            Audit::record($actor, 'order.'.$o->status, $o);
        }, 3);
    }

    public function updateDraft(User $actor, PartsOrder $order, array $data): void
    {
        abort_unless($actor->hasRole('admin', 'purchaser'), 403);
        DB::transaction(function () use ($actor, $order, $data) {
            $order = PartsOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['order' => 'Only draft orders can be edited.']);
            }
            $order->update(['supplier' => $data['supplier']]);
            $order->items()->delete();
            foreach ($data['items'] as $item) {
                $order->items()->create($item);
            }
            Audit::record($actor, 'order.edited', $order);
        });
    }
}
