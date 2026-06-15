<?php

namespace HMsoft\Tools\Features\Response\Providers;

use Illuminate\Support\ServiceProvider;
use HMsoft\Tools\Features\Response\Contracts\ResponseFormatter;
use HMsoft\Tools\Features\Response\Services\CmsResponse;

class ResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResponseFormatter::class, CmsResponse::class);
    }

    public function boot(): void
    {
        //
    }
}
