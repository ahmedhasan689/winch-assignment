<?php

use Illuminate\Support\Facades\Route;
use Presentation\Dispatcher\Controllers\DriverOrdersController;
use Presentation\Dispatcher\Controllers\OrderAssignmentController;
use Presentation\Dispatcher\Controllers\OrderListController;

/*
 * Dispatcher API routes.
 * Loaded from bootstrap/app.php.
 */

Route::post('/orders/{order}/assign', [OrderAssignmentController::class, 'store'])
    ->whereNumber('order');

Route::get('/drivers/{driver}/orders', [DriverOrdersController::class, 'index'])
    ->whereNumber('driver');

Route::get('/orders', [OrderListController::class, 'index']);
