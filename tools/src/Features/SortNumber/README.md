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

💡 How It Works
The trait hooks into the Eloquent creating event. Before the model is saved to the database, it checks if a value has been provided for the sort column. If the value is null, it executes a query to find the maximum existing value in that column and increments it by 1.
