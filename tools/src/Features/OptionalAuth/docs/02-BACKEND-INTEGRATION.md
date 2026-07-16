# OptionalAuth — Backend Integration Guide

> **Full reference:** [../README.md](../README.md) | **Checklist:** [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md)

---

## Prerequisites

- Laravel 11+ with `bootstrap/app.php` middleware configuration
- Laravel Sanctum installed and configured
- HMsoft Tools package with OptionalAuth feature

---

## Step 1 — Register provider

`bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    // ...
    \HMsoft\Tools\Features\OptionalAuth\Providers\OptionalAuthServiceProvider::class,
];
```

---

## Step 2 — Configure middleware (`bootstrap/app.php`)

Full example from this project:

```php
<?php

use HMsoft\Tools\Features\OptionalAuth\Middleware\OptionalAuthMiddleware;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Global optional auth on all API routes
        $middleware->api(append: [
            OptionalAuthMiddleware::class . ':sanctum',
            'zero.trust',
            'set.locale',
        ]);

        // Alias for selective use
        $middleware->alias([
            'optional.auth' => OptionalAuthMiddleware::class,
        ]);

        // Run before route model binding
        $middleware->priority([
            OptionalAuthMiddleware::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->create();
```

---

## Step 3 — Route strategy

### Public read endpoints (no extra middleware)

```php
Route::prefix('blogs')->group(function () {
    Route::get('/', [BlogController::class, 'index']);
    Route::get('/{blog}', [BlogController::class, 'show']);
});
```

Optional auth from global middleware handles token if present.

### CMS write endpoints (require permission)

```php
Route::prefix('decrees')->group(function () {
    Route::get('/', [DecreeController::class, 'index']);
    Route::post('/', [DecreeController::class, 'store']);
    Route::post('/{decree}', [DecreeController::class, 'update']);
})->middleware('permission:decree');
```

`CheckFeaturePermission` returns 401 if `$request->user()` is null.

### Strict auth endpoints

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
});
```

---

## Step 4 — Write guest-safe controller code

```php
public function index()
{
    $result = $this->blogService->list();
    $result['data'] = BlogData::filterableCollect($result['data']);
    return CmsResponse::success(data: $result['data'], pagination: $result['pagination']);
}
```

Personalization happens in DTO layer:

```php
is_favorited: IsFavoritedResolver::forModel($blog), // false when guest
```

---

## Step 5 — Guest vs admin visibility (optional)

On models that should hide inactive content from public:

```php
use App\Traits\ApplyActiveScopeForNotAdmin;
use HMsoft\Tools\Features\Active\Traits\HasActiveScope;

class Blog extends Model
{
    use HasActiveScope, ApplyActiveScopeForNotAdmin;
}
```

| Viewer | `is_active = false` records |
|--------|----------------------------|
| Guest | Hidden |
| Logged-in customer | Hidden |
| Admin | Visible |

Requires OptionalAuth to run **before** query executes so `Auth::user()` is available in scope.

---

## Step 6 — Add personalized fields

```php
class IsFavoritedResolver
{
    public static function forModel(Model $model): bool
    {
        if (!auth()->id()) {
            return false;
        }
        return Favorite::query()
            ->where('user_id', auth()->id())
            ->where('resource_type', ...)
            ->where('resource_id', $model->getKey())
            ->exists();
    }
}
```

---

## Per-route optional auth (without global)

If you prefer not to apply globally:

```php
// Remove from api append, use on specific groups:
Route::middleware('optional.auth:sanctum')->group(function () {
    Route::get('/public-feed', ...);
});
```

---

## Testing with curl

```bash
# Guest
curl http://localhost/api/blogs

# Authenticated
curl http://localhost/api/blogs \
  -H "Authorization: Bearer 1|your-sanctum-token"

# Protected (should 401 without token)
curl -X POST http://localhost/api/decrees \
  -H "Content-Type: application/json" \
  -d '{"title":"test"}'
```

---

## Common integration mistakes

| Mistake | Fix |
|---------|-----|
| `auth:sanctum` on public GET routes | Remove — use global optional auth only |
| Assuming user always exists | Always null-check `auth()->user()` |
| Permission middleware on public routes | Guests get 401 — only on write routes |
| Optional auth after SubstituteBindings | Set middleware priority correctly |

---

## See also

- [03-FRONTEND-GUIDE.md](./03-FRONTEND-GUIDE.md)
- [05-COMPLETE-API-REFERENCE.md](./05-COMPLETE-API-REFERENCE.md)
