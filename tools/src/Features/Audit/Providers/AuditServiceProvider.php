<?php

namespace HMsoft\Tools\Features\Audit\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use HMsoft\Tools\Features\Audit\Listeners\LogAuthenticationEvent;
use HMsoft\Tools\Features\Audit\Commands\VerifySystemState;
use HMsoft\Tools\Features\Audit\Commands\VerifyAuditLedger;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use HMsoft\Tools\Features\Audit\Models\AuditLog;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {


        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->registerRoutes();

           Relation::enforceMorphMap([
            'audit_log' => AuditLog::class,
        ]);


        // Dynamically scan and cache the Morph Map
        Relation::enforceMorphMap($this->resolveMorphMap());

        // Register Console Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                VerifySystemState::class,
                VerifyAuditLedger::class,
            ]);
        }

        // Register Authentication Event Listeners
        Event::listen(Login::class, LogAuthenticationEvent::class);
        Event::listen(Failed::class, LogAuthenticationEvent::class);
        Event::listen(Logout::class, LogAuthenticationEvent::class);
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api')         // إضافة بادئة الـ API العامة
            ->middleware('api')         // تطبيق حماية الـ API الافتراضية
            ->group(__DIR__ . '/../Routes/api.php');
    }

    protected function resolveMorphMap(): array
    {
        $resolver = fn (): array => $this->buildMorphMap();

        if (! $this->canUseCacheStore()) {
            return $resolver();
        }

        try {
            return Cache::rememberForever('system_morph_map', $resolver);
        } catch (\Throwable) {
            return $resolver();
        }
    }

    protected function canUseCacheStore(): bool
    {
        if (config('cache.default') !== 'database') {
            return true;
        }

        try {
            return Schema::hasTable(config('cache.stores.database.table', 'cache'));
        } catch (\Throwable) {
            return false;
        }
    }

    protected function buildMorphMap(): array
    {
        $map = [];
        $featuresPath = app_path('Features');

        if (! File::exists($featuresPath)) {
            return $map;
        }

        foreach (File::allFiles($featuresPath) as $file) {
            if (! str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR) || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $class = 'App\\Features\\' . $relativePath;

            if (! class_exists($class) || ! is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)) {
                continue;
            }

            $model = new $class;
            $alias = $model->getTable();

            try {
                $alias = $model->getMorphClass() !== $class
                    ? $model->getMorphClass()
                    : $model->getTable();
            } catch (\Exception) {
            }

            $map[$alias] = $class;
        }

        return $map;
    }
}
