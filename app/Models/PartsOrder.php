<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartsOrder extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ordered_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
