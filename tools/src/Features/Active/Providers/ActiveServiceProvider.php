<?php

namespace HMsoft\Tools\Features\Active\Providers;

use Illuminate\Support\ServiceProvider;

class ActiveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/active.php',
            'active'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/active.php' => config_path('active.php'),
            ], 'hmsoft-active-config');
        }
    }
}
