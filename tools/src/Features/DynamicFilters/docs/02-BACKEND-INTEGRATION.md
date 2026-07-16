# DynamicFilters — Backend Integration Guide

Step-by-step guide to integrate Auto Filter and BaseData field pruning in a Laravel feature module.

> **Full reference:** [../README.md](../README.md) | **Checklist:** [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md)

---

## Setup checklist (start here)

DynamicFilters has **two parts**. Use this table to know what is required:

| Step | Auto Filter | fields / except |
|------|:-----------:|:---------------:|
| Model implements `AutoFilterable` + `IsAutoFilterable` | **Required** | — |
| `getFilterableExtra()` / `getSortableExtra()` | Recommended | — |
| `GetListAction` → `dynamicSearchFromRequest()` | **Required** | — |
| `{Feature}Data extends BaseData` | — | **Required** |
| `filterableCollect()` in controller `index()` | — | **Required** |
| Nested Data extends `BaseData` | — | If nested `fields`/`except` |
| `getFieldSelectionMapExtra()` on model | Optional (DB SELECT) | Uses Data property names |

### Full checklist

```
Part A — Auto Filter
[ ] 1. Model implements AutoFilterable + use IsAutoFilterable
[ ] 2. getRelationshipsExtra(), getFilterableExtra(), getSortableExtra()
[ ] 3. GetListAction → dynamicSearchFromRequest()
[ ] 4. Service::list() returns action result

Part B — fields / except
[ ] 5. Create {Feature}Data extends BaseData + fromModel()
[ ] 6. Nested relation Data classes extend BaseData (if needed)
[ ] 7. Controller index: {Feature}Data::filterableCollect($result['data'])
[ ] 8. Controller show: {Feature}Data::fromModel() (auto fields/except)
```

> **Part A alone** works (full JSON, dynamic queries).  
> **Part B requires a Data file** — without `BaseData` + `filterableCollect()`, `?fields=` and `?except=` have no effect on JSON.

---

## Prerequisites

- Model uses Eloquent
- Response uses Spatie Laravel Data DTOs
- Package: `hmsoft/tools` with DynamicFilters namespace

---

## Step 1 — Make the model filterable

```php
use HMsoft\Tools\Features\DynamicFilters\Contracts\AutoFilterable;
use HMsoft\Tools\Features\DynamicFilters\Traits\IsAutoFilterable;

class Decree extends Model implements AutoFilterable
{
    use IsAutoFilterable;

    // Optional: default eager loads applied in extraOperation
    public const DEFAULT_INCLUDES = ['translations', 'category.translations'];
}
```

The `IsAutoFilterable` trait auto-discovers table columns (cached) and excludes sensitive fields.

---

## Step 2 — Configure whitelists on the model

Override hook methods to expose relation fields and aliases.

```php
public function getRelationshipsExtra(): array
{
    return [
        'translations' => 'translations',
        'translation'  => 'translation',  // singular locale relation
        'category'     => 'category',
        'sector'       => 'sector',
    ];
}

public function getFilterableExtra(): array
{
    return [
        'translation.title',
        'category_id',
        'sector_id',
        'number',
        'date',
        'published_at',
    ];
}

public function getSortableExtra(): array
{
    return [
        'translation.title',
        'date',
        'number',
        'published_at',
    ];
}

public function getFieldSelectionMapExtra(): array
{
    return [
        'title'       => 'translation.title',
        'summary'     => 'translation.summary',
        'category_id' => 'category.id',
        'pdf_url'     => 'pdf_path',  // virtual alias
    ];
}

public function defineGlobalSearchRelatedAttributes(): array
{
    return [
        'translation' => ['title', 'summary', 'content'],
    ];
}

public function getVirtualFieldsDependenciesExtra(): array
{
    return [
        'pdf_url' => ['columns' => ['pdf_path']],
    ];
}
```

### CMS default filters (optional)

Applied when the frontend sends no filters:

```php
public function cmsDefaultFilters(): array
{
    return [
        'is_active' => (object) [
            'value'    => 1,
            'filterFn' => \HMsoft\Tools\Features\DynamicFilters\Enums\FilterFnsEnum::equals->value,
        ],
    ];
}

public function cmsDefaultSorts(): array
{
    return [
        (object) ['id' => 'created_at', 'desc' => true],
    ];
}
```

---

## Step 3 — Create GetListAction

```php
namespace App\Features\Decree\Decree\Actions;

use App\Features\Decree\Decree\Models\Decree;
use HMsoft\Tools\Features\DynamicFilters\Services\AutoFilterAndSortService;

class GetListAction
{
    public function execute(): array
    {
        return AutoFilterAndSortService::dynamicSearchFromRequest(
            model: Decree::class,
            extraOperation: function (\Illuminate\Database\Eloquent\Builder &$query) {
                $query->with(Decree::DEFAULT_INCLUDES);
            },
        );
    }
}
```

### Advanced options

```php
AutoFilterAndSortService::dynamicSearchFromRequest(
    model: Decree::class,

    // Rename frontend column ids before processing
    filterKeyMap: ['type' => 'category_id'],
    sortKeyMap:   ['name' => 'translation.title'],

    // Custom filter logic for computed/virtual columns
    customFilterHandlers: [
        'full_text' => function ($query, $filter) {
            $query->whereRaw('MATCH(title) AGAINST(?)', [$filter->value]);
        },
    ],

    // Cache results 5 minutes
    cacheDuration: 5,

    beforeOperation: function ($query, $ctx) {
        // $ctx: filterKeys, sortingKeys, mainTableAlias
    },
);
```

---

## Step 4 — Extend BaseData for response DTO (required for fields / except)

