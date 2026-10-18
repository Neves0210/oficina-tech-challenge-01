<?php

namespace App\Application\Registration;

use App\Domain\Services\ServiceRepository;

class ServiceRegistry extends Registry
{
    public function __construct(ServiceRepository $records)
    {
        parent::__construct($records);
    }

    protected function normalize(array $data): array
    {
        return $data;
    }
}
