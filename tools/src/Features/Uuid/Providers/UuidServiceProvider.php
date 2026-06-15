<?php

namespace HMsoft\Tools\Features\Uuid\Providers;

use Illuminate\Support\ServiceProvider;

class UuidServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register bindings if needed
    }

    public function boot(): void
    {
        // Uuid doesn't require config publishing, but it's ready for future extensions.
    }
}
