<?php

namespace App\Application\Materials;

use App\Domain\Materials\MaterialRepository;
use App\Domain\Materials\MaterialType;
use App\Domain\Materials\MovementDirection;
use App\Domain\Materials\Quantity;

class MaterialRegistry
{
    public function __construct(private readonly MaterialRepository $materials) {}

    public function paginate(MaterialType $type, int $perPage, int $page): array
    {
        return $this->materials->paginate($type, $perPage, $page);
    }

    public function find(MaterialType $type, int $id): array
    {
        return $this->materials->find($type, $id);
    }

    public function save(MaterialType $type, array $data, ?int $id = null): array
    {
        if ($type === MaterialType::PART) {
            $data['unit'] = 'unit';
        }

        return $this->materials->save($type, $data, $id);
    }

    public function delete(MaterialType $type, int $id): void
    {
        $this->materials->delete($type, $id);
    }

    public function move(MaterialType $type, int $id, array $data, int $userId): array
    {
        $quantity = new Quantity($data['quantity']);
        $type->validateQuantity($quantity);

        return $this->materials->move($type, $id, MovementDirection::from($data['direction']), $quantity, $data['reason'], $userId);
    }

    public function movements(MaterialType $type, int $id, int $perPage, int $page): array
    {
        return $this->materials->movements($type, $id, $perPage, $page);
    }
}
