<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $guarded = ['id'];

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
