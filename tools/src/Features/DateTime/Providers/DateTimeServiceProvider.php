<?php

namespace HMsoft\Tools\Features\DateTime\Providers;

use DateTimeInterface;
use HMsoft\Tools\Features\DateTime\Casts\CmsDateTimeCast;
use HMsoft\Tools\Features\DateTime\Contracts\DateTimeResolverInterface;
use HMsoft\Tools\Features\DateTime\Resolvers\CallbackDateTimeResolver;
use HMsoft\Tools\Features\DateTime\Resolvers\ConfigDateTimeResolver;
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;
use HMsoft\Tools\Features\DateTime\Support\DateTimeConfig;
use HMsoft\Tools\Features\DateTime\Transformers\CmsDateTimeTransformer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class DateTimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cms_datetime.php',
            'cms_datetime'
        );

        $this->app->singleton(DateTimeResolverInterface::class, function () {
            return $this->makeResolver();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cms_datetime.php' => config_path('cms_datetime.php'),
            ], 'cms-datetime-config');
        }

        if (! DateTimeConfig::isEnabled()) {
            return;
        }

        Date::serializeUsing(function (DateTimeInterface $date): string {
            return CmsDateTime::toApi($date) ?? '';
        });

        Carbon::macro('toApi', function (): ?string {
            /** @var Carbon $this */
            return CmsDateTime::toApi($this);
        });

        config([
            'data.date_timezone' => DateTimeConfig::defaultApiTimezone(),
            'data.date_format' => DateTimeConfig::dateFormat(),
            'data.casts.' . DateTimeInterface::class => CmsDateTimeCast::class,
            'data.transformers.' . DateTimeInterface::class => CmsDateTimeTransformer::class,
        ]);

        if (DateTimeConfig::shouldRegisterRoutes()) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }

    protected function makeResolver(): DateTimeResolverInterface
    {
        $resolver = DateTimeConfig::resolver();
        $class = DateTimeConfig::resolverClass();

        if (in_array($resolver, ['class', 'custom'], true) && $class !== null) {
            $instance = $this->app->make($class);

            if (! $instance instanceof DateTimeResolverInterface) {
                throw new InvalidArgumentException("Resolver class [{$class}] must implement DateTimeResolverInterface.");
            }

            return $instance;
        }

        if ($resolver === 'callback') {
            return new CallbackDateTimeResolver();
        }

        return new ConfigDateTimeResolver();
    }
}
