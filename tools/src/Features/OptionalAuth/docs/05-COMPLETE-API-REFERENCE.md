# OptionalAuth — Complete API Reference

---

## OptionalAuthMiddleware

**Namespace:** `HMsoft\Tools\Features\OptionalAuth\Middleware\OptionalAuthMiddleware`

### Signature

```php
public function handle(Request $request, Closure $next, ...$guards): Response
```

### Parameters

| Parameter | Source | Description |
|-----------|--------|-------------|
| `$guards` | Middleware route definition | Guard names to try, in order |
| (default) | No parameter | All keys from `config('auth.guards')` |

### Registration syntax

```php
// Single guard (recommended for API)
OptionalAuthMiddleware::class . ':sanctum'

// Alias form
'optional.auth:sanctum'

// Multiple guards — first authenticated wins
'optional.auth:sanctum,web'
```

### Side effects on success

```php
Auth::shouldUse($guard);
$request->setUserResolver(fn () => Auth::guard($guard)->user());
```

### Side effects on failure (guest)

No changes — request proceeds with null user.

### Return value

Always passes through to `$next($request)` — **never aborts**.

---

## OptionalAuthServiceProvider

**Namespace:** `HMsoft\Tools\Features\OptionalAuth\Providers\OptionalAuthServiceProvider`

| Method | Behavior |
|--------|----------|
| `boot()` | Empty — alias registered in app bootstrap |
| `register()` | Default (none) |

---

## Middleware alias

| Alias | Class |
|-------|-------|
| `optional.auth` | `OptionalAuthMiddleware` |

Register in `bootstrap/app.php`:

```php
$middleware->alias([
    'optional.auth' => OptionalAuthMiddleware::class,
]);
```

---

## Auth helper reference after middleware

| Helper | Guest | Authenticated |
|--------|-------|---------------|
| `auth()->check()` | `false` | `true` |
| `auth()->user()` | `null` | `User` model |
| `auth()->id()` | `null` | user id |
| `$request->user()` | `null` | `User` model |
| `Auth::user()` | `null` | `User` model |

---

## Comparison table — Laravel auth middlewares

| Middleware | Guest request | Invalid token | Valid token |
|------------|---------------|---------------|-------------|
| `optional.auth:sanctum` | 200 continue | 200 continue (guest) | 200 + user |
| `auth:sanctum` | 401 | 401 | 200 + user |
| `permission:blog` | 401 | 401 | 200 if permitted |

---

## Recommended middleware stacks

### Public API read

```php
// Global only — no route middleware
Route::get('/blogs', [BlogController::class, 'index']);
```

Stack: `OptionalAuth:sanctum` → `zero.trust` → controller

### CMS feature routes

```php
Route::prefix('decrees')->group(function () { ... })
    ->middleware('permission:decree');
```

Stack: `OptionalAuth:sanctum` → ... → `permission:decree` → controller

### User account routes

```php
Route::middleware('auth:sanctum')->prefix('profile')->group(function () { ... });
```

Stack: `OptionalAuth:sanctum` → ... → `auth:sanctum` → controller

---

## Related app components

| Component | Path | Role |
|-----------|------|------|
| `IsFavoritedResolver` | `app/Features/Favorite/Support/` | Guest-safe favorite check |
| `ApplyActiveScopeForNotAdmin` | `app/Traits/` | Guest = active-only scope |
| `CheckFeaturePermission` | `app/Features/Authorization/Middleware/` | Requires authenticated user |
| Sanctum config | `config/sanctum.php` | Token/session auth |

---

## bootstrap/app.php reference (this project)

```php
$middleware->api(append: [
    OptionalAuthMiddleware::class . ':sanctum',
    'zero.trust',
    'set.locale',
]);

$middleware->alias([
    'optional.auth' => OptionalAuthMiddleware::class,
]);

$middleware->priority([
    OptionalAuthMiddleware::class,
    SubstituteBindings::class,
]);
```

---

## See also

- [../README.md](../README.md)
- [01-BACKEND-ARCHITECTURE.md](./01-BACKEND-ARCHITECTURE.md)
