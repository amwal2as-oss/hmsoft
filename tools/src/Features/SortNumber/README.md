# SortNumber Feature

The Sort Number feature automatically assigns an incremental sorting value to your Eloquent models upon creation. It ensures that new records are placed at the end of the list automatically without requiring manual input.

## 📂 Directory Structure

```text
HMsoft/Tools/Features/SortNumber/
├── Contracts/
│   └── Sortable.php
└── Traits/
    └── HasSortNumber.php
```

🚀 Installation & Usage
To implement the Sort Number feature, your model must implement the Sortable contract and use the HasSortNumber trait.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\SortNumber\Contracts\Sortable;
use HMsoft\Tools\Features\SortNumber\Traits\HasSortNumber;

class Category extends Model implements Sortable
{
    use HasSortNumber;

    protected $fillable = ['name', 'sort_number'];
}

```

When you create a new Category, the sort_number will automatically be calculated as MAX(sort_number) + 1.

⚙️ Customization
Changing the Column Name
By default, the trait looks for a column named sort_number. If your table uses a different column name (e.g., order_index), you can customize it in one of three ways:

1. Using a Constant (Recommended):

```php
class Category extends Model implements Sortable
{
    use HasSortNumber;

    const SORT_COLUMN = 'order_index';
}
```

2. Using a Class Property:

```php
class Category extends Model implements Sortable
{
    use HasSortNumber;

    public $sortNumberColumn = 'order_index';
}
```

3. Overriding the Contract Method:

```php
class Category extends Model implements Sortable
{
    use HasSortNumber;

    public function getSortNumberColumnName(): string
    {
        return 'order_index';
    }
}
```

Overriding Context Scoping (scopeSortByContext)
When working with complex relational layers (such as Polymorphic Models like a unified Faq component), sorting should be calculated within explicit contexts (e.g., specific to an owner_id and owner_type).

You can override the scopeSortByContext method to surgically declare your own custom index boundaries:

```php
<?php

namespace App\Features\Faq\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use HMsoft\Tools\Features\SortNumber\Contracts\Sortable;
use HMsoft\Tools\Features\SortNumber\Traits\HasSortNumber;

class Faq extends Model implements Sortable
{
    use HasSortNumber;

    protected $fillable = ['owner_id', 'owner_type', 'sort_number'];

    /**
     * 🛠️ SURGICAL OVERRIDE:
     * Restrict incremental sequential calculations inside the polymorphic boundary.
     */
    public function scopeSortByContext(Builder $query): Builder
    {
        $ownerType = $this->getAttribute('owner_type');
        $ownerId = $this->getAttribute('owner_id');

        if ($ownerType && $ownerId) {
            // Context Shape 1: Record specific isolation (e.g., Blog #15)
            return $query->where('owner_type', $ownerType)->where('owner_id', $ownerId);
        } elseif ($ownerType && !$ownerId) {
            // Context Shape 2: Model wide isolation (e.g., All general Blogs)
            return $query->where('owner_type', $ownerType)->whereNull('owner_id');
        }

        // Context Shape 3: Global system FAQs fallback
        return $query->whereNull('owner_type')->whereNull('owner_id');
    }
}
```

💡 How It Works
The trait hooks into the Eloquent creating event. Before the model is saved to the database, it checks if a value has been provided for the sort column. If the value is null, it executes a query to find the maximum existing value in that column and increments it by 1.
