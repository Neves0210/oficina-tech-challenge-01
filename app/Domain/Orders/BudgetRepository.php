<?php

namespace App\Domain\Orders;

interface BudgetRepository
{
    public function present(int $orderId, int $userId): array;

    public function paginate(int $orderId, int $perPage, int $page): array;

    public function find(int $orderId, int $budgetId): array;

    public function revise(int $orderId, int $userId): void;
}
