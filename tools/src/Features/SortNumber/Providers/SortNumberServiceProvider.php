<?php

namespace HMsoft\Tools\Features\SortNumber\Providers;

use Illuminate\Support\ServiceProvider;

class SortNumberServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sort_number.php',
            'sort_number'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/sort_number.php' => config_path('sort_number.php'),
            ], 'hmsoft-sort-number-config');
        }
    }
}
