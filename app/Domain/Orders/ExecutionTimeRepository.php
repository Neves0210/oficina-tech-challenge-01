<?php

namespace App\Domain\Orders;

interface ExecutionTimeRepository
{
    public function summarize(): ExecutionTimeSummary;
}
