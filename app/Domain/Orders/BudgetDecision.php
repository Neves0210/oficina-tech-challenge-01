<?php

namespace App\Domain\Orders;

enum BudgetDecision: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}
