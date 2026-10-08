<?php

namespace App\Models;

use App\Domain\Bookings\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => BookingStatus::class, 'preferred_at' => 'datetime', 'scheduled_at' => 'datetime', 'completed_at' => 'datetime', 'consented_at' => 'datetime', 'pickup_requested' => 'boolean', 'service_price' => 'decimal:2'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function parts()
    {
        return $this->hasMany(JobPart::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->role === 'mechanic') {
            return $query->where('assigned_staff_id', $user->id);
        }

        return $user->role === 'customer' ? $query->whereHas('customer', fn ($q) => $q->where('user_id', $user->id)) : $query;
    }
}
