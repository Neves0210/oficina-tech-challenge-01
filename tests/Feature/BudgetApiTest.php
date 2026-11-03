<?php

namespace Tests\Feature;

use App\Domain\Orders\OrderStatus;
use App\Infrastructure\Persistence\OrderBudgetItem;
use App\Infrastructure\Persistence\ServiceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\WorkshopFixtures;
use Tests\TestCase;

class BudgetApiTest extends TestCase
{
    use RefreshDatabase, WorkshopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateWorkshop();
    }

    public function test_presented_budget_copies_items_and_computes_total_on_the_server(): void
    {
        $order = $this->createWorkshopOrder();
        $response = $this->postJson("/api/service-orders/{$order}/budgets", ['total_cents' => 1, 'decision' => 'APPROVED'])
            ->assertCreated()->assertJsonPath('data.total_cents', 5051)->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.decision', 'PENDING')->assertJsonPath('data.decided_at', null)->assertJsonCount(2, 'data.items');
        $budget = $response->json('data.id');
        $this->getJson("/api/service-orders/{$order}")->assertOk()->assertJsonPath('data.status', 'Aguardando aprovação')
            ->assertJsonCount(3, 'data.history')->assertJsonPath('data.budgets.0.id', $budget);
        $this->patchJson('/api/services/'.$this->fixtureService, ['price_cents' => 9999])->assertOk();
        $this->getJson("/api/service-orders/{$order}/budgets/{$budget}")->assertOk()->assertJsonPath('data.total_cents', 5051)
            ->assertJsonPath('data.items.0.unit_price_cents', 5000);
        $this->getJson("/api/service-orders/{$order}/budgets?per_page=1")->assertOk()->assertJsonPath('meta.total', 1);
        $this->postJson("/api/service-orders/{$order}/budgets")->assertConflict();
        $this->putJson("/api/service-orders/{$order}/items", ['items' => []])->assertConflict();
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_revision_creates_a_new_version_without_changing_the_old_proposal(): void
    {
        $order = $this->createWorkshopOrder();
        $first = $this->presentWorkshopBudget($order);
        $this->postJson("/api/service-orders/{$order}/revise-budget")->assertOk()->assertJsonPath('data.status', 'Em diagnóstico');
        $this->putJson("/api/service-orders/{$order}/items", ['items' => [['kind' => 'SERVICE', 'catalog_id' => $this->fixtureService, 'quantity' => 2]]])->assertOk();
        $this->postJson("/api/service-orders/{$order}/budgets")->assertCreated()->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.total_cents', 10000)->assertJsonPath('data.decision', 'PENDING');
        $this->getJson("/api/service-orders/{$order}/budgets/{$first}")->assertOk()->assertJsonPath('data.total_cents', 5051)->assertJsonCount(2, 'data.items');
        $this->getJson("/api/service-orders/{$order}/budgets")->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.version', 2);
        // A referência da versão histórica também preserva o catálogo removido dos itens atuais.
        $this->deleteJson('/api/supplies/'.$this->fixtureSupply)->assertConflict();
    }

    public function test_diagnosis_items_and_current_status_are_required(): void
    {
        $order = $this->createWorkshopOrder(false);
        $this->postJson("/api/service-orders/{$order}/budgets")->assertConflict();
        $this->postJson("/api/service-orders/{$order}/revise-budget")->assertConflict();
        $this->postJson("/api/service-orders/{$order}/diagnosis", ['diagnosis' => 'Teste'])->assertOk();
        $this->putJson("/api/service-orders/{$order}/items", ['items' => []])->assertOk();
        $this->postJson("/api/service-orders/{$order}/budgets")->assertConflict();
        $row = ServiceOrder::findOrFail($order);
        $row->status = OrderStatus::EXECUTING;
        $row->save();
        $this->postJson("/api/service-orders/{$order}/revise-budget")->assertConflict();
        $this->assertDatabaseCount('order_budgets', 0);
    }

    public function test_snapshot_failure_rolls_back_budget_status_and_history(): void
    {
        $order = $this->createWorkshopOrder();
        OrderBudgetItem::creating(fn () => throw new RuntimeException('Falha simulada no snapshot'));
        $this->withoutExceptionHandling();
        try {
            $this->postJson("/api/service-orders/{$order}/budgets");
            $this->fail('O snapshot incompleto deveria impedir a apresentação.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada no snapshot', $exception->getMessage());
        } finally {
            OrderBudgetItem::flushEventListeners();
        }
        $this->assertDatabaseCount('order_budgets', 0);
        $this->assertDatabaseCount('order_budget_items', 0);
        $this->assertDatabaseCount('order_status_history', 2);
        $this->assertSame(OrderStatus::DIAGNOSIS, ServiceOrder::findOrFail($order)->status);
    }

    public function test_budget_queries_are_scoped_and_administrative(): void
    {
        $order = $this->createWorkshopOrder();
        $budget = $this->presentWorkshopBudget($order);
        $other = $this->createWorkshopOrder();
        $this->getJson("/api/service-orders/{$other}/budgets/{$budget}")->assertNotFound();
        $this->getJson('/api/service-orders/999999/budgets')->assertNotFound();
        $this->getJson("/api/service-orders/{$order}/budgets?per_page=101")->assertUnprocessable();
        $this->flushHeaders();
        $this->getJson("/api/service-orders/{$order}/budgets")->assertUnauthorized();
        $this->getJson("/api/service-orders/{$order}/budgets/{$budget}")->assertUnauthorized();
        $this->postJson("/api/service-orders/{$order}/budgets")->assertUnauthorized();
        $this->postJson("/api/service-orders/{$order}/revise-budget")->assertUnauthorized();
    }
}
