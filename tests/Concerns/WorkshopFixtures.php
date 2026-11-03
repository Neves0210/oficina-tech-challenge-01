<?php

namespace Tests\Concerns;

use App\Infrastructure\Persistence\Customer;
use App\Infrastructure\Persistence\Material;
use App\Infrastructure\Persistence\Vehicle;
use App\Infrastructure\Persistence\WorkshopService;
use App\Infrastructure\Security\AdminToken;
use App\Models\User;

trait WorkshopFixtures
{
    protected User $workshopAdmin;

    protected int $fixtureSupply;

    protected int $fixtureService;

    protected string $fixtureCustomerToken;

    protected function authenticateWorkshop(): void
    {
        config(['jwt.secret' => str_repeat('s', 64)]);
        $this->workshopAdmin = User::factory()->create(['is_admin' => true]);
        $this->useWorkshopAdmin();
    }

    protected function useWorkshopAdmin(): void
    {
        $this->withToken(app(AdminToken::class)->issue($this->workshopAdmin->id));
    }

    protected function approveWorkshopBudget(int $order): int
    {
        $this->useWorkshopAdmin();
        $budget = $this->presentWorkshopBudget($order);
        $this->fixtureCustomerToken = $this->postJson("/api/service-orders/{$order}/customer-access")->assertCreated()->json('data.access_token');
        $this->withToken($this->fixtureCustomerToken)->postJson("/api/customer/service-orders/{$order}/budgets/{$budget}/decision", ['decision' => 'APPROVED'])->assertOk();
        $this->useWorkshopAdmin();

        return $budget;
    }

    protected function createWorkshopOrder(bool $diagnose = true): int
    {
        $customer = Customer::firstOrCreate(['document' => '52998224725'], ['name' => 'Cliente', 'document_type' => 'CPF']);
        Vehicle::firstOrCreate(['plate' => 'ABC1234'], ['customer_id' => $customer->id, 'brand' => 'Fiat', 'model' => 'Uno', 'year' => 2014]);
        $service = WorkshopService::firstOrCreate(['name' => 'Troca'], ['price_cents' => 5000]);
        $supply = Material::where('type', 'SUPPLY')->first();
        if (! $supply) {
            $this->fixtureSupply = $this->postJson('/api/supplies', ['name' => 'Óleo', 'unit' => 'L', 'price_cents' => 101])->assertCreated()->json('data.id');
        } else {
            $this->fixtureSupply = $supply->id;
        }
        $this->fixtureService = $service->id;
        $id = $this->postJson('/api/service-orders', [
            'customer_document' => '52998224725', 'vehicle_plate' => 'ABC1234', 'complaint' => 'Troca de óleo',
            'items' => [
                ['kind' => 'SERVICE', 'catalog_id' => $service->id, 'quantity' => 1],
                ['kind' => 'SUPPLY', 'catalog_id' => $this->fixtureSupply, 'quantity' => '0.5'],
            ],
        ])->assertCreated()->json('data.id');
        if ($diagnose) {
            $this->postJson("/api/service-orders/{$id}/diagnosis", ['diagnosis' => 'Troca confirmada'])->assertOk();
        }

        return $id;
    }

    protected function presentWorkshopBudget(int $order): int
    {
        return $this->postJson("/api/service-orders/{$order}/budgets")->assertCreated()->json('data.id');
    }
}
