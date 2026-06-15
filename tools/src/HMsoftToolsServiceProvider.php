<?php

namespace HMsoft\Tools;

use Illuminate\Support\ServiceProvider;

class HMsoftToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // تسجيل المزودات المستقلة لكل Feature
        $this->app->register(\HMsoft\Tools\Features\Active\Providers\ActiveServiceProvider::class);
        $this->app->register(\HMsoft\Tools\Features\SortNumber\Providers\SortNumberServiceProvider::class);
        $this->app->register(\HMsoft\Tools\Features\Response\Providers\ResponseServiceProvider::class);
        $this->app->register(\HMsoft\Tools\Features\OptionalAuth\Providers\OptionalAuthServiceProvider::class);
        $this->app->register(\HMsoft\Tools\Features\DynamicUrl\Providers\DynamicUrlServiceProvider::class);
        // لاحقاً سنضيف هنا: DynamicFiltersServiceProvider, SecurityServiceProvider, MediaServiceProvider
    }

    public function boot(): void
    {
        // يمكننا هنا وضع أوامر نشر الإعدادات العامة للمكتبة (Publishing) إن وجدت
    }
}
