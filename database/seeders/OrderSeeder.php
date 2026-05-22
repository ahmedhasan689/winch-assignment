<?php

namespace Database\Seeders;

use Domain\Driver\Enums\DriverStatus;
use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 10 pending orders - waiting for assignment
        Order::factory()->pending()->count(10)->create();

        // Assigned orders linked to busy drivers
        $busyDrivers = Driver::query()
            ->where('status', DriverStatus::BUSY->value)
            ->get();

        foreach ($busyDrivers as $driver) {
            Order::factory()->assignedTo($driver)->create();
        }

        // 5 historical completed orders
        Order::factory()->completed()->count(5)->create();
    }
}
