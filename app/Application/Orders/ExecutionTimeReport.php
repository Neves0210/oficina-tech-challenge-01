<?php

namespace App\Application\Orders;

use App\Domain\Orders\ExecutionTimeRepository;

class ExecutionTimeReport
{
    public function __construct(private readonly ExecutionTimeRepository $repository) {}

    public function summary(): array
    {
        return $this->repository->summarize()->toArray();
    }
}
