# Advanced Localization System (Plug-and-Play)

This document provides a comprehensive guide for integrating, using, and customizing the **Localization Feature**. Built on Clean Architecture principles, this component is entirely database-agnostic. It determines the user's preferred language using a smart priority system and allows developers to inject supported locales dynamically from any source (Config, Database, Cache, or external API).

---

## 1. Core Architecture & Mechanism

The Localization feature operates as a standalone middleware-driven component. It decouples language detection from rigid database structures, relying instead on a configuration-first approach.

### The Detection Priority Engine

When a request passes through the `SetLocaleMiddleware`, the system attempts to detect the correct locale in the following strict order:

1. **Route Parameter (URL Segment):** Checks if the URL contains a valid locale (e.g., `/ar/dashboard`).
2. **HTTP Header (`Accept-Language`):** Parses the browser or API request header. It intelligently extracts the primary language code (e.g., converting `en-US,en;q=0.9` to `en`). Extremely useful for stateless API consumption.
3. **Session State:** Checks if the user previously selected a language that was saved in the active session.
4. **Fallback Locale:** If all the above fail, it defaults to the application's fallback locale defined in the config.

---

## 2. Installation & Setup

### Step 1: Register the Service Provider

Ensure the package provider is registered within your application bootstrap (`bootstrap/providers.php` or `config/app.php`):

```php
HMsoft\Tools\Features\Localization\Providers\LocalizationServiceProvider::class,
```

Step 2: Publish the Configuration File
Publish the default configuration to your application's config directory so you can modify it:

```Bash
php artisan vendor:publish --tag="cms-localization-config"
```

Step 3: Review the Configuration (config/cms_localization.php)
This file controls the supported locales, the fallback language, and the detection keys:

```PHP
<?php

return [
    // Array of strictly supported locale codes.
    'supported_locales' => ['ar', 'en'],

    // The default language if detection fails.
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'ar'),

    // Keys used by the Middleware to detect the language.
    'detectors' => [
        'route_parameter' => 'locale',          // e.g., Route::get('/{locale}/home')
        'header'          => 'Accept-Language', // Standard HTTP Header
        'session_key'     => 'locale',          // Session storage key
    ]
];
```

3. Usage & Integration
   Once configured, the Service Provider automatically registers a middleware alias: set.locale.

Protecting API Routes (Header-Based Detection)
Apply the middleware to your API routes. The system will automatically detect the language via the Accept-Language header.

```PHP
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'set.locale'])->group(function () {
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/services', [ServiceController::class, 'index']);
});
```

Protecting Web Routes (URL & Session-Based Detection)
Apply it to web routes where the locale might be present in the URL parameter.

```PHP
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'set.locale'])->prefix('{locale?}')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

4. Advanced Customization & Dynamic Overriding
   One of the strongest features of this package is that it doesn't force you to use a static array. You can dynamically override the configuration at runtime.

Dynamic Database Injection (The "Override" Pattern)
If your main application manages languages via a database table (e.g., a langs table), you can inject those active languages into the Localization package dynamically during the application's boot sequence.

Open your main application's App\Providers\AppServiceProvider.php and override the config:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use App\Models\Lang;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Ensure the table exists to prevent errors during initial migrations
        if (Schema::hasTable('langs')) {

            // Cache the active locales to avoid querying the DB on every single request
            $activeLocales = Cache::rememberForever('active_locales', function () {
                return Lang::where('is_active', true)->pluck('locale')->toArray();
            });

            // Dynamically override the package's supported locales at runtime!
            config(['cms_localization.supported_locales' => $activeLocales]);
        }
    }
}
```

Extending or Replacing the Middleware
If your business logic requires a radically different priority engine (for instance, reading the locale from an authenticated User's profile before checking the session), you can easily override the middleware binding.

In your AppServiceProvider:

```PHP
use HMsoft\Tools\Features\Localization\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\MyCustomLocaleMiddleware;

public function boot(\Illuminate\Routing\Router $router): void
{
    // Override the package's alias with your custom middleware implementation
    $router->aliasMiddleware('set.locale', MyCustomLocaleMiddleware::class);
}
```

5. Summary of Benefits
   Zero Database Coupling: Works perfectly out-of-the-box using pure PHP arrays, making it highly portable.

RESTful API Ready: Automatically parses HTTP headers, eliminating the need to pass ?lang=en in every API query string.

Highly Cacheable: Because it relies on Laravel's Config repository, it integrates perfectly with php artisan config:cache and custom Cache drivers.
