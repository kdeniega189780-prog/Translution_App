<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['unit_cost' => 'decimal:2'];
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
