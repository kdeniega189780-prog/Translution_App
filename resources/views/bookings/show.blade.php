@extends('layouts.app')
@section('title', 'Booking #' . $booking->id)
@section('content')
    <div class="page-heading">
        <div><a class="eyebrow" href="{{ route('bookings.index') }}">← ALL BOOKINGS</a>
            <h1>{{ $booking->vehicle->make }} {{ $booking->vehicle->model }} <span
                    class="plate">{{ $booking->vehicle->plate_number }}</span></h1>
            <p>Booking #{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }} · {{ $booking->customer->name }}</p>
        </div><x-badge :status="$booking->status" />
    </div>
    <section class="card"><x-stepper :status="$booking->status" /></section>
    <div class="detail-grid">
        <div class="stack">
            <section class="card">
                <h2>Check-up details</h2>
                <dl class="details">
                    <div>
                        <dt>Service</dt>
                        <dd>{{ $booking->service->name }}</dd>
                    </div>
                    <div>
                        <dt>{{ $booking->scheduled_at ? 'Confirmed schedule' : 'Preferred schedule' }}</dt>
                        <dd>{{ ($booking->scheduled_at ?? $booking->preferred_at)->format('M j, Y · h:i A') }}</dd>
                    </div>
                    <div>
                        <dt>Assigned mechanic</dt>
                        <dd>{{ $booking->staff?->name ?? 'Not assigned yet' }}</dd>
                    </div>
                    <div>
                        <dt>Service estimate</dt>
                        <dd>₱{{ number_format($booking->service_price, 2) }} <small>Parts added separately</small></dd>
                    </div>
                    <div>
                        <dt>Contact</dt>
                        <dd>{{ $booking->customer->mobile }}</dd>
                    </div>
                    <div>
                        <dt>Request source</dt>
                        <dd>{{ ucfirst($booking->source) }}</dd>
                    </div>
                </dl>
                <h3>Reported problem</h3>
                <p class="preserve">{{ $booking->problem }}</p>
                @if ($booking->pickup_requested)
                    <p class="badge scheduled">Pick-up / on-site check requested</p>
                    @endif @if ($booking->notes)
                        <h3>Notes</h3>
                        <p class="preserve">{{ $booking->notes }}</p>
                    @endif
                    @can('view', $booking->vehicle)
                    <a href="{{ route('vehicles.show', $booking->vehicle) }}">
                        View vehicle service history →</a>
                    @endcan
            </section>
            <section class="card">
                <div class="section-heading">
                    <h2>Diagnosis & approval</h2><x-badge :status="$booking->consent" />
                </div>
                <p class="preserve">{{ $booking->diagnosis ?? 'The mechanic will add findings after inspection.' }}</p>
                @if ($booking->consented_at)
                    <small>Decision recorded {{ $booking->consented_at->format('M j, Y h:i A') }} ·
                        {{ $booking->consent_note }}</small>
                @endif
                @if ($booking->status->value === 'processing')
                    @can('repair', $booking) @if ($booking->parts->isEmpty())
                        <details class="disclosure" @if (!$booking->diagnosis) open @endif>
                            <summary>{{ $booking->diagnosis ? 'Update diagnosis' : 'Add diagnosis' }}</summary>
                            <form method="post" action="{{ route('bookings.diagnose', $booking) }}">@csrf<x-field
                                    name="diagnosis" label="Findings and proposed repair" type="textarea" :value="$booking->diagnosis"
                                    maxlength="4000" />
                                @if (auth()->user()->role === 'admin')
                                    <x-field name="service_price" label="Service price (PHP)" type="number"
                                        :value="$booking->service_price" min="0" max="9999999.99" step="0.01" />
                                @endif
                                <small>
                                    Changing the diagnosis or price requires fresh customer approval.</small><button
                                    class="button primary">Save diagnosis</button>
                            </form>
                        </details>
                    @endif @endcan
                    @if ($booking->diagnosis && $booking->consent === 'pending' && auth()->user()->can('consent', $booking))
                        <form method="post" action="{{ route('bookings.consent', $booking) }}"
                            data-confirm="Record this repair decision?">@csrf<p>Please review the diagnosis and service
                                estimate before deciding. Parts will be added to the final invoice.</p>
                            @if (auth()->user()->hasRole('admin', 'secretary'))
                                <x-field name="consent_note" label="How did the customer give their decision?"
                                    hint="For example: customer confirmed by phone today." maxlength="1000" />
                            @endif
                            <div class="inline-form">
                                <button class="button primary" name="consent" value="agreed">Agree to repair</button><button
                                    class="button" name="consent" value="declined">Decline repair</button>
                            </div>
                        </form>
                    @elseif($booking->consent === 'declined')
                        <p class="alert">Repair declined. Work and parts usage are paused. Contact the shop to discuss the
                            next steps.</p>
                    @endif
                @endif
            </section>
            <section class="card">
                <h2>Parts used</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($booking->parts as $line)
                                <tr>
                                    <td>{{ $line->part->name }}</td>
                                    <td>{{ $line->quantity }}</td>
                                    <td>₱{{ number_format($line->quantity * $line->unit_price, 2) }}</td>
                            </tr>@empty<tr>
                                    <td colspan="3" class="empty">No parts used yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @can('repair', $booking) @if ($booking->status->value === 'processing' && $booking->consent === 'agreed')
                    <form method="post" action="{{ route('bookings.parts', $booking) }}" class="parts-form">@csrf<div
                            class="field"><label for="part_id">Part from inventory</label><select name="part_id"
                                id="part_id" required>
                                @foreach ($parts as $part)
                                    <option value="{{ $part->id }}" @disabled($part->quantity === 0)>{{ $part->name }} ·
                                        {{ $part->quantity }} in stock · ₱{{ number_format($part->unit_cost, 2) }}</option>
                                @endforeach
                            </select>
                        </div><x-field name="quantity" label="Quantity used" type="number" value="1" min="1"
                            max="10000" /><button class="button primary">Record parts used</button></form>
                @endif @endcan
            </section>
        </div>
        <div class="stack">
            <section class="card next-card"><span class="eyebrow">WHAT’S NEXT</span>
                <h2>{{ ['pending' => 'Confirm the schedule', 'scheduled' => 'Start the repair job', 'processing' => $booking->consent === 'agreed' ? 'Complete the repair' : 'Get customer approval', 'completed' => 'Ready for handover', 'released' => 'All taken care of'][$booking->status->value] }}
                </h2>
                @can('advance', $booking)
                    @if (in_array($booking->status->value, ['pending', 'scheduled', 'processing']))
                        <form method="post" action="{{ route('bookings.advance', $booking) }}">@csrf<input type="hidden"
                                name="expected_status" value="{{ $booking->status->value }}">
                            @if ($booking->status->value === 'pending')
                                <x-field name="scheduled_at" label="Confirmed date and time" type="datetime-local"
                                    :value="$booking->preferred_at->format('Y-m-d\TH:i')" />
                            @elseif($booking->status->value === 'scheduled')
                                <div class="field"><label for="assigned_staff_id">Assign mechanic</label><select
                                        name="assigned_staff_id" id="assigned_staff_id" required>
                                        <option value="">Choose a mechanic</option>
                                        @foreach ($mechanics as $person)
                                            <option value="{{ $person->id }}">{{ $person->name }}</option>
                                        @endforeach
                                    </select>
                            </div>@else<p>Check that the repair is finished and all parts are recorded. Completing the job
                                    creates the invoice and notifies the customer.</p>
                            @endif
                            <button class="button primary full" @disabled($booking->status->value === 'processing' && $booking->consent !== 'agreed')>
                                Next step:
                                {{ ['pending' => 'Schedule', 'scheduled' => 'Start job', 'processing' => 'Complete'][$booking->status->value] }}
                                <x-icon name="arrow" /></button>
                        </form>
                    @endif
                @else<p>
                        {{ ['pending' => 'The shop will contact you to confirm a time.', 'scheduled' => 'Your check-up is scheduled. The shop will update you when work begins.', 'processing' => 'Review the diagnosis here. You can agree to or decline the repair.', 'completed' => 'Your vehicle is ready. Please contact the shop for payment and pick-up.', 'released' => 'Thank you for trusting Transolutions. Your service history is always here.'][$booking->status->value] }}
                </p>@endcan
                @if ($booking->invoice && auth()->user()->can('view', $booking->invoice))
                    <a class="button primary full"
                        href="{{ route('invoices.show', $booking->invoice) }}">{{ $booking->invoice->paid_at ? 'View paid invoice' : 'Next step: View invoice' }}
                        <x-icon name="receipt" /></a>
                @endif
            </section>
            @if (auth()->user()->role === 'admin')
                <section class="card">
                    <details>
                        <summary>Owner controls</summary>
                        @if ($booking->status->value !== 'pending')
                            <form method="post" action="{{ route('bookings.reverse', $booking) }}"
                                data-confirm="Return this booking to its previous step? Reversing a paid booking removes its payment from sales reports and records the reason.">
                                @csrf<x-field name="reason" label="Reason for reversal" type="textarea" minlength="10"
                                maxlength="1000" /><button class="button">Return to previous step</button></form>@else
                            <form method="post" action="{{ route('bookings.destroy', $booking) }}"
                                data-confirm="Archive this booking? Its record will be preserved.">@csrf @method('DELETE')
                                <p>Archive a duplicate or withdrawn request.</p><button class="button danger-button">Archive
                                    booking</button></form>
                        @endif
                    </details>
                </section>
            @endif
        </div>
    </div>
@endsection
