<?php

namespace App\Http\Controllers;

use App\Application\Audit;
use App\Http\Requests\ListRequest;
use App\Http\Requests\VehicleRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function index(ListRequest $r)
    {
        return view('vehicles.index', ['vehicles' => Vehicle::with('customer')->whereHas('customer', fn ($q) => $r->user()->role === 'customer' ? $q->where('user_id', $r->user()->id) : $q)->when($r->q, fn ($q, $s) => $q->where('plate_number', 'like', "%$s%"))->orderBy('plate_number')->paginate(config('workshop.table_page_size'))->withQueryString()->fragment('vehicles-list')]);
    }

    public function create()
    {
        return view('vehicles.form', ['vehicle' => new Vehicle, 'customers' => auth()->user()->hasRole('admin', 'secretary') ? Customer::orderBy('name')->get() : collect()]);
    }

    public function store(VehicleRequest $r)
    {
        $v = DB::transaction(function () use ($r) {
            $data = $r->validated();
            $data['customer_id'] = $r->user()->role === 'customer' ? $r->user()->customer->id : $data['customer_id'];
            Customer::findOrFail($data['customer_id']);
            $data['plate_number'] = strtoupper($data['plate_number']);
            $v = Vehicle::create($data);
            Audit::record($r->user(), 'vehicle.created', $v);

            return $v;
        });

        return to_route('vehicles.show', $v)->with('status', 'Vehicle added.');
    }

    public function show(ListRequest $r, Vehicle $vehicle)
    {
        $this->authorize('view', $vehicle);

        return view('vehicles.show', ['vehicle' => $vehicle, 'history' => $vehicle->bookings()->with(['service', 'staff', 'parts.part', 'invoice'])->latest()->orderByDesc('id')->paginate(config('workshop.card_page_size'))->withQueryString()->fragment('vehicle-history')]);
    }

    public function edit(Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle);

        return view('vehicles.form', ['vehicle' => $vehicle, 'customers' => auth()->user()->hasRole('admin', 'secretary') ? Customer::orderBy('name')->get() : collect()]);
    }

    public function update(VehicleRequest $r, Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle);
        DB::transaction(function () use ($r, $vehicle) {
            $data = $r->validated();
            unset($data['customer_id']);
            $data['plate_number'] = strtoupper($data['plate_number']);
            $vehicle->update($data);
            Audit::record($r->user(), 'vehicle.edited', $vehicle);
        });

        return to_route('vehicles.show', $vehicle)->with('status', 'Vehicle updated.');
    }
}
