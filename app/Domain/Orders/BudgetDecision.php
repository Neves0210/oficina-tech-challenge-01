<?php

namespace App\Domain\Orders;

use App\Domain\Shared\BusinessRuleViolation;

enum BudgetDecision: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    public function assertDecisionAllowed(self $next): void
    {
        if ($this !== self::PENDING || $next === self::PENDING) {
            throw new BusinessRuleViolation('A decisão da proposta já foi registrada ou é inválida.');
        }
    }
}
