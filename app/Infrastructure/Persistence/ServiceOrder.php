<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Orders\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    protected function casts(): array
    {
        return ['status' => OrderStatus::class, 'started_at' => 'immutable_datetime', 'finished_at' => 'immutable_datetime', 'delivered_at' => 'immutable_datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class)->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }
}
