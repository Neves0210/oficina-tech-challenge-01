<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = ['material_id', 'user_id', 'service_order_id', 'direction', 'quantity', 'reason'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }
}
