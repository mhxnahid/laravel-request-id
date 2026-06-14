<?php

namespace Mxnwire\RequestId;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Mxnwire\RequestId\Http\Middleware\RequestIdMiddleware;

class RequestIdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/request-id.php', 'request-id');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/request-id.php' => config_path('request-id.php'),
        ], 'request-id-config');

        // Register the "request-id" alias so the middleware can be added to any
        // route or group manually.
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('request-id', RequestIdMiddleware::class);

        // Prepend to the global stack so every request is covered out of the box.
        if (config('request-id.register_global_middleware', true)) {
            $kernel = $this->app->make(Kernel::class);
            $kernel->prependMiddleware(RequestIdMiddleware::class);
        }
    }
}
