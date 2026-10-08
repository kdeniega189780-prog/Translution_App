<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPart extends Model
{
    protected $guarded = ['id'];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
