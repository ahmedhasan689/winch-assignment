<?php

declare(strict_types=1);

namespace Tests\Feature;

use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DriverOrdersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_driver_orders_with_filter_and_pagination(): void
    {
        $driver = Driver::factory()->busy()->create();

        // Create mixed orders for this driver
        Order::factory()->assignedTo($driver)->count(20)->create();
        Order::factory()->count(5)->create([
            'driver_id' => $driver->id,
            'status' => OrderStatus::COMPLETED->value,
            'assigned_at' => now()->subHours(3),
        ]);

        // Filter: status=assigned, per_page=10
        $response = $this->getJson(
            "/api/drivers/{$driver->id}/orders?status=assigned&per_page=10",
        );

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.last_page', 2);

        // Every returned order should have status=assigned
        foreach ($response->json('data') as $order) {
            $this->assertSame(OrderStatus::ASSIGNED->value, $order['status']);
        }
    }
}
