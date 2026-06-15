# Dynamic URL Feature

Automatically updates Laravel's `app.url` and `Reverb` WebSocket configurations at runtime based on the incoming request's host, scheme, and port. Ideal for Multi-Tenancy or Multi-Domain setups.

## 🚀 Installation & Setup

1. **Publish the config file:**
    ```bash
    php artisan vendor:publish --tag=cms_dynamicUrl-config"
    ```

Enable the feature:
In your .env file, add:

```env
DYNAMIC_URL_ENABLED=true
```

🛠️ Usage
Simply attach the dynamic.url middleware to your routes.

For APIs or Multi-Tenant apps, it is highly recommended to place this middleware high up in the stack (e.g., in bootstrap/app.php or app/Http/Kernel.php depending on your Laravel version), so it adjusts the URL globally before other logic runs.

```PHP
use Illuminate\Support\Facades\Route;

Route::middleware(['dynamic.url', 'api'])->group(function () {
    // Inside here, config('app.url') will reflect the actual request domain!
});
```
