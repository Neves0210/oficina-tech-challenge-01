<?php

namespace App\Application\Orders;

use App\Domain\Orders\BudgetDecision;
use App\Domain\Orders\BudgetRepository;
use App\Domain\Orders\CustomerAccessRepository;
use App\Domain\Orders\OrderRepository;

class CustomerPortal
{
    public function __construct(private readonly CustomerAccessRepository $access, private readonly OrderRepository $orders, private readonly BudgetRepository $budgets) {}

    public function issue(int $orderId, int $userId): array
    {
        return $this->access->issue($orderId, $userId);
    }

    public function revoke(int $orderId): void
    {
        $this->access->revoke($orderId);
    }

    public function isValid(int $orderId, string $hash): bool
    {
        return $this->access->isValid($orderId, $hash);
    }

    public function show(int $orderId): array
    {
        $source = $this->orders->find($orderId);
        $data = array_intersect_key($source, array_flip(['id', 'status', 'complaint', 'diagnosis', 'started_at', 'finished_at', 'delivered_at', 'items_total_cents']));
        $data['items'] = array_map($this->item(...), $source['items']);
        $data['history'] = array_map(fn ($row) => array_intersect_key($row, array_flip(['from_status', 'to_status', 'created_at'])), $source['history']);
        $data['budgets'] = array_map($this->budgetView(...), $source['budgets']);

        return $data;
    }

    public function budget(int $orderId, int $budgetId): array
    {
        return $this->budgetView($this->budgets->find($orderId, $budgetId));
    }

    public function decide(int $orderId, int $budgetId, BudgetDecision $decision, string $hash): array
    {
        return $this->budgetView($this->access->decide($orderId, $budgetId, $decision, $hash));
    }

    private function budgetView(array $source): array
    {
        $data = array_intersect_key($source, array_flip(['id', 'version', 'total_cents', 'decision', 'presented_at', 'decided_at']));
        $data['items'] = array_map($this->item(...), $source['items']);

        return $data;
    }

    private function item(array $source): array
    {
        return array_intersect_key($source, array_flip(['kind', 'name', 'unit', 'quantity', 'unit_price_cents', 'subtotal_cents']));
    }
}
