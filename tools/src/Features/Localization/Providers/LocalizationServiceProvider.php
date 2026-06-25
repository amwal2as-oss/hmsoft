<?php

namespace HMsoft\Tools\Features\Localization\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use HMsoft\Tools\Features\Localization\Middleware\SetLocaleMiddleware;

class LocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // دمج الإعدادات الافتراضية
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cms_localization.php',
            'cms_localization'
        );
    }

    public function boot(Router $router): void
    {
        // نشر ملف الإعدادات ليتمكن المطور من التعديل عليه
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cms_localization.php' => config_path('cms_localization.php'),
            ], 'cms-localization-config');
        }

        // تسجيل الـ Middleware كـ Alias (مفيد في Laravel 11 وما قبله)
        $router->aliasMiddleware('set.locale', SetLocaleMiddleware::class);
    }
}
