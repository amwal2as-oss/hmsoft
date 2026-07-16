# Middleware Order

## Problem

Laravel resolves **route model binding** inside `SubstituteBindings` middleware. If `set.locale` runs **after** binding, then:

- `app()->getLocale()` is still the app default (often `en`)
- `Accept-Language: ar` is ignored during slug lookup
- Translated slug routes return 404 or match the wrong locale

## Required stack (API)

In `bootstrap/app.php`:

```php
use HMsoft\Tools\Features\Localization\Middleware\SetLocaleMiddleware;

$middleware->api(prepend: [
    'set.locale',
]);

$middleware->priority([
    \HMsoft\Tools\Features\OptionalAuth\Middleware\OptionalAuthMiddleware::class,
    SetLocaleMiddleware::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
]);
```

### Execution order (inbound)

```
1. OptionalAuth (sanctum)
2. SetLocaleMiddleware     ← sets App locale from Accept-Language
3. SubstituteBindings      ← route model binding (slug/id)
4. … other api middleware (zero.trust, controllers)
```

## Rules

| Do | Don't |
|----|-------|
| Prepend `set.locale` to the `api` group | Append `set.locale` after default API middleware |
| Put `SetLocaleMiddleware` **before** `SubstituteBindings` in `$middleware->priority()` | Rely on default middleware order alone |
| Use `LocaleResolver::resolve()` inside early binding code | Duplicate Accept-Language parsing in app code |

## Verify

```http
GET /api/news/{slug}
Accept-Language: ar
```

With middleware order correct, `app()->getLocale()` inside the controller returns `ar`.

For binding that still runs before middleware, use:

```php
use HMsoft\Tools\Features\Localization\Support\LocaleResolver;

$locale = LocaleResolver::resolve(); // reads Accept-Language directly
```

See [ROUTE_MODEL_BINDING.md](./ROUTE_MODEL_BINDING.md).
