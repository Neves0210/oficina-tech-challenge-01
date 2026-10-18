<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Shared\RecordNotFound;
use App\Domain\Shared\RecordRepository;
use Illuminate\Database\Eloquent\Model;

abstract class EloquentRecordRepository implements RecordRepository
{
    public function __construct(protected Model $model) {}

    public function paginate(int $perPage, int $page): array
    {
        $result = $this->model->newQuery()->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $result->getCollection()->map(fn (Model $record) => $record->toArray())->all(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
            ],
        ];
    }

    public function find(int $id): array
    {
        return $this->record($id)->toArray();
    }

    public function save(array $data, ?int $id = null): array
    {
        $record = $id === null ? $this->model->newInstance() : $this->record($id);
        $record->fill($data)->save();

        return $record->refresh()->toArray();
    }

    public function delete(int $id): void
    {
        $this->record($id)->delete();
    }

    private function record(int $id): Model
    {
        return $this->model->newQuery()->find($id) ?? throw new RecordNotFound;
    }
}
