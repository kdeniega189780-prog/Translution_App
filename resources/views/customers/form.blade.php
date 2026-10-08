@extends('layouts.app')
@section('title', $customer->exists ? 'Edit customer' : 'Add customer')
@section('content')<div class="page-heading">
        <div>
            <h1>{{ $customer->exists ? 'Edit customer' : 'Add customer' }}</h1>
            <p>For walk-in, phone, and Messenger bookings.</p>
        </div>
    </div>
    <section class="card form-card">
        <form method="post"
            action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}">@csrf
            @if ($customer->exists)
                @method('PUT')
            @endif
            <x-field name="name" label="Full name" :value="$customer->name" maxlength="120" /><x-field name="mobile"
                label="Mobile number" type="tel" :value="$customer->mobile" /><x-field name="email" label="Contact email"
                type="email" :value="$customer->email" :required="false" />
            <p class="muted">Contact records do not create a sign-in account. Online customers register through Create
                Account.</p>
            <div class="form-actions"><a href="{{ route('customers.index') }}">Back</a><button class="button primary">Save
                    customer</button></div>
        </form>
</section>@endsection
