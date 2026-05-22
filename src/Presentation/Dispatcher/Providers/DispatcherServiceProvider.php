<?php

namespace Presentation\Dispatcher\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class DispatcherServiceProvider extends ServiceProvider
{
    /**
     * Register the Dispatcher panel's view directory and namespace.
     */
    public function boot(): void
    {
        $viewsPath = __DIR__ . '/../Views';

        // Register as a namespaced location: view('dispatcher::dispatcher')
        View::addNamespace('dispatcher', $viewsPath);

        // Also register globally for convenience: view('dispatcher')
        View::addLocation($viewsPath);
    }
}
