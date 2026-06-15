# User Stamps Feature (CRUD Tracker)

Automatically tracks the user responsible for creating, updating, or deleting an Eloquent model.

## 🚀 Installation & Usage

Implement the `UserStampable` contract and use the `HasUserStamps` trait.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\UserStamps\Contracts\UserStampable;
use HMsoft\Tools\Features\UserStamps\Traits\HasUserStamps;

class Article extends Model implements UserStampable
{
    use HasUserStamps;
}
```

⚙️ Customization (Overrides)

1. Multi-Guard Authentication (Custom User Source):
   If your application uses different guards (e.g., admin and web), you can override getStampUserId to define exactly who gets logged:

```PHP
public function getStampUserId(): int|string|null
{
return auth('admin')->id() ?? auth('web')->id();
}
```

2. Customizing Column Names:
   You can publish the global config using
    ```bash
    php artisan vendor:publish --tag="hmsoft-user-stamps-config",
    ```
    OR you can override columns directly on the model:

```PHP
class Article extends Model implements UserStampable
{
    use HasUserStamps;

    const CREATED_BY = 'author_id';
    const UPDATED_BY = 'editor_id';
    const DELETED_BY = 'archiver_id';
}
```
