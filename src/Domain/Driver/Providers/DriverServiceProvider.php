<?php

namespace Domain\Driver\Providers;

use Domain\Driver\Contracts\DriverMatcherContract;
use Domain\Driver\Contracts\DriverRepositoryContract;
use Domain\Driver\Services\EloquentDriverRepository;
use Domain\Driver\Services\NearestAvailableDriverMatcher;
use Illuminate\Support\ServiceProvider;

final class DriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DriverRepositoryContract::class,
            EloquentDriverRepository::class,
        );

        $this->app->bind(
            DriverMatcherContract::class,
            NearestAvailableDriverMatcher::class,
        );
    }
}
