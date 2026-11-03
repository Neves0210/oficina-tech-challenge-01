<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Material;
use App\Infrastructure\Persistence\StockMovement;
use App\Infrastructure\Security\AdminToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class MaterialApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['jwt.secret' => str_repeat('s', 64)]);
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->withToken(app(AdminToken::class)->issue($this->admin->id));
    }

    public static function resourceTypes(): array
    {
        return [['parts', 'PART', 'unit'], ['supplies', 'SUPPLY', 'L']];
    }

    private function createMaterial(string $resource = 'supplies'): int
    {
        return $this->postJson('/api/'.$resource, [
            'name' => $resource === 'parts' ? 'Filtro' : 'Óleo', 'price_cents' => 2500,
            ...($resource === 'supplies' ? ['unit' => 'L'] : []),
        ])->assertCreated()->json('data.id');
    }

    private function move(int $id, string|int $quantity, string $direction = 'IN', string $resource = 'supplies')
    {
        return $this->postJson("/api/{$resource}/{$id}/stock-movements", [
            'direction' => $direction, 'quantity' => $quantity, 'reason' => 'Movimentação de teste',
        ]);
    }

    #[DataProvider('resourceTypes')]
    public function test_material_crud_and_initial_balance(string $resource, string $type, string $unit): void
    {
        $id = $this->createMaterial($resource);
        $this->getJson("/api/{$resource}/{$id}")->assertOk()->assertJsonPath('data.type', $type)
            ->assertJsonPath('data.unit', $unit)->assertJsonPath('data.stock_quantity', '0.000')->assertJsonPath('data.active', true);
        $this->getJson("/api/{$resource}?per_page=1")->assertOk()->assertJsonPath('meta.total', 1);
        $this->putJson("/api/{$resource}/{$id}", ['name' => 'Atualizado', 'unit' => $unit, 'price_cents' => 0])
            ->assertOk()->assertJsonPath('data.price_cents', 0);
        $this->patchJson("/api/{$resource}/{$id}", ['description' => null, 'active' => false])
            ->assertOk()->assertJsonPath('data.active', false);
        $this->deleteJson("/api/{$resource}/{$id}")->assertNoContent();
        $this->getJson("/api/{$resource}/{$id}")->assertNotFound();
    }

    public function test_type_scopes_all_queries_and_mutations(): void
    {
        $id = $this->createMaterial('supplies');
        $this->getJson('/api/parts')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson("/api/parts/{$id}")->assertNotFound();
        $this->patchJson("/api/parts/{$id}", ['name' => 'Não alterar'])->assertNotFound();
        $this->deleteJson("/api/parts/{$id}")->assertNotFound();
        $this->move($id, 1, 'IN', 'parts')->assertNotFound();
        $this->getJson("/api/parts/{$id}/stock-movements")->assertNotFound();
        $this->assertDatabaseCount('materials', 1);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_material_validation_rejects_direct_stock_and_type_changes(): void
    {
        $this->postJson('/api/supplies', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'unit', 'price_cents']);
        $id = $this->createMaterial();
        foreach ([['stock_quantity' => '10'], ['type' => 'PART'], ['price_cents' => -1], ['price_cents' => 2147483648], ['unit' => null], ['active' => null]] as $invalid) {
            $this->patchJson("/api/supplies/{$id}", $invalid)->assertUnprocessable();
        }
        $this->putJson("/api/supplies/{$id}", ['name' => 'Incompleto'])->assertUnprocessable();
        $this->postJson('/api/parts', ['name' => 'Filtro', 'price_cents' => 100, 'unit' => 'L'])->assertUnprocessable();
        $this->assertDatabaseHas('materials', ['id' => $id, 'type' => 'SUPPLY', 'stock_quantity' => 0]);
    }

    public function test_balance_and_ledger_keep_exact_fractional_values_and_author(): void
    {
        $id = $this->createMaterial();
        $this->move($id, '0.1')->assertCreated()->assertJsonPath('data.material.stock_quantity', '0.100');
        $this->move($id, '0.2')->assertCreated()->assertJsonPath('data.material.stock_quantity', '0.300')
            ->assertJsonPath('data.movement.user_id', $this->admin->id)->assertJsonPath('data.movement.quantity', '0.200');
        $this->move($id, '0.3', 'OUT')->assertCreated()->assertJsonPath('data.material.stock_quantity', '0.000');
        $this->getJson("/api/supplies/{$id}/stock-movements?per_page=2&page=2")
            ->assertOk()->assertJsonPath('meta.total', 3)->assertJsonPath('data.0.direction', 'OUT');
        $other = $this->createMaterial();
        $this->getJson("/api/supplies/{$other}/stock-movements")->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_part_quantities_are_whole_and_zero_or_imprecise_movements_are_rejected(): void
    {
        $id = $this->createMaterial('parts');
        $this->move($id, 2, 'IN', 'parts')->assertCreated()->assertJsonPath('data.material.stock_quantity', '2.000');
        foreach (['0', '0.5', '-1', '1.0001', '1e3', '1000000000'] as $quantity) {
            $this->move($id, $quantity, 'IN', 'parts')->assertUnprocessable()->assertJsonValidationErrors('quantity');
        }
        $this->postJson("/api/parts/{$id}/stock-movements", ['direction' => 'IN', 'quantity' => 0.5, 'reason' => 'Teste'])
            ->assertUnprocessable();
        $this->postJson("/api/parts/{$id}/stock-movements", ['direction' => 'INVALID', 'quantity' => ['bad']])
            ->assertUnprocessable()->assertJsonValidationErrors(['direction', 'quantity', 'reason']);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_insufficient_stock_and_overflow_do_not_create_movements(): void
    {
        $id = $this->createMaterial();
        $this->move($id, '0.001', 'OUT')->assertConflict();
        $this->assertDatabaseCount('stock_movements', 0);
        $this->move($id, '999999999.999')->assertCreated();
        $this->move($id, '0.001')->assertConflict();
        $this->assertDatabaseCount('stock_movements', 1);
        $this->getJson("/api/supplies/{$id}")->assertOk()->assertJsonPath('data.stock_quantity', '999999999.999');
    }

    public function test_ledger_failure_rolls_back_the_balance(): void
    {
        $id = $this->createMaterial();
        StockMovement::creating(fn () => throw new RuntimeException('Falha simulada no histórico'));
        $this->withoutExceptionHandling();
        try {
            $this->move($id, '1');
            $this->fail('A falha do histórico deveria impedir a operação.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada no histórico', $exception->getMessage());
        } finally {
            StockMovement::flushEventListeners();
        }
        $this->assertSame('0.000', Material::findOrFail($id)->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_unit_and_history_cannot_be_rewritten_after_a_movement(): void
    {
        $id = $this->createMaterial();
        $this->patchJson("/api/supplies/{$id}", ['unit' => 'kg'])->assertOk();
        $this->move($id, '2')->assertCreated();
        $this->patchJson("/api/supplies/{$id}", ['unit' => 'L'])->assertConflict();
        $this->patchJson("/api/supplies/{$id}", ['unit' => 'kg', 'price_cents' => 3000, 'active' => false])->assertOk();
        $this->deleteJson("/api/supplies/{$id}")->assertConflict();
        $this->getJson("/api/supplies/{$id}")->assertOk()->assertJsonPath('data.unit', 'kg')->assertJsonPath('data.stock_quantity', '2.000');
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_administrative_access_pagination_and_missing_records(): void
    {
        foreach (['parts', 'supplies'] as $resource) {
            $this->getJson("/api/{$resource}?per_page=101")->assertUnprocessable();
            $this->getJson("/api/{$resource}/999999/stock-movements")->assertNotFound();
            $this->flushHeaders();
            $this->getJson("/api/{$resource}")->assertUnauthorized();
            $this->postJson("/api/{$resource}", [])->assertUnauthorized();
            $this->patchJson("/api/{$resource}/1", [])->assertUnauthorized();
            $this->deleteJson("/api/{$resource}/1")->assertUnauthorized();
            $this->postJson("/api/{$resource}/1/stock-movements", [])->assertUnauthorized();
            $this->getJson("/api/{$resource}/1/stock-movements")->assertUnauthorized();
            $this->withToken(app(AdminToken::class)->issue($this->admin->id));
        }
        $this->admin->forceFill(['is_admin' => false])->save();
        $this->getJson('/api/parts')->assertForbidden();
    }
}
