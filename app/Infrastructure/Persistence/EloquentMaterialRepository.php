<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Materials\MaterialRepository;
use App\Domain\Materials\MaterialType;
use App\Domain\Materials\MovementDirection;
use App\Domain\Materials\Quantity;
use App\Domain\Shared\BusinessRuleViolation;
use App\Domain\Shared\RecordNotFound;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentMaterialRepository implements MaterialRepository
{
    public function paginate(MaterialType $type, int $perPage, int $page): array
    {
        return $this->page(Material::where('type', $type->value)->orderBy('id')->paginate($perPage, ['*'], 'page', $page));
    }

    public function find(MaterialType $type, int $id): array
    {
        return $this->record($type, $id)->toArray();
    }

    public function save(MaterialType $type, array $data, ?int $id = null): array
    {
        return DB::transaction(function () use ($type, $data, $id) {
            $material = $id === null ? new Material : $this->record($type, $id, true);
            if ($id !== null && isset($data['unit']) && $material->unit !== $data['unit']
                && (StockMovement::where('material_id', $id)->exists() || ServiceOrderItem::where('material_id', $id)->exists())) {
                throw new BusinessRuleViolation('A unidade não pode mudar após movimentação ou inclusão em OS.');
            }
            $material->fill($data);
            $material->type = $type->value;
            $material->save();

            return $material->refresh()->toArray();
        });
    }

    public function delete(MaterialType $type, int $id): void
    {
        DB::transaction(fn () => $this->record($type, $id, true)->delete());
    }

    public function move(MaterialType $type, int $id, MovementDirection $direction, Quantity $quantity, string $reason, int $userId): array
    {
        return DB::transaction(function () use ($type, $id, $direction, $quantity, $reason, $userId) {
            $type->validateQuantity($quantity);
            $material = $this->record($type, $id, true);
            $balance = new Quantity($material->stock_quantity);
            $balance = $direction === MovementDirection::IN ? $balance->plus($quantity) : $balance->minus($quantity);
            $material->stock_quantity = $balance->format();
            $material->save();
            $movement = StockMovement::create([
                'material_id' => $material->id,
                'user_id' => $userId,
                'direction' => $direction->value,
                'quantity' => $quantity->format(),
                'reason' => $reason,
            ]);

            return ['material' => $material->refresh()->toArray(), 'movement' => $movement->toArray()];
        });
    }

    public function movements(MaterialType $type, int $id, int $perPage, int $page): array
    {
        $this->record($type, $id);

        return $this->page(StockMovement::where('material_id', $id)->orderBy('id')->paginate($perPage, ['*'], 'page', $page));
    }

    private function record(MaterialType $type, int $id, bool $lock = false): Material
    {
        $query = Material::where('type', $type->value)->whereKey($id);

        return ($lock ? $query->lockForUpdate() : $query)->first() ?? throw new RecordNotFound;
    }

    private function page(LengthAwarePaginator $result): array
    {
        return ['data' => $result->items(), 'meta' => [
            'current_page' => $result->currentPage(), 'per_page' => $result->perPage(),
            'total' => $result->total(), 'last_page' => $result->lastPage(),
        ]];
    }
}
