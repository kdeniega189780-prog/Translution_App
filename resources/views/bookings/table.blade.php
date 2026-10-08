<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Vehicle / Customer</th>
                <th>Service</th>
                <th>Schedule</th>
                <th>Status</th>
                <th><span class="sr-only">Open booking</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td><strong>{{ $booking->vehicle->plate_number }}</strong><small>{{ $booking->customer->name }}</small>
                    </td>
                    <td>{{ $booking->service->name }}</td>
                    <td>{{ ($booking->scheduled_at ?? $booking->preferred_at)->format('M j, Y') }}<small>{{ ($booking->scheduled_at ?? $booking->preferred_at)->format('h:i A') }}
                            {{ $booking->scheduled_at ? '' : '(preferred)' }}</small></td>
                    <td><x-badge :status="$booking->status" /><x-mini-stepper :status="$booking->status" /></td>
                    <td><a class="button compact" href="{{ route('bookings.show', $booking) }}">View <x-icon
                                name="arrow" /></a></td>
            </tr>@empty<tr>
                    <td colspan="5" class="empty">No bookings yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
