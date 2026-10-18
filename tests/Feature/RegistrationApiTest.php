<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Customer;
use App\Infrastructure\Security\AdminToken;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationApiTest extends TestCase
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

    private function customerData(): array
    {
        return ['name' => 'Cliente de teste', 'document' => '529.982.247-25', 'email' => 'cliente@example.test', 'phone' => '11999990000'];
    }

    private function createCustomer(): int
    {
        return $this->postJson('/api/customers', $this->customerData())->assertCreated()->json('data.id');
    }

    public function test_customer_crud_and_normalized_document(): void
    {
        $id = $this->createCustomer();
        $this->getJson("/api/customers/{$id}")->assertOk()
            ->assertJsonPath('data.document', '52998224725')->assertJsonPath('data.document_type', 'CPF');
        $this->getJson('/api/customers?per_page=1')->assertOk()->assertJsonPath('meta.total', 1);
        $this->putJson("/api/customers/{$id}", array_replace($this->customerData(), ['name' => 'Nome atualizado']))
            ->assertOk()->assertJsonPath('data.name', 'Nome atualizado');
        $this->patchJson("/api/customers/{$id}", ['phone' => null])->assertOk()->assertJsonPath('data.phone', null);
        $this->deleteJson("/api/customers/{$id}")->assertNoContent();
        $this->getJson("/api/customers/{$id}")->assertNotFound();
    }

    public function test_cnpj_and_document_update(): void
    {
        $id = $this->createCustomer();
        $this->patchJson("/api/customers/{$id}", ['document' => '11.222.333/0001-81'])
            ->assertOk()->assertJsonPath('data.document_type', 'CNPJ')->assertJsonPath('data.document', '11222333000181');
        $this->patchJson("/api/customers/{$id}", ['document' => '12.ABC.345/01DE-35'])
            ->assertOk()->assertJsonPath('data.document', '12ABC34501DE35');
    }

    public function test_invalid_and_duplicate_documents_do_not_persist(): void
    {
        $this->createCustomer();
        $this->postJson('/api/customers', $this->customerData())->assertUnprocessable()->assertJsonValidationErrors('document');
        foreach (['11111111111', '52998224724', ['bad'], null] as $document) {
            $this->postJson('/api/customers', array_replace($this->customerData(), ['document' => $document]))
                ->assertUnprocessable()->assertJsonValidationErrors('document');
        }
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_customer_update_keeps_uniqueness_and_required_fields(): void
    {
        $id = $this->createCustomer();
        $other = Customer::create(['name' => 'Empresa', 'document' => '11222333000181', 'document_type' => 'CNPJ']);
        $this->patchJson("/api/customers/{$id}", ['document' => $other->document])->assertUnprocessable();
        $this->putJson("/api/customers/{$id}", ['name' => 'Incompleto'])->assertUnprocessable();
        $this->postJson('/api/customers', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'document']);
    }

    public function test_vehicle_crud_plate_and_customer_relationship(): void
    {
        $customerId = $this->createCustomer();
        $data = ['customer_id' => $customerId, 'plate' => 'abc-1234', 'brand' => 'Fiat', 'model' => 'Uno', 'year' => 2014];
        $id = $this->postJson('/api/vehicles', $data)->assertCreated()->assertJsonPath('data.plate', 'ABC1234')->json('data.id');
        $this->getJson("/api/vehicles/{$id}")->assertOk()->assertJsonPath('data.customer_id', $customerId);
        $this->getJson('/api/vehicles')->assertOk()->assertJsonPath('meta.total', 1);
        $this->putJson("/api/vehicles/{$id}", array_replace($data, ['plate' => 'bra1a23']))
            ->assertOk()->assertJsonPath('data.plate', 'BRA1A23');
        $this->patchJson("/api/vehicles/{$id}", ['model' => 'Uno Way'])->assertOk()->assertJsonPath('data.model', 'Uno Way');
        $this->deleteJson("/api/customers/{$customerId}")->assertConflict();
        $this->assertDatabaseHas('customers', ['id' => $customerId]);
        $this->deleteJson("/api/vehicles/{$id}")->assertNoContent();
        $this->deleteJson("/api/customers/{$customerId}")->assertNoContent();
    }

    public function test_vehicle_rejects_duplicate_plate_bad_data_and_unknown_customer(): void
    {
        $customerId = $this->createCustomer();
        $data = ['customer_id' => $customerId, 'plate' => 'BRA1A23', 'brand' => 'Honda', 'model' => 'Civic', 'year' => 2012];
        $id = $this->postJson('/api/vehicles', $data)->assertCreated()->json('data.id');
        $this->postJson('/api/vehicles', array_replace($data, ['plate' => 'bra-1a23']))
            ->assertUnprocessable()->assertJsonValidationErrors('plate');
        $this->patchJson("/api/vehicles/{$id}", ['plate' => 'bra1a23'])->assertOk();
        foreach ([['plate' => ['bad']], ['plate' => 'AB12345'], ['year' => 1800], ['customer_id' => 999999]] as $invalid) {
            $this->postJson('/api/vehicles', array_replace($data, $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_service_crud_keeps_money_in_integer_cents(): void
    {
        $data = ['name' => 'Troca de óleo', 'description' => 'Mão de obra', 'price_cents' => 6000];
        $id = $this->postJson('/api/services', $data)->assertCreated()->assertJsonPath('data.price_cents', 6000)->json('data.id');
        $this->getJson("/api/services/{$id}")->assertOk();
        $this->getJson('/api/services')->assertOk()->assertJsonPath('meta.total', 1);
        $this->putJson("/api/services/{$id}", array_replace($data, ['price_cents' => 6500]))
            ->assertOk()->assertJsonPath('data.price_cents', 6500);
        $this->patchJson("/api/services/{$id}", ['description' => null])->assertOk()->assertJsonPath('data.description', null);
        $this->deleteJson("/api/services/{$id}")->assertNoContent();
        $this->getJson("/api/services/{$id}")->assertNotFound();
    }

    public function test_invalid_prices_are_rejected_and_zero_is_supported(): void
    {
        foreach ([-1, 12.5, 2147483648, 'abc', null] as $price) {
            $this->postJson('/api/services', ['name' => 'Serviço', 'price_cents' => $price])
                ->assertUnprocessable()->assertJsonValidationErrors('price_cents');
        }
        $this->postJson('/api/services', ['name' => 'Avaliação gratuita', 'price_cents' => 0])->assertCreated();
    }

    public function test_pagination_missing_records_and_http_error_format(): void
    {
        foreach (['customers', 'vehicles', 'services'] as $resource) {
            $this->getJson("/api/{$resource}?per_page=101")->assertUnprocessable();
            $this->getJson("/api/{$resource}?page=0")->assertUnprocessable();
            $this->getJson("/api/{$resource}/999999")->assertNotFound();
            $this->deleteJson("/api/{$resource}/999999")->assertNotFound();
            $this->get("/api/{$resource}/999999")->assertHeader('Content-Type', 'application/json');
        }
        $this->get('/')->assertNotFound();
    }

    public function test_all_administrative_resources_require_authentication(): void
    {
        $this->flushHeaders();
        foreach (['customers', 'vehicles', 'services'] as $resource) {
            $this->getJson("/api/{$resource}")->assertUnauthorized();
            $this->postJson("/api/{$resource}", [])->assertUnauthorized();
            $this->patchJson("/api/{$resource}/1", [])->assertUnauthorized();
            $this->deleteJson("/api/{$resource}/1")->assertUnauthorized();
        }
    }

    public function test_expired_forged_malformed_and_wrong_audience_tokens_are_denied(): void
    {
        $now = time();
        $payload = ['iss' => config('app.url'), 'aud' => 'oficina-admin', 'sub' => (string) $this->admin->id,
            'iat' => $now - 120, 'nbf' => $now - 120, 'exp' => $now + 3600];
        $tokens = [
            JWT::encode(array_replace($payload, ['exp' => $now - 1]), config('jwt.secret'), 'HS256'),
            JWT::encode($payload, str_repeat('x', 64), 'HS256'),
            JWT::encode(array_replace($payload, ['aud' => 'outro']), config('jwt.secret'), 'HS256'),
            JWT::encode(array_diff_key($payload, ['exp' => true]), config('jwt.secret'), 'HS256'),
            'not-a-token',
            'eyJhbGciOiJIUzI1NiJ9.bm90LWpzb24.signature',
        ];
        foreach ($tokens as $token) {
            $this->withToken($token)->getJson('/api/customers')->assertUnauthorized();
        }
    }

    public function test_admin_permission_is_checked_again_on_every_request(): void
    {
        $this->admin->forceFill(['is_admin' => false])->save();
        $this->getJson('/api/customers')->assertForbidden();
        $this->admin->delete();
        $this->getJson('/api/customers')->assertUnauthorized();
    }
}
