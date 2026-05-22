<?php

namespace Database\Seeders;

use Domain\Driver\Models\Entities\Driver;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 5 available drivers - ready to receive orders
        Driver::factory()->available()->count(5)->create();

        // 3 busy drivers - already handling orders
        Driver::factory()->busy()->count(3)->create();

        // 2 offline drivers - not working currently
        Driver::factory()->offline()->count(2)->create();
    }
}
