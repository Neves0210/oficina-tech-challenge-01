<?php

namespace App\Application\Registration;

use App\Domain\Vehicles\Plate;
use App\Domain\Vehicles\VehicleRepository;

class VehicleRegistry extends Registry
{
    public function __construct(VehicleRepository $records)
    {
        parent::__construct($records);
    }

    protected function normalize(array $data): array
    {
        if (isset($data['plate'])) {
            $data['plate'] = (new Plate($data['plate']))->value;
        }

        return $data;
    }
}
