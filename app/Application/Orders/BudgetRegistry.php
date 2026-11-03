<?php

namespace App\Application\Orders;

use App\Domain\Orders\BudgetRepository;
use App\Domain\Orders\OrderRepository;

class BudgetRegistry
{
    public function __construct(private readonly BudgetRepository $budgets, private readonly OrderRepository $orders) {}

    public function present(int $orderId, int $userId): array
    {
        return $this->budgets->present($orderId, $userId);
    }

    public function paginate(int $orderId, int $perPage, int $page): array
    {
        return $this->budgets->paginate($orderId, $perPage, $page);
    }

    public function find(int $orderId, int $budgetId): array
    {
        return $this->budgets->find($orderId, $budgetId);
    }

    public function revise(int $orderId, int $userId): array
    {
        $this->budgets->revise($orderId, $userId);

        return $this->orders->find($orderId);
    }
}
