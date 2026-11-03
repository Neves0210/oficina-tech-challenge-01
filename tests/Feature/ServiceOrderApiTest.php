<?php

namespace Tests\Feature;

use App\Domain\Orders\OrderStatus;
use App\Infrastructure\Persistence\ServiceOrder;
use App\Infrastructure\Security\AdminToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderApiTest extends TestCase
{
    use RefreshDatabase;

    private array $opening;

    private int $service;

    private int $supply;

    protected function setUp(): void
    {
        parent::setUp();
        config(['jwt.secret' => str_repeat('s', 64)]);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->withToken(app(AdminToken::class)->issue($admin->id));
        $customer = $this->postJson('/api/customers', ['name' => 'Cliente', 'document' => '52998224725'])->assertCreated()->json('data.id');
        $this->postJson('/api/vehicles', ['customer_id' => $customer, 'plate' => 'ABC1234', 'brand' => 'Fiat', 'model' => 'Uno', 'year' => 2014])->assertCreated();
        $this->service = $this->postJson('/api/services', ['name' => 'Troca', 'price_cents' => 5000])->assertCreated()->json('data.id');
        $this->supply = $this->postJson('/api/supplies', ['name' => 'Óleo', 'price_cents' => 101, 'unit' => 'L'])->assertCreated()->json('data.id');
        $this->opening = ['customer_document' => '529.982.247-25', 'vehicle_plate' => 'abc-1234', 'complaint' => 'Troca de óleo solicitada', 'items' => [
            ['kind' => 'SERVICE', 'catalog_id' => $this->service, 'quantity' => 1],
            ['kind' => 'SUPPLY', 'catalog_id' => $this->supply, 'quantity' => '0.5'],
        ]];
    }

    public function test_opening_links_customer_vehicle_and_snapshots_without_consuming_stock(): void
    {
        $r = $this->postJson('/api/service-orders', $this->opening)->assertCreated()->assertJsonPath('data.status', 'Recebida')
            ->assertJsonPath('data.items_total_cents', 5051)->assertJsonPath('data.items.1.subtotal_cents', 51)
            ->assertJsonPath('data.history.0.from_status', null)->assertJsonPath('data.history.0.to_status', 'Recebida');
        $id = $r->json('data.id');
        $this->patchJson('/api/services/'.$this->service, ['price_cents' => 9999])->assertOk();
        $this->getJson('/api/service-orders/'.$id)->assertOk()->assertJsonPath('data.items_total_cents', 5051);
        $this->getJson('/api/service-orders?per_page=1')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.items_count', 2);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->getJson('/api/supplies/'.$this->supply)->assertJsonPath('data.stock_quantity', '0.000');
        $this->deleteJson('/api/services/'.$this->service)->assertConflict();
        $this->deleteJson('/api/supplies/'.$this->supply)->assertConflict();
        $this->patchJson('/api/supplies/'.$this->supply, ['unit' => 'kg'])->assertConflict();
    }

    public function test_diagnosis_changes_status_once_and_items_can_be_replaced_or_cleared(): void
    {
        $id = $this->postJson('/api/service-orders', $this->opening)->assertCreated()->json('data.id');
        $this->postJson("/api/service-orders/{$id}/diagnosis", ['diagnosis' => 'Filtro sujo'])->assertOk()
            ->assertJsonPath('data.status', 'Em diagnóstico')->assertJsonCount(2, 'data.history');
        $this->postJson("/api/service-orders/{$id}/diagnosis", ['diagnosis' => 'Diagnóstico atualizado'])->assertOk()->assertJsonCount(2, 'data.history');
        $this->putJson("/api/service-orders/{$id}/items", ['items' => [['kind' => 'SUPPLY', 'catalog_id' => $this->supply, 'quantity' => '1.5']]])
            ->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.items_total_cents', 152);
        $this->putJson("/api/service-orders/{$id}/items", ['items' => []])->assertOk()->assertJsonPath('data.items_total_cents', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_unknown_customer_vehicle_and_wrong_owner_do_not_create_an_order(): void
    {
        $this->postJson('/api/service-orders', array_replace($this->opening, ['customer_document' => '11222333000181']))->assertNotFound();
        $this->postJson('/api/customers', ['name' => 'Outro', 'document' => '11222333000181'])->assertCreated();
        $this->postJson('/api/service-orders', array_replace($this->opening, ['customer_document' => '11222333000181']))->assertConflict();
        $this->postJson('/api/service-orders', array_replace($this->opening, ['vehicle_plate' => 'BRA1A23']))->assertNotFound();
        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_invalid_items_roll_back_opening_and_preserve_previous_items_on_replacement(): void
    {
        $bad = [['kind' => 'PART', 'catalog_id' => $this->supply, 'quantity' => '1']];
        $this->postJson('/api/service-orders', array_replace($this->opening, ['items' => $bad]))->assertNotFound();
        $this->assertDatabaseCount('service_orders', 0);
        $this->assertDatabaseCount('order_status_history', 0);
        $id = $this->postJson('/api/service-orders', $this->opening)->assertCreated()->json('data.id');
        $this->putJson("/api/service-orders/{$id}/items", ['items' => $bad])->assertNotFound();
        $this->getJson("/api/service-orders/{$id}")->assertJsonPath('data.items_total_cents', 5051)->assertJsonCount(2, 'data.items');
        $this->patchJson('/api/supplies/'.$this->supply, ['active' => false])->assertOk();
        $this->postJson('/api/service-orders', $this->opening)->assertConflict();
        $this->assertDatabaseCount('service_orders', 1);
    }

    public function test_validation_prohibits_status_changes_fractional_services_and_injected_prices(): void
    {
        $this->postJson('/api/service-orders', [])->assertUnprocessable();
        foreach ([['status' => 'Entregue'], ['customer_document' => '11111111111'], ['vehicle_plate' => 'bad']] as $bad) {
            $this->postJson('/api/service-orders', array_replace($this->opening, $bad))->assertUnprocessable();
        }
        foreach ([['kind' => 'SERVICE', 'catalog_id' => $this->service, 'quantity' => '0.5'], ['kind' => 'SERVICE', 'catalog_id' => $this->service, 'quantity' => 1, 'unit_price_cents' => 1]] as $bad) {
            $this->postJson('/api/service-orders', array_replace($this->opening, ['items' => [$bad]]))->assertUnprocessable();
        }
        $withoutItems = $this->opening;
        unset($withoutItems['items']);
        $id = $this->postJson('/api/service-orders', $withoutItems)->assertCreated()->assertJsonPath('data.items_total_cents', 0)->json('data.id');
        $this->putJson("/api/service-orders/{$id}/items", [])->assertUnprocessable();
        $this->postJson("/api/service-orders/{$id}/diagnosis", [])->assertUnprocessable();
    }

    public function test_noneditable_orders_and_unauthenticated_requests_are_denied(): void
    {
        $id = $this->postJson('/api/service-orders', $this->opening)->assertCreated()->json('data.id');
        $order = ServiceOrder::findOrFail($id);
        $order->status = OrderStatus::WAITING;
        $order->save();
        $this->putJson("/api/service-orders/{$id}/items", ['items' => []])->assertConflict();
        $this->postJson("/api/service-orders/{$id}/diagnosis", ['diagnosis' => 'Teste'])->assertConflict();
        $this->getJson('/api/service-orders/999999')->assertNotFound();
        $this->getJson('/api/service-orders?per_page=101')->assertUnprocessable();
        $this->flushHeaders();
        $this->getJson('/api/service-orders')->assertUnauthorized();
        $this->postJson('/api/service-orders', $this->opening)->assertUnauthorized();
        $this->getJson("/api/service-orders/{$id}")->assertUnauthorized();
        $this->postJson("/api/service-orders/{$id}/diagnosis", [])->assertUnauthorized();
        $this->putJson("/api/service-orders/{$id}/items", [])->assertUnauthorized();
    }
}
