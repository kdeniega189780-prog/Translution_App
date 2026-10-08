<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
