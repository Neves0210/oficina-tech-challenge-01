<?php

namespace Tests\Feature;

use App\Domain\Orders\BudgetDecision;
use App\Domain\Orders\CustomerAccessDenied;
use App\Domain\Orders\CustomerAccessRepository;
use App\Infrastructure\Persistence\OrderBudget;
use App\Infrastructure\Persistence\OrderCustomerAccess;
use App\Infrastructure\Security\AdminToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WorkshopFixtures;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase, WorkshopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateWorkshop();
    }

    private function admin(): void
    {
        $this->withToken(app(AdminToken::class)->issue($this->workshopAdmin->id));
    }

    private function access(int $order): string
    {
        $this->admin();

        return $this->postJson("/api/service-orders/{$order}/customer-access")->assertCreated()->assertJsonPath('data.expires_in', 86400)->json('data.access_token');
    }

    public function test_customer_credential_is_hashed_and_only_exposes_its_own_order(): void
    {
        $order = $this->createWorkshopOrder();
        $budget = $this->presentWorkshopBudget($order);
        $other = $this->createWorkshopOrder();
        $token = $this->access($order);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertDatabaseHas('order_customer_access', ['service_order_id' => $order, 'token_hash' => hash('sha256', $token)]);
        $this->assertNotSame($token, OrderCustomerAccess::firstOrFail()->token_hash);
        $this->withToken($token)->getJson("/api/customer/service-orders/{$order}")->assertOk()
            ->assertJsonPath('data.status', 'Aguardando aprovação')->assertJsonPath('data.budgets.0.id', $budget)
            ->assertJsonMissingPath('data.customer_id')->assertJsonMissingPath('data.history.0.user_id')->assertJsonMissingPath('data.budgets.0.user_id');
        $this->getJson("/api/customer/service-orders/{$order}/budgets/{$budget}")->assertOk()->assertJsonPath('data.total_cents', 5051);
        $this->getJson("/api/customer/service-orders/{$other}")->assertUnauthorized();
        $this->getJson('/api/customers')->assertUnauthorized();
    }

    public function test_rotation_and_revocation_invalidate_previous_credentials(): void
    {
        $order = $this->createWorkshopOrder();
        $old = $this->access($order);
        $new = $this->access($order);
        $this->assertNotSame($old, $new);
        $this->assertDatabaseCount('order_customer_access', 1);
        $this->withToken($old)->getJson("/api/customer/service-orders/{$order}")->assertUnauthorized();
        $this->withToken($new)->getJson("/api/customer/service-orders/{$order}")->assertOk();
        $this->admin();
        $this->deleteJson("/api/service-orders/{$order}/customer-access")->assertNoContent();
        $this->deleteJson("/api/service-orders/{$order}/customer-access")->assertNoContent();
        $this->withToken($new)->getJson("/api/customer/service-orders/{$order}")->assertUnauthorized();
    }

    public function test_expired_missing_and_malformed_tokens_return_the_same_denial(): void
    {
        $order = $this->createWorkshopOrder();
        $token = $this->access($order);
        $this->flushHeaders();
        $denial = $this->getJson("/api/customer/service-orders/{$order}")->assertUnauthorized()->json();
        foreach (['invalid', str_repeat('0', 64)] as $bad) {
            $this->withToken($bad)->getJson("/api/customer/service-orders/{$order}")->assertUnauthorized()->assertExactJson($denial);
        }
        $this->withToken($token)->getJson('/api/customer/service-orders/999999')->assertUnauthorized()->assertExactJson($denial);
        $this->travel(25)->hours();
        $this->withToken($token)->getJson("/api/customer/service-orders/{$order}")->assertUnauthorized()->assertExactJson($denial);
    }

    public function test_approval_is_explicit_immutable_and_does_not_start_execution(): void
    {
        $order = $this->createWorkshopOrder();
        $budget = $this->presentWorkshopBudget($order);
        $token = $this->access($order);
        $this->withToken($token)->postJson("/api/customer/service-orders/{$order}/budgets/{$budget}/decision", ['decision' => 'APPROVED'])
            ->assertOk()->assertJsonPath('data.decision', 'APPROVED')->assertJsonPath('data.total_cents', 5051);
        $decided = OrderBudget::findOrFail($budget)->decided_at;
        $this->assertNotNull($decided);
        $this->postJson("/api/customer/service-orders/{$order}/budgets/{$budget}/decision", ['decision' => 'REJECTED'])->assertConflict();
        $this->postJson("/api/customer/service-orders/{$order}/budgets/{$budget}/decision", ['decision' => 'APPROVED'])->assertConflict();
        $this->assertEquals($decided, OrderBudget::findOrFail($budget)->decided_at);
        $this->getJson("/api/customer/service-orders/{$order}")->assertJsonPath('data.status', 'Aguardando aprovação');
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_rejection_and_revision_require_a_decision_on_the_new_version(): void
    {
        $order = $this->createWorkshopOrder();
        $first = $this->presentWorkshopBudget($order);
        $token = $this->access($order);
        $this->withToken($token)->postJson("/api/customer/service-orders/{$order}/budgets/{$first}/decision", ['decision' => 'REJECTED'])->assertOk();
        $this->getJson("/api/customer/service-orders/{$order}")->assertJsonPath('data.status', 'Aguardando aprovação');
        $this->admin();
        $this->postJson("/api/service-orders/{$order}/revise-budget")->assertOk();
        $this->withToken($token)->postJson("/api/customer/service-orders/{$order}/budgets/{$first}/decision", ['decision' => 'APPROVED'])->assertConflict();
        $this->admin();
        $second = $this->presentWorkshopBudget($order);
        $this->withToken($token)->postJson("/api/customer/service-orders/{$order}/budgets/{$first}/decision", ['decision' => 'APPROVED'])->assertConflict();
        $this->postJson("/api/customer/service-orders/{$order}/budgets/{$second}/decision", ['decision' => 'APPROVED'])->assertOk();
        $this->assertDatabaseHas('order_budgets', ['id' => $first, 'decision' => 'REJECTED']);
        $this->assertDatabaseHas('order_budgets', ['id' => $second, 'decision' => 'APPROVED']);
    }

    public function test_decisions_validate_scope_input_and_credential_type(): void
    {
        $order = $this->createWorkshopOrder();
        $budget = $this->presentWorkshopBudget($order);
        $other = $this->createWorkshopOrder();
        $otherBudget = $this->presentWorkshopBudget($other);
        $token = $this->access($order);
        $this->postJson("/api/customer/service-orders/{$order}/budgets/{$budget}/decision", ['decision' => 'APPROVED'])->assertUnauthorized();
        $this->withToken($token)->postJson("/api/customer/service-orders/{$order}/budgets/{$otherBudget}/decision", ['decision' => 'APPROVED'])->assertNotFound();
        $this->getJson("/api/customer/service-orders/{$order}/budgets/{$otherBudget}")->assertNotFound();
        foreach ([[], ['decision' => 'PENDING'], ['decision' => ['APPROVED']]] as $data) {
            $this->postJson("/api/customer/service-orders/{$order}/budgets/{$budget}/decision", $data)->assertUnprocessable();
        }
        $this->postJson("/api/service-orders/{$order}/customer-access")->assertUnauthorized();
        $this->admin();
        $this->postJson('/api/service-orders/999999/customer-access')->assertNotFound();
        $this->deleteJson('/api/service-orders/999999/customer-access')->assertNotFound();
    }

    public function test_decision_rechecks_credential_inside_the_transaction(): void
    {
        $order = $this->createWorkshopOrder();
        $budget = $this->presentWorkshopBudget($order);
        $old = $this->access($order);
        $this->access($order);
        $this->expectException(CustomerAccessDenied::class);
        app(CustomerAccessRepository::class)->decide($order, $budget, BudgetDecision::APPROVED, hash('sha256', $old));
    }
}
