<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Services\ServiceRepository;

class EloquentServiceRepository extends EloquentRecordRepository implements ServiceRepository
{
    public function __construct()
    {
        parent::__construct(new WorkshopService);
    }
}
