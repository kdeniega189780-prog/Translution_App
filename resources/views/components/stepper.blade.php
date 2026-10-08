@props(['status'])
<ol class="stepper" aria-label="Booking progress">
    @foreach (\App\Domain\Bookings\BookingStatus::cases() as $step)
        <li class="{{ $step->position() <= $status->position() ? 'done' : '' }} {{ $step === $status ? 'current' : '' }}"
            @if ($step === $status) aria-current="step" @endif>
            <span>{{ $step->position() < $status->position() ? '✓' : $loop->iteration }}</span>{{ $step->label() }}</li>
    @endforeach
</ol>
