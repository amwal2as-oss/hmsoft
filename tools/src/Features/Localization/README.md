# Localization Feature

Plug-and-play locale detection for Laravel APIs and web apps. Database-agnostic; driven by `config/cms_localization.php`.

## Quick start

### 1. Register the provider

```php
// bootstrap/providers.php
HMsoft\Tools\Features\Localization\Providers\LocalizationServiceProvider::class,
```

Or rely on `HMsoftToolsServiceProvider` (registers Localization automatically).

### 2. Publish config (optional)

```bash
php artisan vendor:publish --tag=cms-localization-config
```

### 3. Apply middleware on API routes

```php
// bootstrap/app.php
$middleware->api(prepend: [
    'set.locale',
]);

$middleware->priority([
    \HMsoft\Tools\Features\OptionalAuth\Middleware\OptionalAuthMiddleware::class,
    \HMsoft\Tools\Features\Localization\Middleware\SetLocaleMiddleware::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
]);
```

See [Middleware order](./docs/MIDDLEWARE_ORDER.md) — **required** when using slug route binding.

### 4. Send locale from clients

```http
Accept-Language: ar
```

---

## Detection priority

When locale is resolved (middleware or `LocaleResolver`):

| Priority | Source | Example |
|----------|--------|---------|
| 1 | Route parameter | `/api/ar/news` |
| 2 | HTTP header | `Accept-Language: ar` |
| 3 | Session | `session('locale')` |
| 4 | Fallback | `config('cms_localization.fallback_locale')` |

---

## Core classes

| Class | Purpose |
|-------|---------|
| `SetLocaleMiddleware` | Middleware alias `set.locale` — sets `App::setLocale()` per request |
| `LocaleResolver` | Shared detection engine; safe **before** middleware (route binding, jobs, tests) |

### LocaleResolver usage

```php
use HMsoft\Tools\Features\Localization\Support\LocaleResolver;

// Read locale only (does not mutate App)
$locale = LocaleResolver::resolve();

// Read from a specific request
$locale = LocaleResolver::resolve($request);

// Resolve + apply to App (same as middleware)
$locale = LocaleResolver::apply($request);
```

Use `LocaleResolver::resolve()` anywhere `app()->getLocale()` is wrong because middleware has not run yet.

---

## Configuration

`config/cms_localization.php`:

```php
return [
    'supported_locales' => ['ar', 'en'],
    'fallback_locale'   => env('APP_FALLBACK_LOCALE', 'ar'),
    'detectors' => [
        'route_parameter' => 'locale',
        'header'          => 'Accept-Language',
        'session_key'     => 'locale',
    ],
];
```

Override at runtime (e.g. from DB):

```php
config(['cms_localization.supported_locales' => ['ar', 'en', 'fr']]);
```

---

## Documentation index

| Doc | Description |
|-----|-------------|
| [MIDDLEWARE_ORDER.md](./docs/MIDDLEWARE_ORDER.md) | Why `set.locale` must run before route model binding |
| [ROUTE_MODEL_BINDING.md](./docs/ROUTE_MODEL_BINDING.md) | Slug + ID show endpoints with `LocaleResolver` |
| [API_REFERENCE.md](./docs/API_REFERENCE.md) | Config keys, classes, and client headers |

---

## Related HMsoft features

- **Translations** — `HasTranslations` trait uses `app()->getLocale()` for `translation()` relation
- **Optional Auth** — runs before locale in recommended priority stack
