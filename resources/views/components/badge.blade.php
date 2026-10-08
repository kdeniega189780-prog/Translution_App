@props(['status'])
@php($value = $status instanceof \App\Domain\Bookings\BookingStatus ? $status->value : $status)
<span
    class="badge {{ $value }}">{{ $status instanceof \App\Domain\Bookings\BookingStatus ? $status->label() : ucfirst($value) }}</span>
