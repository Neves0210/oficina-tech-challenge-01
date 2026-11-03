<?php

namespace Tests\Feature;

use App\Domain\Orders\OrderStatus;
use App\Infrastructure\Persistence\ServiceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WorkshopFixtures;
use Tests\TestCase;

class ExecutionTimeReportTest extends TestCase
{
    use RefreshDatabase, WorkshopFixtures;

    public function test_empty_report_and_authentication(): void
    {
        $this->getJson('/api/reports/service-execution-time')->assertUnauthorized();
        $this->authenticateWorkshop();
        $this->getJson('/api/reports/service-execution-time')->assertOk()->assertExactJson([
            'data' => ['orders_count' => 0, 'average_seconds' => null],
        ]);
        $order = $this->createWorkshopOrder();
        $token = $this->postJson("/api/service-orders/{$order}/customer-access")->assertCreated()->json('data.access_token');
        $this->withToken($token)->getJson('/api/reports/service-execution-time')->assertUnauthorized();
    }

    public function test_only_valid_completed_orders_count_once_and_delivery_wait_is_excluded(): void
    {
        $this->authenticateWorkshop();
        $start = now()->startOfSecond();
        foreach ([
            [OrderStatus::FINISHED, $start, $start->copy()->addSeconds(60)],
            [OrderStatus::DELIVERED, $start, $start->copy()->addSeconds(121)],
            [OrderStatus::FINISHED, $start, $start],
            [OrderStatus::EXECUTING, $start, null],
            [OrderStatus::RECEIVED, $start, $start->copy()->addHours(1)],
            [OrderStatus::FINISHED, null, $start],
            [OrderStatus::DELIVERED, $start, null],
            [OrderStatus::FINISHED, $start, $start->copy()->subSecond()],
        ] as [$status, $started, $finished]) {
            $order = ServiceOrder::findOrFail($this->createWorkshopOrder(false));
            $order->status = $status;
            $order->started_at = $started;
            $order->finished_at = $finished;
            $order->delivered_at = $status === OrderStatus::DELIVERED ? $start->copy()->addDays(2) : null;
            $order->save();
        }
        $this->getJson('/api/reports/service-execution-time')->assertOk()->assertExactJson([
            'data' => ['orders_count' => 3, 'average_seconds' => 60.33],
        ]);
    }
}
