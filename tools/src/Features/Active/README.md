# Active Feature

The Active feature provides a clean, plug-and-play implementation for managing activation statuses (e.g., `is_active`) in your Eloquent models. It automatically applies a global scope to filter active records and provides local scopes for manual querying.

## 📂 Directory Structure

```text
HMsoft/Tools/Features/Active/
├── Contracts/
│   └── Activable.php
└── Traits/
    └── HasActiveScope.php
```

🚀 Installation & Usage
To implement the Active feature, your model must implement the Activable contract and use the HasActiveScope trait.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\Active\Contracts\Activable;
use HMsoft\Tools\Features\Active\Traits\HasActiveScope;

class Article extends Model implements Activable
{
use HasActiveScope;

    protected $fillable = ['title', 'is_active'];

}

```

By default, the trait assumes your database column is named is_active.

⚙️ Customization
Changing the Column Name
If your database column has a different name (e.g., status), you can override the default by defining a constant in your model:

```php
class Article extends Model implements Activable
{
    use HasActiveScope;

    const ACTIVE_COLUMN = 'status';
}
```

Alternatively, you can override the getActiveColumnName() method:

```php
public function getActiveColumnName(): string
{
return 'custom_active_column';
}
```

Disabling the Global Scope Dynamically
You can control whether the global scope should be applied by overriding the shouldApplyActiveScope() method in your model or globally setting a callable:

```php
public function shouldApplyActiveScope(): bool
{
    // Example: Disable scope for admins
    return ! auth()->user()?->isAdmin();
}
```

🛠️ Available Scopes
The trait provides local scopes to easily retrieve records regardless of the global scope.

```php
active(): Retrieves only active records.
```

```php
Article::withoutGlobalScope('active_scope')->active()->get();
inactive(): Retrieves only inactive records.
```

```php
Article::withoutGlobalScope('active_scope')->inactive()->get();
```
