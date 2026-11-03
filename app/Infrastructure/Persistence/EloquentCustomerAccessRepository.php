<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Orders\BudgetDecision;
use App\Domain\Orders\CustomerAccessDenied;
use App\Domain\Orders\CustomerAccessRepository;
use App\Domain\Orders\OrderStatus;
use App\Domain\Shared\BusinessRuleViolation;
use App\Domain\Shared\RecordNotFound;
use Illuminate\Support\Facades\DB;

class EloquentCustomerAccessRepository implements CustomerAccessRepository
{
    public function issue(int $orderId, int $userId): array
    {
        return DB::transaction(function () use ($orderId, $userId) {
            ServiceOrder::whereKey($orderId)->lockForUpdate()->first() ?? throw new RecordNotFound;
            $token = bin2hex(random_bytes(32));
            $access = OrderCustomerAccess::updateOrCreate(['service_order_id' => $orderId], [
                'user_id' => $userId, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDay(),
            ]);

            return ['access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => 86400, 'expires_at' => $access->expires_at->toISOString()];
        });
    }

    public function revoke(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            ServiceOrder::whereKey($orderId)->lockForUpdate()->first() ?? throw new RecordNotFound;
            OrderCustomerAccess::where('service_order_id', $orderId)->delete();
        });
    }

    public function isValid(int $orderId, string $tokenHash): bool
    {
        return OrderCustomerAccess::where('service_order_id', $orderId)->where('token_hash', $tokenHash)->where('expires_at', '>', now())->exists();
    }

    public function decide(int $orderId, int $budgetId, BudgetDecision $decision, string $tokenHash): array
    {
        return DB::transaction(function () use ($orderId, $budgetId, $decision, $tokenHash) {
            $order = ServiceOrder::whereKey($orderId)->lockForUpdate()->first() ?? throw new CustomerAccessDenied;
            // Revalida depois do bloqueio: rotação/revogação não autoriza uma decisão enfileirada.
            $access = OrderCustomerAccess::where('service_order_id', $orderId)->where('token_hash', $tokenHash)->lockForUpdate()->first();
            if (! $access || ! $access->expires_at->isFuture()) {
                throw new CustomerAccessDenied;
            }
            $budget = OrderBudget::where('service_order_id', $orderId)->find($budgetId) ?? throw new RecordNotFound;
            $latestId = OrderBudget::where('service_order_id', $orderId)->orderByDesc('version')->value('id');
            if ($order->status !== OrderStatus::WAITING || $budget->id !== (int) $latestId) {
                throw new BusinessRuleViolation('Somente a proposta vigente aguardando aprovação pode receber decisão.');
            }
            $budget->decision->assertDecisionAllowed($decision);
            $budget->decision = $decision;
            $budget->decided_at = now();
            $budget->save();

            return $budget->load('items')->toArray();
        });
    }
}
