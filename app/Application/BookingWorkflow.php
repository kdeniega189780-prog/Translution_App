<?php

namespace App\Application;

use App\Domain\Bookings\BookingStatus as Status;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\VehicleReady;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BookingWorkflow
{
    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['workflow' => $message]);
        }
    }

    public function create(User $actor, array $data): Booking
    {
        return DB::transaction(function () use ($actor, $data) {
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);
            Gate::forUser($actor)->authorize('view', $vehicle);
            $this->require(! $vehicle->customer->trashed(), 'This customer is archived.');
            $service = Service::where('active', true)->findOrFail($data['service_id']);
            $booking = Booking::create([...$data, 'customer_id' => $vehicle->customer_id, 'service_price' => $service->price, 'created_by' => $actor->id, 'source' => $actor->role === 'customer' ? 'online' : $data['source'] ?? 'walk-in', 'status' => Status::Pending]);
            Audit::record($actor, 'booking.created', $booking);

            return $booking;
        });
    }

    public function advance(User $actor, Booking $booking, array $data): void
    {
        DB::transaction(function () use ($actor, $booking, $data) {
            $b = Booking::lockForUpdate()->findOrFail($booking->id);
            Gate::forUser($actor)->authorize('advance', $b);
            $this->require($b->status->value === $data['expected_status'], 'This booking changed. Refresh the page before continuing.');
            $from = $b->status;
            if ($from === Status::Pending) {
                $this->require(! empty($data['scheduled_at']), 'Choose a confirmed date and time.');
                $at = Carbon::parse($data['scheduled_at']);
                DB::table('schedule_days')->insertOrIgnore(['date' => $at->toDateString()]);
                DB::table('schedule_days')->where('date', $at->toDateString())->lockForUpdate()->first();
                $this->require(Booking::whereDate('scheduled_at', $at->toDateString())->count() < config('workshop.daily_capacity'), 'That day already has four bookings. Choose another day.');
                $this->require(! Booking::where('scheduled_at', $at)->exists(), 'That time is already booked. Choose another time.');
                $b->scheduled_at = $at;
            } elseif ($from === Status::Scheduled) {
                $this->require(User::where('role', 'mechanic')->where('active', true)->whereKey($data['assigned_staff_id'] ?? 0)->exists(), 'Select an active mechanic.');
                $b->assigned_staff_id = $data['assigned_staff_id'];
            } elseif ($from === Status::Processing) {
                $this->require((bool) $b->diagnosis && $b->consent === 'agreed', 'Add a diagnosis and get customer approval before completing the repair.');
                $b->completed_at = now();
            } else {
                $this->require(false, 'Use the invoice to record payment.');
            }
            $b->status = $from->next();
            $b->save();
            if ($b->status === Status::Completed) {
                $this->invoice($b);
                $b->customer->user?->notify(new VehicleReady($b));
            }
            Audit::record($actor, 'booking.advanced', $b, ['from' => $from->value, 'to' => $b->status->value]);
        }, 3);
    }

    private function invoice(Booking $b): void
    {
        $i = Invoice::withTrashed()->firstOrNew(['booking_id' => $b->id]);
        $this->require(! $i->paid_at, 'Paid invoices are locked.');
        if ($i->trashed()) {
            $i->restore();
        }
        $i->fill(['number' => 'TR-'.str_pad((string) $b->id, 6, '0', STR_PAD_LEFT), 'total' => 0])->save();
        $i->items()->delete();
        $i->items()->create(['description' => $b->service->name, 'quantity' => 1, 'unit_price' => $b->service_price]);
        foreach ($b->parts()->with('part')->get() as $line) {
            $i->items()->create(['description' => $line->part->name, 'quantity' => $line->quantity, 'unit_price' => $line->unit_price]);
        }
        $total = '0.00';
        foreach ($i->items as $line) {
            $total = bcadd($total, bcmul((string) $line->quantity, (string) $line->unit_price, 2), 2);
        }
        $i->update(['total' => $total]);
    }

    public function diagnose(User $actor, Booking $booking, array $data): void
    {
        DB::transaction(function () use ($actor, $booking, $data) {
            $b = Booking::lockForUpdate()->findOrFail($booking->id);
            Gate::forUser($actor)->authorize('repair', $b);
            $this->require($b->status === Status::Processing, 'Diagnosis can be changed only during processing.');
            $this->require(! $b->parts()->exists(), 'Parts have already been used. The approved diagnosis is locked.');
            if (! $actor->hasRole('admin')) {
                unset($data['service_price']);
            }
            $b->fill([...$data, 'consent' => 'pending', 'consented_at' => null, 'consent_recorded_by' => null, 'consent_note' => null])->save();
            Audit::record($actor, 'booking.diagnosed', $b, ['diagnosis' => $b->diagnosis, 'service_price' => $b->service_price]);
        });
    }

    public function consent(User $actor, Booking $booking, array $data): void
    {
        Gate::forUser($actor)->authorize('consent', $booking);
        DB::transaction(function () use ($actor, $booking, $data) {
            $b = Booking::lockForUpdate()->findOrFail($booking->id);
            $this->require($b->status === Status::Processing && (bool) $b->diagnosis && $b->consent === 'pending', 'No diagnosis is waiting for approval.');
            $this->require($actor->role === 'customer' || ! empty($data['consent_note']), 'Record how the customer gave their decision.');
            $b->update(['consent' => $data['consent'], 'consent_recorded_by' => $actor->id, 'consented_at' => now(), 'consent_note' => $data['consent_note'] ?? 'Customer responded in the portal.']);
            Audit::record($actor, 'booking.consent', $b, ['decision' => $b->consent, 'note' => $b->consent_note]);
        });
    }

    public function usePart(User $actor, Booking $booking, array $data): void
    {
        DB::transaction(function () use ($actor, $booking, $data) {
            $b = Booking::lockForUpdate()->findOrFail($booking->id);
            Gate::forUser($actor)->authorize('repair', $b);
            $this->require($b->status === Status::Processing && $b->consent === 'agreed', 'Parts can only be used on an approved repair in progress.');
            $part = Part::lockForUpdate()->findOrFail($data['part_id']);
            $qty = (int) $data['quantity'];
            $this->require($qty > 0 && $part->quantity >= $qty, 'Not enough stock. Receive more stock or reduce the quantity.');
            $part->decrement('quantity', $qty);
            $b->parts()->create(['part_id' => $part->id, 'quantity' => $qty, 'unit_price' => $part->unit_cost]);
            StockMovement::create(['part_id' => $part->id, 'user_id' => $actor->id, 'booking_id' => $b->id, 'type' => 'job_usage', 'quantity' => -$qty, 'balance' => $part->quantity, 'note' => 'Used on booking #'.$b->id]);
            Audit::record($actor, 'stock.used', $b, ['part_id' => $part->id, 'quantity' => $qty]);
        }, 3);
    }

    public function pay(User $actor, Invoice $invoice, string $method): void
    {
        Gate::forUser($actor)->authorize('pay', $invoice);
        DB::transaction(function () use ($actor, $invoice, $method) {
            $b = Booking::lockForUpdate()->findOrFail($invoice->booking_id);
            $i = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->require(! $i->paid_at && $b->status === Status::Completed, 'This invoice is already paid or the job is not completed.');
            $this->require(in_array($method, ['Cash', 'Bank Transfer', 'GCash', 'PayMaya'], true), 'Choose a supported payment method.');
            $i->update(['payment_method' => $method, 'paid_at' => now(), 'paid_by' => $actor->id]);
            $b->update(['status' => Status::Released]);
            Audit::record($actor, 'payment.recorded', $i, ['amount' => $i->total, 'method' => $method]);
        }, 3);
    }

    public function reverse(User $actor, Booking $booking, string $reason): void
    {
        Gate::forUser($actor)->authorize('reverse', $booking);
        DB::transaction(function () use ($actor, $booking, $reason) {
            $b = Booking::lockForUpdate()->findOrFail($booking->id);
            $from = $b->status;
            $this->require($from !== Status::Pending, 'This booking is already pending.');
            // Reversals preserve consumed stock: only receipt/use actions change quantities.
            if ($from === Status::Released) {
                $i = $b->invoice()->lockForUpdate()->firstOrFail();
                Audit::record($actor, 'payment.reversed', $i, ['amount' => $i->total, 'method' => $i->payment_method, 'paid_at' => $i->paid_at, 'reason' => $reason]);
                $i->update(['paid_at' => null, 'payment_method' => null, 'paid_by' => null]);
            }
            if ($from === Status::Completed) {
                $b->invoice?->delete();
                $b->completed_at = null;
            }
            if ($from === Status::Processing) {
                $this->require(! $b->parts()->exists(), 'A job with consumed parts cannot be returned to scheduling.');
                $b->assigned_staff_id = null;
                $b->consent = 'pending';
            }
            if ($from === Status::Scheduled) {
                $b->scheduled_at = null;
            }
            $b->status = Status::cases()[$from->position() - 1];
            $b->save();
            Audit::record($actor, 'booking.reversed', $b, ['from' => $from->value, 'to' => $b->status->value, 'reason' => $reason]);
        }, 3);
    }

    public function archive(User $actor, Booking $booking): void
    {
        Gate::forUser($actor)->authorize('delete', $booking);
        DB::transaction(function () use ($actor, $booking) {
            $b = Booking::lockForUpdate()->findOrFail($booking->id);
            $this->require($b->status === Status::Pending, 'Only pending bookings can be archived.');
            $b->delete();
            Audit::record($actor, 'booking.archived', $b);
        });
    }
}
