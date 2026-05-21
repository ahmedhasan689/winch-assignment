<?php

namespace App\Providers;

use Domain\Driver\Contracts\DriverRepositoryInterface;
use Domain\Order\Contracts\OrderRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Persistence\Eloquent\Repositories\EloquentDriverRepository;
use Infrastructure\Persistence\Eloquent\Repositories\EloquentOrderRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Active requests (assigned/in_progress) for a specific driver.
     * Utilizes the Composite Index (driver_id, status) we created in the migration.
     */
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            EloquentOrderRepository::class,
        );

        $this->app->bind(
            DriverRepositoryInterface::class,
            EloquentDriverRepository::class,
        );
    }
}
