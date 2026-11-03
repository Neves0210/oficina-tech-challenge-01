<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class OrderCustomerAccess extends Model
{
    protected $table = 'order_customer_access';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime'];
    }
}
