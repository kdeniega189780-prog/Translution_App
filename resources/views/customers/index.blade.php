@extends('layouts.app')
@section('title', 'Customers & Vehicles')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">PEOPLE BEHIND THE PLATES</span>
            <h1>Customers & vehicles</h1>
            <p>One record for every customer. A complete history for every car.</p>
        </div><a class="button primary" href="{{ route('customers.create') }}"><x-icon name="plus" />Add customer</a>
    </div>
    <div class="tabs"><a class="active" href="{{ route('customers.index') }}">Customers</a><a
            href="{{ route('vehicles.index') }}">Vehicles & history</a></div>
    <section class="card" id="customers-list">
        <form method="get" class="filters"><label for="q">Find customer</label><input name="q" id="q"
                value="{{ request('q') }}" placeholder="Customer name"><button class="button compact">Search</button>
        </form>
        {{ $customers->links('pagination.simple', ['label' => 'Customers']) }}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Vehicles</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td><strong>{{ $customer->name }}</strong><small>{{ $customer->email }}</small></td>
                            <td>{{ $customer->mobile }}</td>
                            <td>{{ $customer->vehicles_count }}</td>
                            <td>
                                <div class="inline-form"><a class="button compact"
                                        href="{{ route('customers.edit', $customer) }}">Edit</a><a class="button compact"
                                        href="{{ route('vehicles.create', ['customer' => $customer->id]) }}">Add vehicle</a>
                                    @if (auth()->user()->role === 'admin')
                                        <form method="post" action="{{ route('customers.destroy', $customer) }}"
                                            data-confirm="Archive this customer and disable their portal account? History will be preserved.">
                                            @csrf @method('DELETE')<button class="button compact">Archive</button></form>
                                    @endif
                                </div>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="4" class="empty">No customers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $customers->links('pagination.simple', ['label' => 'Customers']) }}
</section>@endsection
