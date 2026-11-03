<?php

namespace App\Domain\Orders;

interface CustomerAccessRepository
{
    public function issue(int $orderId, int $userId): array;

    public function revoke(int $orderId): void;

    public function isValid(int $orderId, string $tokenHash): bool;

    public function decide(int $orderId, int $budgetId, BudgetDecision $decision, string $tokenHash): array;
}
