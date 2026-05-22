<?php

use Domain\Driver\Exceptions\DriverNotAvailableException;
use Domain\Order\Exceptions\OrderAlreadyAssignedException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../src/Presentation/Dispatcher/Routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Domain\Order\Exceptions\OrderAlreadyAssignedException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error_code' => 'ORDER_ALREADY_ASSIGNED',
                ], 409);
            }
        });

        $exceptions->render(function (\Domain\Driver\Exceptions\DriverNotAvailableException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error_code' => 'DRIVER_NOT_AVAILABLE',
                ], 422);
            }
        });

        // 🆕 لا سائقين متاحين
        $exceptions->render(function (\Domain\Order\Exceptions\NoAvailableDriverException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error_code' => 'NO_AVAILABLE_DRIVER',
                ], 409);
            }
        });
    })->create();
