<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Orders\ExecutionTimeRepository;
use App\Domain\Orders\ExecutionTimeSummary;
use App\Domain\Orders\OrderStatus;

class EloquentExecutionTimeRepository implements ExecutionTimeRepository
{
    public function summarize(): ExecutionTimeSummary
    {
        $summary = new ExecutionTimeSummary;
        $orders = ServiceOrder::query()
            ->whereIn('status', [OrderStatus::FINISHED->value, OrderStatus::DELIVERED->value])
            ->whereNotNull('started_at')->whereNotNull('finished_at')
            ->whereColumn('finished_at', '>=', 'started_at')
            ->select(['id', 'started_at', 'finished_at'])->cursor();
        foreach ($orders as $order) {
            $summary->add($order->started_at->getTimestamp(), $order->finished_at->getTimestamp());
        }

        return $summary;
    }
}
