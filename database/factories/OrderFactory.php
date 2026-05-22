<?php

namespace Database\Factories;

use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'customer_phone' => '+9665' . fake()->numerify('########'),
            'pickup_lat' => fake()->randomFloat(7, 24.5, 25.0),
            'pickup_lng' => fake()->randomFloat(7, 46.5, 47.0),
            'dropoff_lat' => fake()->randomFloat(7, 24.5, 25.0),
            'dropoff_lng' => fake()->randomFloat(7, 46.5, 47.0),
            'status' => OrderStatus::PENDING->value,
            'driver_id' => null,
            'assigned_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::PENDING->value,
            'driver_id' => null,
            'assigned_at' => null,
        ]);
    }

    public function assignedTo(Driver $driver): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::ASSIGNED->value,
            'driver_id' => $driver->id,
            'assigned_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => OrderStatus::COMPLETED->value,
            'driver_id' => $attrs['driver_id'] ?? Driver::factory()->available(),
            'assigned_at' => now()->subHours(3),
        ]);
    }
}
