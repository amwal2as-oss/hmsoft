<?php

namespace HMsoft\Tools\Features\Media\Providers;

use HMsoft\Tools\Features\Media\Service\MediaUploadService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{

    public function register(): void
    {

        $this->mergeConfigFrom(
            __DIR__ . '/../config/cms_media.php',
            'cms_media'
        );

        // تسجيل خدمة الرفع ليعمل الـ Facade بشكل صحيح
        $this->app->singleton('media-uploader', function ($app) {
            return new MediaUploadService();
        });
    }


    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Lang', 'media');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cms_media.php' => config_path('cms_media.php'),
            ], 'cms_media-config');

            $this->publishes([
                __DIR__ . '/../Database/Migrations/' => database_path('migrations'),
            ], 'cms_media-migrations');
        }
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
