<?php

namespace App\Application\Orders;

use App\Domain\Orders\ExecutionRepository;
use App\Domain\Orders\OrderRepository;

class OrderExecution
{
    public function __construct(private readonly ExecutionRepository $execution, private readonly OrderRepository $orders) {}

    public function start(int $orderId, int $userId): array
    {
        $this->execution->start($orderId, $userId);

        return $this->orders->find($orderId);
    }

    public function finish(int $orderId, int $userId): array
    {
        $this->execution->finish($orderId, $userId);

        return $this->orders->find($orderId);
    }

    public function deliver(int $orderId, int $userId): array
    {
        $this->execution->deliver($orderId, $userId);

        return $this->orders->find($orderId);
    }
}
