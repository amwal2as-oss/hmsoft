<?php

namespace HMsoft\Tools\Features\UserStamps\Providers;

use Illuminate\Support\ServiceProvider;

class UserStampsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/user-stamps.php',
            'user-stamps'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // تفعيل الـ Publishing ليتمكن المطور من تعديل الإعدادات
            $this->publishes([
                __DIR__ . '/../config/user-stamps.php' => config_path('user-stamps.php'),
            ], 'hmsoft-user-stamps-config');
        }
    }
}
