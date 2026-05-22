<?php

namespace Database\Factories;

use Domain\Driver\Enums\DriverStatus;
use Domain\Driver\Models\Entities\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
final class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+9665' . fake()->numerify('########'),
            'status' => DriverStatus::OFFLINE->value,
            'current_lat' => fake()->randomFloat(7, 24.5, 25.0),
            'current_lng' => fake()->randomFloat(7, 46.5, 47.0),
        ];
    }


    public function available(): static
    {
        return $this->state(fn () => ['status' => DriverStatus::AVAILABLE->value]);
    }

    public function busy(): static
    {
        return $this->state(fn () => ['status' => DriverStatus::BUSY->value]);
    }

    public function offline(): static
    {
        return $this->state(fn () => ['status' => DriverStatus::OFFLINE->value]);
    }
}
