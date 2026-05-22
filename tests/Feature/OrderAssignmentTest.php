<?php

declare(strict_types=1);

namespace Tests\Feature;

use Domain\Driver\Enums\DriverStatus;
use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OrderAssignmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_assigns_pending_order_to_nearest_available_driver(): void
    {
        // Far driver
        $farDriver = Driver::factory()->available()->create([
            'current_lat' => 25.0,
            'current_lng' => 47.0,
        ]);

        // Near driver (should be picked)
        $nearDriver = Driver::factory()->available()->create([
            'current_lat' => 24.71,
            'current_lng' => 46.68,
        ]);

        $order = Order::factory()->pending()->create([
            'pickup_lat' => 24.7136,
            'pickup_lng' => 46.6753,
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/assign");

        $response->assertOk()
            ->assertJsonPath('data.status', OrderStatus::ASSIGNED->value)
            ->assertJsonPath('data.driver.id', $nearDriver->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'driver_id' => $nearDriver->id,
            'status' => OrderStatus::ASSIGNED->value,
        ]);

        $this->assertDatabaseHas('drivers', [
            'id' => $nearDriver->id,
            'status' => DriverStatus::BUSY->value,
        ]);
    }

    #[Test]
    public function it_returns_409_when_no_driver_is_available(): void
    {
        // Only busy and offline drivers exist
        Driver::factory()->busy()->count(3)->create();
        Driver::factory()->offline()->count(2)->create();

        $order = Order::factory()->pending()->create();

        $response = $this->postJson("/api/orders/{$order->id}/assign");

        $response->assertStatus(409)
            ->assertJsonPath('error_code', 'NO_AVAILABLE_DRIVER');

        // Order should remain pending
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PENDING->value,
            'driver_id' => null,
        ]);
    }

    #[Test]
    public function it_returns_409_when_order_is_already_assigned(): void
    {
        $driver = Driver::factory()->busy()->create();

        $order = Order::factory()->assignedTo($driver)->create();

        // Create a new available driver for the (failed) reassignment attempt
        Driver::factory()->available()->create();

        $response = $this->postJson("/api/orders/{$order->id}/assign");

        $response->assertStatus(409)
            ->assertJsonPath('error_code', 'ORDER_ALREADY_ASSIGNED');
    }
}
