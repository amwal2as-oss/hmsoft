<?php

namespace HMsoft\Tools\Features\Attribute\Providers;

use HMsoft\Tools\Features\Attribute\Services\EavValueSyncService;
use HMsoft\Tools\Features\Attribute\Support\EavConfig;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AttributeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cms_eav.php',
            'cms_eav'
        );

        $this->app->singleton(EavValueSyncService::class);
    }

    public function boot(): void
    {
        if (! EavConfig::isEnabled()) {
            return;
        }

        $this->loadTranslationsFrom(__DIR__ . '/../Lang', 'cms_eav');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cms_eav.php' => config_path('cms_eav.php'),
            ], 'cms-eav-config');
        }

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
