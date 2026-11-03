<?php

namespace App\Application\Orders;

use App\Domain\Customers\DocumentNumber;
use App\Domain\Orders\OrderRepository;
use App\Domain\Vehicles\Plate;

class OrderRegistry
{
    public function __construct(private readonly OrderRepository $orders) {}

    public function paginate(int $perPage, int $page): array
    {
        return $this->orders->paginate($perPage, $page);
    }

    public function find(int $id): array
    {
        return $this->orders->find($id);
    }

    public function open(array $data, int $userId): array
    {
        $data['customer_document'] = (new DocumentNumber($data['customer_document']))->value;
        $data['vehicle_plate'] = (new Plate($data['vehicle_plate']))->value;

        return $this->orders->open($data, $userId);
    }

    public function diagnose(int $id, string $diagnosis, int $userId): array
    {
        return $this->orders->diagnose($id, $diagnosis, $userId);
    }

    public function replaceItems(int $id, array $items): array
    {
        return $this->orders->replaceItems($id, $items);
    }
}
