<?php

namespace HMsoft\Tools\Features\ClientOwnership\Providers;

use Illuminate\Support\ServiceProvider;

class ClientOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/client_ownership.php',
            'client_ownership'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/client_ownership.php' => config_path('client_ownership.php'),
            ], 'hmsoft-client-ownership-config');
        }
    }
}
