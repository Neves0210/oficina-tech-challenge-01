<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Materials\Quantity;
use App\Domain\Orders\BudgetDecision;
use App\Domain\Orders\ExecutionRepository;
use App\Domain\Orders\OrderStatus;
use App\Domain\Shared\BusinessRuleViolation;
use App\Domain\Shared\RecordNotFound;
use Illuminate\Support\Facades\DB;

class EloquentExecutionRepository implements ExecutionRepository
{
    public function start(int $orderId, int $userId): void
    {
        DB::transaction(function () use ($orderId, $userId) {
            $order = ServiceOrder::whereKey($orderId)->lockForUpdate()->first() ?? throw new RecordNotFound;
            $order->status->assertTransitionTo(OrderStatus::EXECUTING);
            $budget = OrderBudget::with('items')->where('service_order_id', $orderId)->orderByDesc('version')->first();
            if (! $budget || $budget->decision !== BudgetDecision::APPROVED || $budget->items->isEmpty()) {
                throw new BusinessRuleViolation('A execução exige orçamento vigente aprovado.');
            }
            $quantities = [];
            foreach ($budget->items as $item) {
                if ($item->material_id !== null) {
                    $quantities[$item->material_id] = ($quantities[$item->material_id] ?? new Quantity(0))->plus(new Quantity($item->quantity));
                }
            }
            ksort($quantities, SORT_NUMERIC);
            $materials = Material::whereIn('id', array_keys($quantities))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $balances = [];
            // Valida todos os materiais antes de persistir qualquer baixa.
            foreach ($quantities as $id => $quantity) {
                $material = $materials->get($id) ?? throw new RecordNotFound;
                $balances[$id] = (new Quantity($material->stock_quantity))->minus($quantity);
            }
            foreach ($quantities as $id => $quantity) {
                $material = $materials->get($id);
                $material->stock_quantity = $balances[$id]->format();
                $material->save();
                StockMovement::create([
                    'material_id' => $id, 'user_id' => $userId, 'service_order_id' => $orderId,
                    'direction' => 'OUT', 'quantity' => $quantity->format(),
                    'reason' => "Execução da OS {$orderId}, orçamento v{$budget->version}",
                ]);
            }
            $order->started_at = now();
            $this->transition($order, OrderStatus::EXECUTING, $userId);
        }, 3);
    }

    public function finish(int $orderId, int $userId): void
    {
        $this->advance($orderId, OrderStatus::FINISHED, $userId);
    }

    public function deliver(int $orderId, int $userId): void
    {
        $this->advance($orderId, OrderStatus::DELIVERED, $userId);
    }

    private function advance(int $orderId, OrderStatus $next, int $userId): void
    {
        DB::transaction(function () use ($orderId, $next, $userId) {
            $order = ServiceOrder::whereKey($orderId)->lockForUpdate()->first() ?? throw new RecordNotFound;
            $order->status->assertTransitionTo($next);
            $previousTime = $next === OrderStatus::FINISHED ? $order->started_at : $order->finished_at;
            if ($previousTime === null || now()->lessThan($previousTime)) {
                throw new BusinessRuleViolation('O instante da etapa é inconsistente com a execução registrada.');
            }
            if ($next === OrderStatus::FINISHED) {
                $order->finished_at = now();
            } else {
                $order->delivered_at = now();
            }
            $this->transition($order, $next, $userId);
        }, 3);
    }

    private function transition(ServiceOrder $order, OrderStatus $next, int $userId): void
    {
        $order->status->assertTransitionTo($next);
        $order->history()->create(['from_status' => $order->status->value, 'to_status' => $next->value, 'user_id' => $userId]);
        $order->status = $next;
        $order->save();
    }
}
