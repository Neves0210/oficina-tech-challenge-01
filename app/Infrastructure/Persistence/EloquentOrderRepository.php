<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Materials\MaterialType;
use App\Domain\Materials\Quantity;
use App\Domain\Orders\OrderRepository;
use App\Domain\Orders\OrderStatus;
use App\Domain\Shared\BusinessRuleViolation;
use App\Domain\Shared\Money;
use App\Domain\Shared\RecordNotFound;
use Illuminate\Support\Facades\DB;

class EloquentOrderRepository implements OrderRepository
{
    public function paginate(int $perPage, int $page): array
    {
        $result = ServiceOrder::withCount('items')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return ['data' => $result->items(), 'meta' => ['current_page' => $result->currentPage(), 'per_page' => $result->perPage(), 'total' => $result->total(), 'last_page' => $result->lastPage()]];
    }

    public function find(int $id): array
    {
        return $this->view($this->record($id));
    }

    public function open(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $customer = Customer::where('document', $data['customer_document'])->first() ?? throw new RecordNotFound;
            $vehicle = Vehicle::where('plate', $data['vehicle_plate'])->lockForUpdate()->first() ?? throw new RecordNotFound;
            if ((int) $vehicle->customer_id !== $customer->id) {
                throw new BusinessRuleViolation('O veículo não pertence ao cliente informado.');
            }
            $order = new ServiceOrder;
            $order->customer_id = $customer->id;
            $order->vehicle_id = $vehicle->id;
            $order->status = OrderStatus::RECEIVED;
            $order->complaint = $data['complaint'];
            $order->save();
            $order->history()->create(['from_status' => null, 'to_status' => OrderStatus::RECEIVED->value, 'user_id' => $userId]);
            $this->writeItems($order, $data['items'] ?? []);

            return $this->view($order);
        });
    }

    public function diagnose(int $id, string $diagnosis, int $userId): array
    {
        return DB::transaction(function () use ($id, $diagnosis, $userId) {
            $order = $this->record($id, true);
            $order->status->assertEditable();
            if ($order->status === OrderStatus::RECEIVED) {
                $order->status->assertTransitionTo(OrderStatus::DIAGNOSIS);
                $order->history()->create(['from_status' => $order->status->value, 'to_status' => OrderStatus::DIAGNOSIS->value, 'user_id' => $userId]);
                $order->status = OrderStatus::DIAGNOSIS;
            }
            $order->diagnosis = $diagnosis;
            $order->save();

            return $this->view($order);
        });
    }

    public function replaceItems(int $id, array $items): array
    {
        return DB::transaction(function () use ($id, $items) {
            $order = $this->record($id, true);
            $order->status->assertEditable();
            $order->items()->delete();
            $this->writeItems($order, $items);

            return $this->view($order);
        });
    }

    private function writeItems(ServiceOrder $order, array $items): void
    {
        $total = new Money(0);
        foreach ($items as $item) {
            $quantity = new Quantity($item['quantity']);
            if ($item['kind'] === 'SERVICE') {
                MaterialType::PART->validateQuantity($quantity);
                $catalog = WorkshopService::find($item['catalog_id']) ?? throw new RecordNotFound;
                $unit = 'service';
            } else {
                $type = MaterialType::from($item['kind']);
                $type->validateQuantity($quantity);
                $catalog = Material::where('type', $type->value)->find($item['catalog_id']) ?? throw new RecordNotFound;
                if (! $catalog->active) {
                    throw new BusinessRuleViolation('Material inativo não pode ser incluído na OS.');
                }
                $unit = $catalog->unit;
            }
            $subtotal = (new Money($catalog->price_cents))->times($quantity);
            $total = $total->plus($subtotal);
            $order->items()->create([
                'kind' => $item['kind'], 'name' => $catalog->name, 'unit' => $unit,
                'quantity' => $quantity->format(), 'unit_price_cents' => $catalog->price_cents, 'subtotal_cents' => $subtotal->cents,
                'workshop_service_id' => $item['kind'] === 'SERVICE' ? $catalog->id : null,
                'material_id' => $item['kind'] === 'SERVICE' ? null : $catalog->id,
            ]);
        }
    }

    private function record(int $id, bool $lock = false): ServiceOrder
    {
        $query = ServiceOrder::whereKey($id);

        return ($lock ? $query->lockForUpdate() : $query)->first() ?? throw new RecordNotFound;
    }

    private function view(ServiceOrder $order): array
    {
        $order->load(['items', 'history', 'budgets.items']);
        $data = $order->toArray();
        $total = new Money(0);
        foreach ($order->items as $item) {
            $total = $total->plus(new Money($item->subtotal_cents));
        }
        $data['items_total_cents'] = $total->cents;

        return $data;
    }
}
