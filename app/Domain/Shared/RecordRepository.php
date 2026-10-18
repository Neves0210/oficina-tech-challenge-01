<?php

namespace App\Domain\Shared;

interface RecordRepository
{
    public function paginate(int $perPage, int $page): array;

    /** @throws RecordNotFound */
    public function find(int $id): array;

    public function save(array $data, ?int $id = null): array;

    public function delete(int $id): void;
}
