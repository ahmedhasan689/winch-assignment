<?php

namespace Domain\Order\Providers;

use Domain\Order\Contracts\OrderRepositoryContract;
use Domain\Order\Services\EloquentOrderRepository;
use Illuminate\Support\ServiceProvider;

final class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryContract::class,
            EloquentOrderRepository::class,
        );
    }
}
