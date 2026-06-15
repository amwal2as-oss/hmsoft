# Response Feature

A unified, customizable JSON response formatter for API endpoints. Built on Laravel's Contract/Facade architecture.

🚀 Installation & Setup
Register the Provider:
To enable the Response feature, add the ResponseServiceProvider to your main package provider (e.g., HMsoftToolsServiceProvider.php) inside the register method:

```PHP
public function register(): void
{
    // ... other providers
    $this->app->register(\HMsoft\Tools\Features\Response\Providers\ResponseServiceProvider::class);
}
```

## 🚀 Basic Usage

Import the Facade in your controllers:

```php
use HMsoft\Tools\Features\Response\Facades\CmsResponse;

return CmsResponse::success(message: 'Data loaded', data: $items);
return CmsResponse::error(message: 'Not found', state: 404);
```

⚙️ How to Override (success, error, format)
If you want to customize how success() or error() behave individually (e.g., logging errors automatically, or changing success statuses), you can extend the base service class.

Step 1: Create your Custom Class

```PHP
namespace App\Support;

use HMsoft\Tools\Features\Response\Services\CmsResponse as DefaultCmsResponse;
use Illuminate\Support\Facades\Log;

class CustomApiResponse extends DefaultCmsResponse
{
    /**
     * Override the success method specifically!
     */
    public function success(string $message = "", $data = [], int $code = 200, array $with = [], $pagination = null, $meta = null)
    {
        // Custom Logic: Add a timestamp to every success response
        $meta['timestamp'] = now()->toDateTimeString();

        return parent::success($message, $data, $code, $with, $pagination, $meta);
    }

    /**
     * Override the error method specifically!
     */
    public function error(string $message = "", int $state = 500, array $errors = [], string|null $errorCode = null, $meta = null)
    {
        // Custom Logic: Automatically log all 500 errors
        if ($state >= 500) {
            Log::error("API Error: {$message}", ['errors' => $errors]);
        }

        // Return a completely different JSON structure for errors
        return response()->json([
            'status' => 'failed',
            'error_message' => $message,
            'details' => $errors
        ], $state);
    }
}
```

Step 2: Bind it to the Contract in AppServiceProvider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use HMsoft\Tools\Features\Response\Contracts\ResponseFormatter;
use App\Support\CustomApiResponse;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // The Facade will now use YOUR class globally!
        $this->app->singleton(ResponseFormatter::class, CustomApiResponse::class);
    }
}
```
