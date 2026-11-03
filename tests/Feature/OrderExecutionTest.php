<?php

namespace Tests\Feature;

use App\Domain\Orders\OrderStatus;
use App\Infrastructure\Persistence\Material;
use App\Infrastructure\Persistence\OrderStatusHistory;
use App\Infrastructure\Persistence\ServiceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\WorkshopFixtures;
use Tests\TestCase;

class OrderExecutionTest extends TestCase
{
    use RefreshDatabase, WorkshopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateWorkshop();
    }

    private function addStock(string $quantity = '1'): void
    {
        $this->postJson('/api/supplies/'.$this->fixtureSupply.'/stock-movements', ['direction' => 'IN', 'quantity' => $quantity, 'reason' => 'Compra'])->assertCreated();
    }

    public function test_approved_order_consumes_once_and_follows_all_remaining_statuses(): void
    {
        $this->freezeTime();
        $order = $this->createWorkshopOrder();
        $this->approveWorkshopBudget($order);
        $this->addStock();
        $this->postJson("/api/service-orders/{$order}/start-execution")->assertOk()->assertJsonPath('data.status', 'Em execução');
        $started = ServiceOrder::findOrFail($order)->started_at;
        $this->assertNotNull($started);
        $this->assertDatabaseHas('stock_movements', ['service_order_id' => $order, 'material_id' => $this->fixtureSupply, 'direction' => 'OUT', 'quantity' => '0.5']);
        $this->postJson("/api/service-orders/{$order}/start-execution")->assertConflict();
        $this->assertSame('0.500', Material::findOrFail($this->fixtureSupply)->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->postJson("/api/service-orders/{$order}/deliver")->assertConflict();
        $this->travel(10)->minutes();
        $this->useWorkshopAdmin();
        $this->postJson("/api/service-orders/{$order}/finish")->assertOk()->assertJsonPath('data.status', 'Finalizada');
        $finished = ServiceOrder::findOrFail($order)->finished_at;
        $this->assertSame(600, $finished->getTimestamp() - $started->getTimestamp());
        $this->postJson("/api/service-orders/{$order}/finish")->assertConflict();
        $this->postJson("/api/service-orders/{$order}/deliver")->assertOk()->assertJsonPath('data.status', 'Entregue')->assertJsonCount(6, 'data.history');
        $this->postJson("/api/service-orders/{$order}/deliver")->assertConflict();
        $this->putJson("/api/service-orders/{$order}/items", ['items' => []])->assertConflict();
        $this->postJson("/api/service-orders/{$order}/revise-budget")->assertConflict();
        $this->withToken($this->fixtureCustomerToken)->getJson("/api/customer/service-orders/{$order}")->assertOk()->assertJsonPath('data.status', 'Entregue');
    }

    public function test_pending_or_rejected_or_obsolete_approval_cannot_start_execution(): void
    {
        $order = $this->createWorkshopOrder();
        $this->postJson("/api/service-orders/{$order}/start-execution")->assertConflict();
        $budget = $this->presentWorkshopBudget($order);
        $this->postJson("/api/service-orders/{$order}/start-execution")->assertConflict();
        $token = $this->postJson("/api/service-orders/{$order}/customer-access")->assertCreated()->json('data.access_token');
        $this->withToken($token)->postJson("/api/customer/service-orders/{$order}/budgets/{$budget}/decision", ['decision' => 'REJECTED'])->assertOk();
        $this->useWorkshopAdmin();
        $this->postJson("/api/service-orders/{$order}/start-execution")->assertConflict();
        $this->postJson("/api/service-orders/{$order}/revise-budget")->assertOk();
        $approved = $this->presentWorkshopBudget($order);
        $this->withToken($token)->postJson("/api/customer/service-orders/{$order}/budgets/{$approved}/decision", ['decision' => 'APPROVED'])->assertOk();
        $this->useWorkshopAdmin();
        $this->postJson("/api/service-orders/{$order}/revise-budget")->assertOk();
        $this->presentWorkshopBudget($order);
        $this->addStock();
        $this->postJson("/api/service-orders/{$order}/start-execution")->assertConflict();
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_all_materials_must_have_stock_and_duplicate_lines_are_aggregated(): void
    {
        $order = $this->createWorkshopOrder();
        $part = $this->postJson('/api/parts', ['name' => 'Filtro', 'price_cents' => 2000])->assertCreated()->json('data.id');
        $this->putJson("/api/service-orders/{$order}/items", ['items' => [
            ['kind' => 'SUPPLY', 'catalog_id' => $this->fixtureSupply, 'quantity' => '0.3'],
            ['kind' => 'SUPPLY', 'catalog_id' => $this->fixtureSupply, 'quantity' => '0.4'],
            ['kind' => 'PART', 'catalog_id' => $part, 'quantity' => '1'],
        ]])->assertOk();
        $this->approveWorkshopBudget($order);
        $this->addStock();
        $this->postJson("/api/service-orders/{$order}/start-execution")->assertConflict();
        $this->assertSame('1.000', Material::findOrFail($this->fixtureSupply)->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->postJson("/api/parts/{$part}/stock-movements", ['direction' => 'IN', 'quantity' => 1, 'reason' => 'Compra'])->assertCreated();
        $this->postJson("/api/service-orders/{$order}/start-execution")->assertOk();
        $this->assertDatabaseHas('stock_movements', ['service_order_id' => $order, 'material_id' => $this->fixtureSupply, 'quantity' => '0.7']);
        $this->assertSame('0.300', Material::findOrFail($this->fixtureSupply)->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 4);
    }

    public function test_failure_after_stock_writes_rolls_back_consumption_and_status(): void
    {
        $order = $this->createWorkshopOrder();
        $this->approveWorkshopBudget($order);
        $this->addStock();
        OrderStatusHistory::creating(fn () => throw new RuntimeException('Falha simulada após baixa'));
        $this->withoutExceptionHandling();
        try {
            $this->postJson("/api/service-orders/{$order}/start-execution");
            $this->fail('A falha de histórico deveria reverter a execução.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada após baixa', $exception->getMessage());
        } finally {
            OrderStatusHistory::flushEventListeners();
        }
        $row = ServiceOrder::findOrFail($order);
        $this->assertSame(OrderStatus::WAITING, $row->status);
        $this->assertNull($row->started_at);
        $this->assertSame('1.000', Material::findOrFail($this->fixtureSupply)->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('order_status_history', 3);
    }

    public function test_service_only_order_does_not_need_material_stock(): void
    {
        $order = $this->createWorkshopOrder();
        $this->putJson("/api/service-orders/{$order}/items", ['items' => [['kind' => 'SERVICE', 'catalog_id' => $this->fixtureService, 'quantity' => 1]]])->assertOk();
        $this->approveWorkshopBudget($order);
        $this->postJson("/api/service-orders/{$order}/start-execution")->assertOk();
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_inconsistent_timestamps_and_invalid_actions_are_rejected(): void
    {
        $order = $this->createWorkshopOrder();
        $this->postJson("/api/service-orders/{$order}/finish")->assertConflict();
        $this->postJson("/api/service-orders/{$order}/deliver")->assertConflict();
        $row = ServiceOrder::findOrFail($order);
        $row->status = OrderStatus::EXECUTING;
        $row->save();
        $this->postJson("/api/service-orders/{$order}/finish")->assertConflict();
        $row->started_at = now()->addDay();
        $row->save();
        $this->postJson("/api/service-orders/{$order}/finish")->assertConflict();
        $this->postJson('/api/service-orders/999999/start-execution')->assertNotFound();
        $this->postJson('/api/service-orders/999999/finish')->assertNotFound();
        $this->flushHeaders();
        foreach (['start-execution', 'finish', 'deliver'] as $action) {
            $this->postJson("/api/service-orders/{$order}/{$action}")->assertUnauthorized();
        }
    }
}
