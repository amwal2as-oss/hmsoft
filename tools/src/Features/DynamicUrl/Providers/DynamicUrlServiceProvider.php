<?php

namespace HMsoft\Tools\Features\DynamicUrl\Providers;

use Illuminate\Support\ServiceProvider;
use HMsoft\Tools\Features\DynamicUrl\Middleware\DynamicUrlMiddleware;

class DynamicUrlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cms_dynamicUrl.php',
            'cms_dynamicUrl'
        );
    }

    public function boot(): void
    {
        // تسجيل الـ Middleware كاسم مستعار
        $router = $this->app['router'];
        $router->aliasMiddleware('dynamic.url', DynamicUrlMiddleware::class);

        // السماح بنشر الإعدادات
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cms_dynamicUrl.php' => config_path('cms_dynamicUrl.php'),
            ], 'cms_dynamicUrl-config');
        }
    }
}