> **Without this step, `?fields=` and `?except=` will NOT trim the JSON response.**

Property names in the Data class **must match** the keys you pass in `?fields=` / `?except=`.

```php
use HMsoft\Tools\Features\DynamicFilters\Data\BaseData;

class DecreeData extends BaseData
{
    public function __construct(
        public ?int $id,
        public ?string $title,
        public Lazy|CategoryData $category,
        // ...
    ) {}

    public static function fromModel(Decree $decree): self
    {
        return new self(/* map fields */);
    }
}
```

Nested DTOs (`CategoryData`, etc.) should also extend `BaseData` if they need nested field pruning.

---

## Step 5 — Wire the controller

### List endpoint — `filterableCollect()` is required

```php
public function index(Request $request)
{
    $result = $this->decreeService->list(); // calls GetListAction

    // REQUIRED for ?fields= and ?except= to work on list responses
    $result['data'] = DecreeData::filterableCollect($result['data']);

    return CmsResponse::success(
        data: $result['data'],
        pagination: $result['pagination'],
    );
}
```

### Show endpoint — automatic

```php
public function show(Decree $decree)
{
    // BaseData::toArray() reads ?fields= / ?except= from the request automatically
    return CmsResponse::success(
        data: DecreeData::fromModel($this->decreeService->show($decree))
    );
}
```

### fields / except use case examples

```http
# Whitelist only needed columns
GET /api/decrees?fields=id,title,date,category.name

# Exclude heavy fields
GET /api/decrees?except=translations,pdf_path,content

# Combined
GET /api/decrees?fields=id,title,translations&except=translations.content

# Single item
GET /api/decrees/5?fields=id,title,pdf_url
```

---

## Step 6 — Custom filter/sort scopes (optional)

For complex logic, add Eloquent scopes on the model:

```php
// Filter id: "published_range" → scopeFilterPublishedRange
public function scopeFilterPublishedRange($query, ColumnFilterData $filter)
{
    [$from, $to] = $filter->value;
    $query->whereBetween('published_at', [$from, $to]);
}

// Sort id: "random" → scopeSortRandom
public function scopeSortRandom($query, string $direction)
{
    $query->inRandomOrder();
}
```

Register the filter id in `getFilterableExtra()` as `published_range`.

---

## Complete flow example

```
GET /api/decrees?page=1&perPage=10&globalFilter=law&fields=id,title,category.name
    &filters=eyJpZCI6ImlzX2FjdGl2ZSIs...&sorting=eyJpZCI6ImRhdGUiLC...

1. DecreeController::index()
2. DecreeService::list() → GetListAction
3. AutoFilterAndSortService::dynamicSearchFromRequest(Decree::class)
   - Parses page, filters, sorting, globalFilter, fields
   - Builds SQL with whitelisted columns only
   - Returns ['data' => Paginator, 'pagination' => [...]]
4. DecreeData::filterableCollect($result['data'])
   - Transforms models → DTOs
   - Prunes JSON to id, title, category.name
5. CmsResponse::success(...)
```

---

## FilterFnsEnum quick reference

| Operator | SQL behavior | Example value |
|----------|--------------|---------------|
| `equals` | `=` | `"1"`, `true` |
| `notEquals` | `!=` | `"draft"` |
| `contains` / `fuzzy` / `includesString` | `LIKE %val%` | `"hello"` |
| `startsWith` | `LIKE val%` | `"DEC"` |
| `endsWith` | `LIKE %val` | `"2024"` |
| `in` / `arrIncludes` | `WHERE IN (...)` | `[1, 2, 3]` |
| `notIn` | `WHERE NOT IN` | `[4, 5]` |
| `between` | `> from AND < to` | `["2024-01-01", "2024-12-31"]` |
| `betweenInclusive` | `>= from AND <= to` | `[10, 100]` |
| `greaterThan` / `lessThan` | `>` / `<` | `5` |
| `isNull` / `notIsNull` | `IS NULL` / `IS NOT NULL` | `null` |
| `empty` / `notEmpty` | `= ''` / `<> ''` | `null` |
| `dayEquals` / `monthEquals` / `yearEquals` | date parts | `"2024-03-15"` |

---

## Troubleshooting

| Problem | Check |
|---------|-------|
| **`fields` / `except` has no effect** | **`{Feature}Data extends BaseData`? `filterableCollect()` called in index?** |
| Field missing in pruned response | Property name in Data class must match `fields` param |
| Nested field empty | Relation loaded in DEFAULT_INCLUDES; nested Data extends BaseData |
| Filter ignored | Column in `defineFilterableAttributes()`? |
| Sort ignored | Column in `defineSortableAttributes()`? Relation sort on HasMany? (not supported via join) |
| Relation filter slow | BelongsTo/HasOne uses JOIN; HasMany uses whereHas (subquery) |
| Global search misses field | Add to `defineGlobalSearchRelatedAttributes()` |
| `fields` returns empty relation | Add relation to `defineRelationships()` + `getFieldSelectionMapExtra()` |
| Duplicate rows after filter | Join on HasMany — add `groupBy` in `extraOperation` or use whereHas |
| JSON still has excluded field | Use `except=field` on BaseData; DB `fields` only limits SELECT |

---

## Related files in this project

| File | Role |
|------|------|
| `app/Features/Decree/Decree/Models/Decree.php` | Full model configuration example |
| `app/Features/Decree/Decree/Actions/GetListAction.php` | Minimal list action |
| `app/Features/Decree/Decree/Controllers/DecreeController.php` | filterableCollect usage |
| `app/Features/News/News/Actions/GetListAction.php` | Another minimal example |
