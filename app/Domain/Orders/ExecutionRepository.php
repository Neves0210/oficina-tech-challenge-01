<?php

namespace App\Domain\Orders;

interface ExecutionRepository
{
    public function start(int $orderId, int $userId): void;

    public function finish(int $orderId, int $userId): void;

    public function deliver(int $orderId, int $userId): void;
}
