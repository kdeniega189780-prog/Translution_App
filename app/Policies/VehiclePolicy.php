<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function view(User $u, Vehicle $v): bool
    {
        return $u->hasRole('admin', 'secretary') || ($u->role === 'customer' && $v->customer->user_id === $u->id);
    }

    public function update(User $u, Vehicle $v): bool
    {
        return $this->view($u, $v);
    }
}
