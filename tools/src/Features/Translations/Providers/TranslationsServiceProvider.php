<?php

namespace HMsoft\Tools\Features\Translations\Providers;

use HMsoft\Tools\Features\Translations\Contracts\TranslationResolver;
use HMsoft\Tools\Features\Translations\Support\DefaultTranslationResolver;
use Illuminate\Support\ServiceProvider;

class TranslationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cms_translations.php',
            'cms_translations'
        );

        $this->app->singleton(TranslationResolver::class, DefaultTranslationResolver::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cms_translations.php' => config_path('cms_translations.php'),
            ], 'cms-translations-config');
        }
    }
}
