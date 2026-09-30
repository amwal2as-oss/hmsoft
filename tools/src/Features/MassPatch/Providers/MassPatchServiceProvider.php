<?php

namespace HMsoft\Tools\Features\MassPatch\Providers;

use HMsoft\Tools\Features\MassPatch\Contracts\MassPatchWriter;
use HMsoft\Tools\Features\MassPatch\Writers\EloquentMassPatchWriter;
use Illuminate\Support\ServiceProvider;

class MassPatchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/mass_patch.php',
            'mass_patch'
        );

        $this->app->bind(MassPatchWriter::class, EloquentMassPatchWriter::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'mass_patch');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/mass_patch.php' => config_path('mass_patch.php'),
            ], 'hmsoft-mass-patch-config');

            $this->publishes([
                __DIR__ . '/../lang/en/mass_patch.php' => lang_path('en/mass_patch.php'),
                __DIR__ . '/../lang/ar/mass_patch.php' => lang_path('ar/mass_patch.php'),
            ], 'hmsoft-mass-patch-lang');
        }
    }
}
