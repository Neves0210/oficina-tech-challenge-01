<?php

namespace App\Domain\Orders;

use InvalidArgumentException;

class ExecutionTimeSummary
{
    private int $count = 0;

    private int $seconds = 0;

    public function add(int $startedAt, int $finishedAt): void
    {
        if ($finishedAt < $startedAt) {
            throw new InvalidArgumentException('A finalização deve ocorrer após o início.');
        }
        $this->seconds += $finishedAt - $startedAt;
        $this->count++;
    }

    public function toArray(): array
    {
        return [
            'orders_count' => $this->count,
            'average_seconds' => $this->count === 0 ? null : round($this->seconds / $this->count, 2),
        ];
    }
}
