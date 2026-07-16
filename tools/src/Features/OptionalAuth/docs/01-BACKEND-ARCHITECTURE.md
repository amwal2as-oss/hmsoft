# OptionalAuth — Backend Architecture

---

## Package structure

```
OptionalAuth/
├── Middleware/
│   └── OptionalAuthMiddleware.php   # Core logic
├── Providers/
│   └── OptionalAuthServiceProvider.php  # Package registration (minimal)
└── docs/
```

Only **two PHP files** — no config, migrations, or routes in the feature itself.

---

## Middleware flow

```php
public function handle(Request $request, Closure $next, ...$guards): Response
{
    if (empty($guards)) {
        $guards = array_keys(config('auth.guards'));
    }

    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check()) {
            Auth::shouldUse($guard);
            $request->setUserResolver(fn () => Auth::guard($guard)->user());
            break;
        }
    }

    return $next($request);
}
```

### Step-by-step

1. **Resolve guards** — from middleware parameter (`sanctum`) or all configured guards
2. **Loop guards** — call `Auth::guard($guard)->check()`
3. **First match** — set default guard + request user resolver
4. **No match** — leave request unauthenticated (guest)
5. **Always** call `$next($request)` — never abort

---

## Comparison with Laravel `auth` middleware

| | `Authenticate` (auth:sanctum) | `OptionalAuthMiddleware` |
|---|------------------------------|--------------------------|
| Unauthenticated | Throws `AuthenticationException` → 401 | Continues as guest |
| Authenticated | Sets user | Sets user |
| Guard param | `:sanctum` | `:sanctum` (same syntax) |
| Use case | Protected resources | Public + personalized |

---

## Request user resolution

After optional auth succeeds:

```php
auth()->user()      // Works
auth()->id()        // Works
auth()->check()     // true
$request->user()    // Works (resolver set)
```

After guest (no valid token):

```php
auth()->user()      // null
auth()->id()        // null
auth()->check()     // false
$request->user()    // null
```

---

## Guard parameter syntax

Registered in `bootstrap/app.php`:

```php
OptionalAuthMiddleware::class . ':sanctum'
//                              ^^^^^^^^ passed as $guards argument
```

Multiple guards (first authenticated wins):

```php
'optional.auth:sanctum,web'
```

---

## Middleware stack in this project

```
1. DynamicCorsMiddleware (prepend)
2. ... Laravel defaults ...
3. OptionalAuthMiddleware:sanctum  ← api append
4. zero.trust (decrypt/encrypt)
5. set.locale
6. Route middleware (permission, etc.)
7. Controller
```

**Priority** (runs early):

```php
$middleware->priority([
    OptionalAuthMiddleware::class,
    SubstituteBindings::class,
]);
```

Ensures auth context exists before route model binding and global scopes run.

---

## Sanctum integration

This app uses:

```php
$middleware->statefulApi();
```

Optional auth + Sanctum supports:

- **SPA cookie auth** (stateful domains)
- **Bearer token auth** (mobile/API clients)

Both are checked via `Auth::guard('sanctum')->check()`.

---

## Service provider

`OptionalAuthServiceProvider` currently has empty `boot()` — alias registration moved to application bootstrap (Laravel 11 pattern).

Provider still registered for package discovery consistency.

---

## See also

- [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md)
- [05-COMPLETE-API-REFERENCE.md](./05-COMPLETE-API-REFERENCE.md)
