# Optional Auth Feature

A middleware that allows endpoints to be accessed by both guests and authenticated users, dynamically resolving the user if they are logged in.

## 🚀 Basic Usage

Simply add the `optional.auth` middleware to your routes. If the user is authenticated, `Auth::user()` will return their profile; otherwise, it will be `null` and the request will proceed.

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['optional.auth'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
});

```

⚠️ Important: Middleware Order (Critical)
If your application uses multi-tenancy or database connection switching (e.g., a connection middleware), you MUST ensure that optional.auth runs AFTER the connection middleware.

The optional.auth middleware performs an Auth::check(), which queries the database. If it runs before the connection is switched to the correct tenant/database, it will query the default database (causing "Invalid object name" errors or authentication failures).

Correct implementation:

```PHP
// WRONG: 'optional.auth' runs before 'connection'
Route::middleware(['api', 'optional.auth:api', 'connection'])->group(...);

// CORRECT: 'connection' sets the DB context first, then 'optional.auth' queries it
Route::middleware(['api', 'connection', 'optional.auth:api'])->group(...);
```


⚙️ Advanced Usage (Specific Guards)
You can specify which guards the middleware should check. If you pass nothing, it checks all guards defined in config/auth.php.

```PHP
// Only check 'api' guard
Route::middleware(['connection', 'optional.auth:api'])->get('/profile', function() {
    return auth()->user() ?? 'Guest';
});
```
