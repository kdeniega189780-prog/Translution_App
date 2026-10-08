@extends('layouts.app')
@section('title', 'Book a Check-up')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">WE’LL TAKE IT FROM HERE</span>
            <h1>Book a check-up</h1>
            <p>Tell us what’s happening. The shop will confirm an available time.</p>
        </div>
    </div>
    <section class="card form-card">
        @if ($vehicles->isEmpty())
            <div class="empty">
                <h2>Add a vehicle first.</h2>
                <p>We’ll use its details for your booking and service history.</p><a class="button primary"
                    href="{{ route('vehicles.create') }}">Add vehicle</a>
        </div>@else<form method="post" action="{{ route('bookings.store') }}">@csrf<div class="form-grid">
                    <div class="field"><label for="vehicle_id">Vehicle</label><select id="vehicle_id" name="vehicle_id"
                            required>
                            <option value="">Select a vehicle</option>
                            @foreach ($vehicles as $v)
                                <option value="{{ $v->id }}" @selected(old('vehicle_id', request('vehicle')) == $v->id)>{{ $v->plate_number }} ·
                                    {{ $v->make }} {{ $v->model }} · {{ $v->customer->name }}</option>
                            @endforeach
                        </select>
                        <small><a href="{{ route('vehicles.create') }}">+ Add another vehicle</a></small>
                    </div>
                    <div class="field"><label for="service_id">Service</label><select name="service_id" id="service_id"
                            required>
                            <option value="">Select a service</option>
                            @foreach ($services as $s)
                                <option value="{{ $s->id }}" @selected(old('service_id') == $s->id)>{{ $s->name }} · from
                                    ₱{{ number_format($s->price, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div><x-field name="problem" label="What seems to be the problem?" type="textarea"
                    maxlength="2000" /><x-field name="preferred_at" label="Preferred date and time" type="datetime-local"
                    :value="now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i')"
                    hint="Up to four confirmed bookings per day. This is a request, not a confirmed slot." /><label
                    class="toggle-row"><input type="checkbox" name="pickup_requested" value="1"
                        @checked(old('pickup_requested'))><span>Request pick-up / on-site check<small>The shop will contact you
                            to arrange the details.</small></span></label><x-field name="notes" label="Additional notes"
                    type="textarea" :required="false" maxlength="2000" />
                @if (auth()->user()->hasRole('admin', 'secretary'))
                    <div class="field"><label for="source">Booking received through</label><select id="source"
                            name="source">
                            @foreach (['walk-in' => 'Walk-in', 'phone' => 'Phone', 'messenger' => 'Messenger'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('source') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="form-actions">
                    <a href="{{ route('bookings.index') }}">Back to bookings</a><button class="button primary">Request
                        booking <x-icon name="arrow" /></button>
                </div>
            </form>
        @endif
</section>@endsection
