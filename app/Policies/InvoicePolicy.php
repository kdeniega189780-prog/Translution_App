<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $u, Invoice $i): bool
    {
        return $u->hasRole('admin', 'secretary') || ($u->role === 'customer' && $i->booking->customer->user_id === $u->id);
    }

    public function pay(User $u, Invoice $i): bool
    {
        return $u->hasRole('admin', 'secretary');
    }
}
