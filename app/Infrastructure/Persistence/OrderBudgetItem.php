<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class OrderBudgetItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_price_cents' => 'integer', 'subtotal_cents' => 'integer'];
    }
}
