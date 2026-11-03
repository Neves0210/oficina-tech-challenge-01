<?php

namespace App\Domain\Materials;

interface MaterialRepository
{
    public function paginate(MaterialType $type, int $perPage, int $page): array;

    public function find(MaterialType $type, int $id): array;

    public function save(MaterialType $type, array $data, ?int $id = null): array;

    public function delete(MaterialType $type, int $id): void;

    public function move(MaterialType $type, int $id, MovementDirection $direction, Quantity $quantity, string $reason, int $userId): array;

    public function movements(MaterialType $type, int $id, int $perPage, int $page): array;
}
