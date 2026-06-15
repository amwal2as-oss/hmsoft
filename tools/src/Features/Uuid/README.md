# UUID Feature

The UUID feature automatically assigns a globally unique identifier (UUID) to your Eloquent models.

## 🚀 Installation & Usage

Implement the `Uuidable` contract and use the `HasUuid` trait.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\Uuid\Contracts\Uuidable;
use HMsoft\Tools\Features\Uuid\Traits\HasUuid;

class Post extends Model implements Uuidable
{
    use HasUuid;
}
```

⚙️ Customization (Overrides)
Because you implemented the Uuidable interface, you can override these methods in your model:

1. Change UUID Generation Algorithm:
   By default, it uses UUID V4. If you have a large database, you might want to use Ordered UUIDs (V7 or Laravel's orderedUuid) for better indexing performance.

```PHP
public function generateUuid(): string
{
    return (string) \Illuminate\Support\Str::orderedUuid();
}
```

2. Change the Target Column:
   If your primary key is a standard integer ID, but you want a separate uuid column for API endpoints:

```PHP
public function getUuidColumnName(): string
{
    return 'api_uuid';
}
```
