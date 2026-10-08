<?php

namespace App\Http\Controllers;

use App\Application\Audit;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\ListRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function index(ListRequest $r)
    {
        return view('customers.index', ['customers' => Customer::withCount('vehicles')->when($r->q, fn ($q, $s) => $q->where('name', 'like', "%$s%"))->orderBy('name')->orderBy('id')->paginate(config('workshop.table_page_size'))->withQueryString()->fragment('customers-list')]);
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(CustomerRequest $r)
    {
        $c = DB::transaction(function () use ($r) {
            $c = Customer::create($r->validated());
            Audit::record($r->user(), 'customer.created', $c);

            return $c;
        });

        return to_route('vehicles.create', ['customer' => $c->id])->with('status', 'Customer added. Add their vehicle next.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $r, Customer $customer)
    {
        DB::transaction(function () use ($r, $customer) {
            $customer->update($r->validated());
            Audit::record($r->user(), 'customer.edited', $customer);
        });

        return to_route('customers.index')->with('status', 'Customer updated.');
    }

    public function destroy(Request $r, Customer $customer)
    {
        DB::transaction(function () use ($r, $customer) {
            $c = Customer::lockForUpdate()->findOrFail($customer->id);
            if ($c->bookings()->where('status', '!=', 'released')->exists()) {
                throw ValidationException::withMessages(['customer' => 'Finish or archive open bookings first.']);
            } $c->delete();
            if ($c->user) {
                $c->user->update(['active' => false, 'session_version' => $c->user->session_version + 1]);
            } Audit::record($r->user(), 'customer.archived', $c);
        });

        return back()->with('status', 'Customer archived. History is preserved.');
    }
}
