# Dynamic Filters & Sorting Engine

This engine is a highly advanced (Surgical Engine) system for handling database queries in Laravel. It intelligently manages filtering, sorting, bringing only the specified columns (Surgical Selects), and loading relationships (Eager Loading) to prevent `N+1` issues natively, while fully supporting global search and Magic Scopes.

---

## 🚀 1. Installation & Setup (Plug & Play)

To make any model fully compatible with the Dynamic Filters engine, all you have to do is implement the `AutoFilterable` contract and use the `IsAutoFilterable` trait.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\DynamicFilters\Contracts\AutoFilterable;
use HMsoft\Tools\Features\DynamicFilters\Traits\IsAutoFilterable;

class Product extends Model implements AutoFilterable
{
    use IsAutoFilterable;

    protected $fillable = ['name', 'price', 'sku', 'is_active'];
}
```

**Default Behavior:** Once the trait is added, the engine will **automatically** read the table columns (utilizing caching once a day to reduce DB load), making all physical columns filterable, sortable, and searchable globally. It automatically excludes sensitive fields (like `password`, `remember_token`).

---

## 🛠️ 2. Use Cases & Customization

The engine is designed to be highly flexible to meet the most complex Frontend requirements. You can control everything by overriding the custom `Extra` methods.

### Case 1: Custom Filterable & Sortable Attributes

If you want to allow the frontend to filter or sort based on fields that do not exist directly in the table (e.g., nested relationship fields or JSON paths), use the `Extra` methods:

```php
    /**
     * Additional fields allowed for filtering
     */
    protected function getFilterableExtra(): array
    {
        return [
            'category.name',       // Filter based on a category name (relationship)
            'translations.title',  // Filter across translation tables
            'options->color'       // Filter inside a JSON column
        ];
    }

    /**
     * Additional fields allowed for sorting
     */
    protected function getSortableExtra(): array
    {
        return [
            'category.sort_index', // Sort based on a field in a related table
            'price_after_discount' // Sort by a computed field (Requires a Magic Scope)
        ];
    }

```

### Case 2: Field Selection Map (Aliasing)

Sometimes the Frontend sends a different field name (Alias) for security or organizational reasons, and you need to map it to the actual path in the database.

```php
    /**
     * Map aliases to real database columns or relation paths
     */
    protected function getFieldSelectionMapExtra(): array
    {
        return [
            'author_name' => 'user.profile.full_name', // Nested relationship path
            'status'      => 'is_active',              // Direct column renaming
            'brand'       => 'manufacturer_id'
        ];
    }

```

### Case 3: Eager Loading Protection (Preventing N+1)

To prevent `N+1` queries when the user requests fields from relationships (e.g., `fields=id,name,category.title`), you must explicitly tell the engine which relationships are permitted to be loaded (Eager Load). The engine will automatically apply `with()` for these relations.

```php
    /**
     * Relationships permitted to be eager-loaded dynamically
     */
    protected function getRelationshipsExtra(): array
    {
        return [
            'category' => 'category',
            'tags'     => 'tags',
            'creator'  => 'user', // If Frontend requests 'creator', the engine loads the 'user' relation
        ];
    }

```

### Case 4: Virtual Fields Dependencies (The Surgical Select) 🔥

**This is the most powerful feature:** When a model has a computed accessor like `getProfitAttribute()`, and the frontend requests to fetch this field `fields=profit`, the query will fail because `profit` is not a database column.
Here, you tell the engine exactly which **physical** columns to fetch from the DB so your accessor can function correctly!

```php
    /**
     * Dependencies of computed fields from columns and relations
     */
    protected function getVirtualFieldsDependenciesExtra(): array
    {
        return [
            // To calculate profit, the engine needs to fetch price and cost from the DB
            'profit'        => ['price', 'cost'],

            // To get the author's avatar, we need the ID, and we must eager load the author's media relation
            'author_avatar' => [
                'columns'   => ['author_id'],
                'relations' => ['author.media']
            ],
        ];
    }

```

### Case 5: Global Search Configuration

This controls how the Global Search feature operates when the `globalFilter` parameter is passed.

```php
    // 1. Standard text fields to search via (LIKE %...%)
    public function defineGlobalSearchBaseAttributes(): array
    {
        return ['name', 'sku'];
    }

    // 2. High-performance Full-Text Search (MATCH AGAINST)
    public function defineFullTextSearchableAttributes(): array
    {
        return ['description']; // Requires the column to have a FULLTEXT index in MySQL
    }

    // 3. Search inside related tables (Engine automatically applies whereHas or whereHasMorph)
    public function defineGlobalSearchRelatedAttributes(): array
    {
        return [
            'category'     => ['name', 'slug'],
            'translations' => ['title']
        ];
    }

```

---

## 🎩 3. Magic Scopes (Overriding Engine Behavior)

If the engine's default behavior (which automatically builds `Where` and `OrderBy` clauses) doesn't cover an exceptional or highly complex case, you can intervene surgically using **Magic Scopes**.

The engine relies on a Naming Convention: `scopeFilter[StudlyColumnName]` or `scopeSort[StudlyColumnName]`.

### Example 1: Custom Filter

Suppose the Frontend sends a filter `{"id": "active_status", "value": true}` and this field doesn't physically exist, but rather represents complex logic:

```php
    use Illuminate\Database\Eloquent\Builder;
    use HMsoft\Tools\Features\DynamicFilters\Data\ColumnFilterData;

    /**
     * The engine will automatically trigger this Scope instead of the default behavior
     * when filter.id = active_status
     */
    public function scopeFilterActiveStatus(Builder $query, ColumnFilterData $filter)
    {
        if ($filter->value === true) {
            $query->whereNotNull('published_at')->where('is_banned', false);
        } else {
            $query->whereNull('published_at');
        }
    }

```

### Example 2: Custom Sort (Math/Raw SQL)

If the user wants to sort based on geographic "Distance" using a raw SQL query:

```php
    /**
     * This will be triggered when sort.id = distance
     */
    public function scopeSortDistance(Builder $query, string $direction)
    {
        $lat = request()->header('X-Lat', 0);
        $lng = request()->header('X-Lng', 0);

        // The engine will strictly respect this custom sort!
        $query->orderByRaw("ST_Distance(coordinates, POINT($lng, $lat)) $direction");
    }

```

---

## 🔧 4. Controller Usage (Outside the Model)

How is this engine invoked and executed from within Controllers?

```php
namespace App\Http\Controllers;

use App\Models\Product;
use HMsoft\Tools\Features\DynamicFilters\Services\AutoFilterAndSortService;

class ProductController
{
    public function index()
    {
        // Just one line is enough to summon all this magic!
        $result = AutoFilterAndSortService::dynamicSearchFromRequest(
            model: Product::class,

            // You can inject additional conditions that the Frontend cannot override (Security)
            extraOperation: function ($query) {
                $query->where('store_id', auth()->user()->store_id);
            },

            // Excellent Caching option: Cache the results of this query for 10 minutes
            // (Cache key is automatically generated based on the applied filters/sorts)
            cacheDuration: 10
        );

        return response()->json($result);
    }
}

```
