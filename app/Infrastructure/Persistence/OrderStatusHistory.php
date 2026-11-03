<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $table = 'order_status_history';

    protected $guarded = ['id'];
}
