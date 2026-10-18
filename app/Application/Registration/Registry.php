<?php

namespace App\Application\Registration;

use App\Domain\Shared\RecordRepository;

abstract class Registry
{
    public function __construct(protected readonly RecordRepository $records) {}

    public function paginate(int $perPage, int $page): array
    {
        return $this->records->paginate($perPage, $page);
    }

    public function find(int $id): array
    {
        return $this->records->find($id);
    }

    public function save(array $data, ?int $id = null): array
    {
        if ($id !== null) {
            $this->records->find($id);
        }

        return $this->records->save($this->normalize($data), $id);
    }

    public function delete(int $id): void
    {
        $this->records->delete($id);
    }

    abstract protected function normalize(array $data): array;
}
