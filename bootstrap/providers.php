<?php

return [
    App\Providers\AppServiceProvider::class,
    Domain\Order\Providers\OrderServiceProvider::class,
    Domain\Driver\Providers\DriverServiceProvider::class,
    Presentation\Dispatcher\Providers\DispatcherServiceProvider::class
];
