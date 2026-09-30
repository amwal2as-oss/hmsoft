<?php

namespace HMsoft\Tools\Features\BulkDelete\Providers;

use Illuminate\Support\ServiceProvider;

class BulkDeleteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'bulk_delete');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../lang/en/bulk_delete.php' => lang_path('en/bulk_delete.php'),
                __DIR__ . '/../lang/ar/bulk_delete.php' => lang_path('ar/bulk_delete.php'),
            ], 'hmsoft-bulk-delete-lang');
        }
    }
}
