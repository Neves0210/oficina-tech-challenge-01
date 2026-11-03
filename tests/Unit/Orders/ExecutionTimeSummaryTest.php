<?php

namespace Tests\Unit\Orders;

use App\Domain\Orders\ExecutionTimeSummary;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ExecutionTimeSummaryTest extends TestCase
{
    public function test_empty_summary_and_rounding(): void
    {
        $summary = new ExecutionTimeSummary;
        $this->assertSame(['orders_count' => 0, 'average_seconds' => null], $summary->toArray());
        $summary->add(100, 101);
        $summary->add(100, 100);
        $summary->add(100, 100);
        $this->assertSame(['orders_count' => 3, 'average_seconds' => 0.33], $summary->toArray());
    }

    public function test_reversed_interval_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ExecutionTimeSummary)->add(101, 100);
    }
}
