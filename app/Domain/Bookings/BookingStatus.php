<?php

namespace App\Domain\Bookings;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case Processing = 'processing';
    case Completed = 'completed';
    case Released = 'released';

    public function label(): string
    {
        return $this === self::Released ? 'Paid / Released' : ucfirst($this->value);
    }

    public function position(): int
    {
        return array_search($this, self::cases(), true);
    }

    public function next(): ?self
    {
        return self::cases()[$this->position() + 1] ?? null;
    }
}
