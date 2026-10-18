<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Vehicles\VehicleRepository;

class EloquentVehicleRepository extends EloquentRecordRepository implements VehicleRepository
{
    public function __construct()
    {
        parent::__construct(new Vehicle);
    }
}
