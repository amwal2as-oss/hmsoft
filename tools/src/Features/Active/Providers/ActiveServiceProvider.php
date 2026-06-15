<?php

namespace HMsoft\Tools\Features\Active\Providers;

use Illuminate\Support\ServiceProvider;

class ActiveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // تسجيل أي Bindings أو Singletons خاصة بميزة الـ Active هنا
    }

    public function boot(): void
    {
        // تحميل أي إعدادات أو مسارات خاصة بهذه الميزة فقط
    }
}
