<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $u, Booking $b): bool
    {
        return $u->hasRole('admin', 'secretary') || $this->repair($u, $b) || ($u->role === 'customer' && $b->customer->user_id === $u->id);
    }

    public function repair(User $u, Booking $b): bool
    {
        return $u->role === 'admin' || ($u->role === 'mechanic' && (int) $b->assigned_staff_id === $u->id);
    }

    public function advance(User $u, Booking $b): bool
    {
        return $u->role === 'admin' || ($b->status->value === 'processing'
            ? $this->repair($u, $b)
            : $u->role === 'secretary');
    }

    public function consent(User $u, Booking $b): bool
    {
        return $u->hasRole('admin', 'secretary') || ($u->role === 'customer' && $b->customer->user_id === $u->id);
    }

    public function reverse(User $u, Booking $b): bool
    {
        return $u->role === 'admin';
    }

    public function delete(User $u, Booking $b): bool
    {
        return $u->role === 'admin';
    }
}
