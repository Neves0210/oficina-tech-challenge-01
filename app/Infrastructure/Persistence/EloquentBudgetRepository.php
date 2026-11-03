<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Orders\BudgetDecision;
use App\Domain\Orders\BudgetRepository;
use App\Domain\Orders\OrderStatus;
use App\Domain\Shared\BusinessRuleViolation;
use App\Domain\Shared\Money;
use App\Domain\Shared\RecordNotFound;
use Illuminate\Support\Facades\DB;

class EloquentBudgetRepository implements BudgetRepository
{
    public function present(int $orderId, int $userId): array
    {
        return DB::transaction(function () use ($orderId, $userId) {
            $order = ServiceOrder::whereKey($orderId)->lockForUpdate()->first() ?? throw new RecordNotFound;
            $order->status->assertTransitionTo(OrderStatus::WAITING);
            $items = $order->items()->get();
            if ($items->isEmpty() || ! $order->diagnosis) {
                throw new BusinessRuleViolation('Registre o diagnóstico e pelo menos um item antes do orçamento.');
            }
            $total = new Money(0);
            foreach ($items as $item) {
                $total = $total->plus(new Money($item->subtotal_cents));
            }
            $budget = OrderBudget::create([
                'service_order_id' => $orderId, 'user_id' => $userId,
                'version' => ((int) OrderBudget::where('service_order_id', $orderId)->max('version')) + 1,
                'total_cents' => $total->cents, 'decision' => BudgetDecision::PENDING, 'presented_at' => now(),
            ]);
            foreach ($items as $item) {
                $budget->items()->create($item->only(['workshop_service_id', 'material_id', 'kind', 'name', 'unit', 'quantity', 'unit_price_cents', 'subtotal_cents']));
            }
            $order->history()->create(['user_id' => $userId, 'from_status' => $order->status->value, 'to_status' => OrderStatus::WAITING->value]);
            $order->status = OrderStatus::WAITING;
            $order->save();

            return $budget->load('items')->toArray();
        });
    }

    public function paginate(int $orderId, int $perPage, int $page): array
    {
        ServiceOrder::find($orderId) ?? throw new RecordNotFound;
        $result = OrderBudget::with('items')->where('service_order_id', $orderId)->orderByDesc('version')->paginate($perPage, ['*'], 'page', $page);

        return ['data' => $result->items(), 'meta' => ['current_page' => $result->currentPage(), 'per_page' => $result->perPage(), 'total' => $result->total(), 'last_page' => $result->lastPage()]];
    }

    public function find(int $orderId, int $budgetId): array
    {
        return (OrderBudget::with('items')->where('service_order_id', $orderId)->find($budgetId) ?? throw new RecordNotFound)->toArray();
    }

    public function revise(int $orderId, int $userId): void
    {
        DB::transaction(function () use ($orderId, $userId) {
            $order = ServiceOrder::whereKey($orderId)->lockForUpdate()->first() ?? throw new RecordNotFound;
            $order->status->assertTransitionTo(OrderStatus::DIAGNOSIS);
            // A revisão é exclusiva da etapa de aprovação, não um atalho de abertura.
            if ($order->status !== OrderStatus::WAITING) {
                throw new BusinessRuleViolation('A OS precisa estar aguardando aprovação para revisar a proposta.');
            }
            $order->history()->create(['user_id' => $userId, 'from_status' => $order->status->value, 'to_status' => OrderStatus::DIAGNOSIS->value]);
            $order->status = OrderStatus::DIAGNOSIS;
            $order->save();
        });
    }
}
