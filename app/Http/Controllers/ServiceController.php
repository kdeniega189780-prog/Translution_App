<?php

namespace App\Http\Controllers;

use App\Application\Audit;
use App\Http\Requests\ListRequest;
use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index(ListRequest $r)
    {
        return view('services.index', ['services' => Service::orderBy('name')->paginate(config('workshop.table_page_size'))->withQueryString()->fragment('services-list')]);
    }

    public function create()
    {
        return view('services.form', ['service' => new Service(['active' => true])]);
    }

    public function store(ServiceRequest $r)
    {
        DB::transaction(function () use ($r) {
            $s = Service::create($r->validated());
            Audit::record($r->user(), 'service.created', $s);
        });

        return to_route('services.index')->with('status', 'Service added.');
    }

    public function edit(Service $service)
    {
        return view('services.form', compact('service'));
    }

    public function update(ServiceRequest $r, Service $service)
    {
        DB::transaction(function () use ($r, $service) {
            $service->update($r->validated());
            Audit::record($r->user(), 'service.edited', $service, ['price' => $service->price]);
        });

        return to_route('services.index')->with('status', 'Service updated. Existing bookings keep their quoted price.');
    }
}
