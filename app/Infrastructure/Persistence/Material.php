<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = ['name', 'description', 'unit', 'price_cents', 'active'];

    protected function casts(): array
    {
        return ['price_cents' => 'integer', 'stock_quantity' => 'decimal:3', 'active' => 'boolean'];
    }
}
