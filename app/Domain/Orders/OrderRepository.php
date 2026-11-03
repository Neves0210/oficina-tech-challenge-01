<?php

namespace App\Domain\Orders;

interface OrderRepository
{
    public function paginate(int $perPage, int $page): array;

    public function find(int $id): array;

    public function open(array $data, int $userId): array;

    public function diagnose(int $id, string $diagnosis, int $userId): array;

    public function replaceItems(int $id, array $items): array;
}
