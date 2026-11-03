<?php

namespace App\Domain\Orders;

use App\Domain\Shared\BusinessRuleViolation;

enum OrderStatus: string
{
    case RECEIVED = 'Recebida';
    case DIAGNOSIS = 'Em diagnóstico';
    case WAITING = 'Aguardando aprovação';
    case EXECUTING = 'Em execução';
    case FINISHED = 'Finalizada';
    case DELIVERED = 'Entregue';

    public function assertEditable(): void
    {
        if (! in_array($this, [self::RECEIVED, self::DIAGNOSIS], true)) {
            throw new BusinessRuleViolation('Retorne ao diagnóstico antes de alterar a OS.');
        }
    }

    public function assertTransitionTo(self $next): void
    {
        $allowed = match ($this) {
            self::RECEIVED => [self::DIAGNOSIS],
            self::DIAGNOSIS => [self::WAITING],
            self::WAITING => [self::DIAGNOSIS, self::EXECUTING],
            self::EXECUTING => [self::FINISHED],
            self::FINISHED => [self::DELIVERED],
            self::DELIVERED => [],
        };
        if (! in_array($next, $allowed, true)) {
            throw new BusinessRuleViolation('Transição de status não permitida.');
        }
    }
}
