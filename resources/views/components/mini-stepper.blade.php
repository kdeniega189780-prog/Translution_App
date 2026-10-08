@props(['status'])
<ol class="mini-stepper" aria-label="Booking progress: {{ $status->label() }}">
    @foreach (\App\Domain\Bookings\BookingStatus::cases() as $step)
        <li class="{{ $step->position() <= $status->position() ? 'done' : '' }}" title="{{ $step->label() }}"
            @if ($step === $status) aria-current="step" @endif><span
                class="sr-only">{{ $step->label() }}</span></li>
    @endforeach
</ol>
