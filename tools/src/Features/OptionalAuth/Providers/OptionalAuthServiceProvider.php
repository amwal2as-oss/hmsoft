<?php

namespace HMsoft\Tools\Features\OptionalAuth\Providers;

use Illuminate\Support\ServiceProvider;
use HMsoft\Tools\Features\OptionalAuth\Middleware\OptionalAuthMiddleware;

class OptionalAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // تسجيل الـ Middleware باسم مستعار
        $router = $this->app['router'];
        $router->aliasMiddleware('optional.auth', OptionalAuthMiddleware::class);
    }
}
