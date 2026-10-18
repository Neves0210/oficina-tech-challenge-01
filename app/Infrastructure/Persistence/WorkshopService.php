<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class WorkshopService extends Model
{
    protected $fillable = ['name', 'description', 'price_cents'];

    protected function casts(): array
    {
        return ['price_cents' => 'integer'];
    }
}
