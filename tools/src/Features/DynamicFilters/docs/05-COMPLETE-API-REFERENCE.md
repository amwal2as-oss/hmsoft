# DynamicFilters — Complete API Reference

Supplement to the main [README](../README.md). Covers model hooks, relation behavior, alternative APIs, and edge cases not fully detailed elsewhere.

---

## Table of contents

1. [Model hooks reference (AutoFilterable)](#model-hooks-reference-autofilterable)
2. [Relation support matrix](#relation-support-matrix)
3. [Alternative service APIs](#alternative-service-apis)
4. [Configuration](#configuration)
5. [Complete feature module structure](#complete-feature-module-structure)
6. [JSON column filtering](#json-column-filtering)
7. [Virtual fields dependencies](#virtual-fields-dependencies)
8. [Default filters & sorts (cms vs Tools)](#default-filters--sorts-cms-vs-tools)
9. [Multiple filters on same column](#multiple-filters-on-same-column)
10. [Sensitive columns auto-excluded](#sensitive-columns-auto-excluded)
11. [BaseData + Spatie Lazy / Optional](#basedata--spatie-lazy--optional)
12. [Gzip encoding (frontend)](#gzip-encoding-frontend)
13. [Known limitations](#known-limitations)
14. [Unused / reserved parameters](#unused--reserved-parameters)

---

## Model hooks reference (AutoFilterable)

All methods available via `IsAutoFilterable` trait. Override the `*Extra()` hooks in your model.

| Method | Purpose | Override hook |
|--------|---------|---------------|
| `defineFilterableAttributes()` | Whitelist filter column ids | `getFilterableExtra()` |
| `defineSortableAttributes()` | Whitelist sort column ids | `getSortableExtra()` |
| `defineFieldSelectionMap()` | API name → DB/relation path | `getFieldSelectionMapExtra()` |
| `defineRelationships()` | Allowed relations for join/eager load | `getRelationshipsExtra()` |
| `defineGlobalSearchBaseAttributes()` | Main table search columns | Override directly |
| `defineGlobalSearchRelatedAttributes()` | Relation search columns | Override directly |
| `defineFullTextSearchableAttributes()` | MySQL MATCH AGAINST columns | Override directly |
| `defineVirtualFieldsDependencies()` | Virtual field → DB deps | `getVirtualFieldsDependenciesExtra()` |
| `definePrimaryKeyName()` | PK for GROUP BY (default: `getKeyName()`) | Override if PK ≠ `id` |
| `cmsDefaultFilters()` | Default filters when request empty | Override directly |
| `cmsDefaultSorts()` | Default sort when request empty | Override directly |
| `ToolsDefaultFilters()` | **Not wired** — use `cmsDefaultFilters()` | — |
| `ToolsDefaultSorts()` | **Not wired** — use `cmsDefaultSorts()` | — |
| `defineDateFilterableAttributes()` | Suggested date columns (informational) | Override directly |
| `defineSearchableRelations()` | Suggested searchable relations (informational) | Override directly |
| `getCachedTableColumns()` | Schema columns minus sensitive fields | `getAdditionalColumns()` |
| `getAdditionalColumns()` | Non-physical columns treated as table columns | Override directly |

### cmsDefaultFilters format

```php
public function cmsDefaultFilters(): array
{
    return [
        // Key = filter column id
        'is_active' => (object) [
            'value'    => 1,
            'filterFn' => \HMsoft\Tools\Features\DynamicFilters\Enums\FilterFnsEnum::equals->value,
        ],
        'deleted_at' => (object) [
            'value'    => null,
            'filterFn' => FilterFnsEnum::isNull->value,
        ],
    ];
}
```

Applied only when the request sends **no** `filters` param.

### cmsDefaultSorts format

```php
public function cmsDefaultSorts(): array
{
    return [
        (object) ['id' => 'sort_number', 'desc' => false],
        (object) ['id' => 'created_at',   'desc' => true],
    ];
}
```

---

## Relation support matrix

| Relation type | Filter | Sort | DB `fields` SELECT | Global search |
|---------------|:------:|:----:|:------------------:|:-------------:|
| BelongsTo | JOIN ✅ | JOIN ✅ | JOIN ✅ | whereHas ✅ |
| HasOne | JOIN ✅ | JOIN ✅ | JOIN ✅ | whereHas ✅ |
| HasMany | whereHas ✅ | ❌ skipped | eager load ✅ | whereHas ✅ |
| BelongsToMany | whereHas ✅ | ❌ skipped | eager load ✅ | whereHas ✅ |
| MorphOne | JOIN ✅ | JOIN ✅ | JOIN ✅ | whereHasMorph ✅ |
| MorphMany | whereHas ✅ | ❌ skipped | eager load ✅ | whereHasMorph ✅ |
| MorphTo | whereHasMorph ✅ | ❌ skipped | eager load ✅ | whereHasMorph ✅ |

**Notes:**
- JOIN = faster for BelongsTo/HasOne filters and sorts via `JoinManager`
- HasMany filter may return duplicate rows if joined — prefer `whereHas` (default) or add `groupBy` in `extraOperation`
- Relation must be registered in `getRelationshipsExtra()` for join/eager load paths

---

## Alternative service APIs

Besides `dynamicSearchFromRequest()`, you can use the service directly.

### buildQuery() — get Builder only

```php
$service = new AutoFilterAndSortService(Blog::class);
$query = $service->buildQuery(applySorting: true);

// Use for exports, custom pipelines, non-standard responses
$blogs = $query->limit(100)->get();
```

### dynamicFilter() — with DynamicFilterData DTO

```php
use HMsoft\Tools\Features\DynamicFilters\Data\DynamicFilterData;
use HMsoft\Tools\Features\DynamicFilters\Data\ColumnFilterData;
use HMsoft\Tools\Features\DynamicFilters\Enums\FilterFnsEnum;
use HMsoft\Tools\Features\DynamicFilters\Enums\PaginationFormateEnum;

$service = new AutoFilterAndSortService(Blog::class);

$data = new DynamicFilterData(
    page: '1',
    perPage: '20',
    paginationFormate: PaginationFormateEnum::separated,
    filters: [
        new ColumnFilterData('is_active', true, FilterFnsEnum::equals),
    ],
    sorting: [
        new \HMsoft\Tools\Features\DynamicFilters\Data\ColumnSortData('published_at', true),
    ],
    globalFilter: 'technology',
    columns: 'id,title,published_at',
);

$result = $service->dynamicFilter($data);
// ['data' => ..., 'pagination' => ...]
```

### count() — simple total (no filters)

```php
$service = new AutoFilterAndSortService(Blog::class);
$total = $service->count(); // Unfiltered count of primary keys
```

> For filtered count, use `count_only=1` in the request or `handleCountOnly()` via `dynamicFilter()` with `count_only: true`.

### filterKeyMap / sortKeyMap

Rename frontend column ids before whitelist check:

```php
AutoFilterAndSortService::dynamicSearchFromRequest(
    model: Blog::class,
    filterKeyMap: [
        'type'     => 'category_id',      // UI "type" → DB category_id
        'name'     => 'translation.title',
    ],
    sortKeyMap: [
        'title' => 'translation.title',
    ],
);
```

---

## Configuration

Optional config key (publish or add to `config/hmsoft-tools.php`):

```php
return [
    // Log generated SQL when APP_DEBUG=true
    'log_dynamic_filter_queries' => env('LOG_DYNAMIC_FILTER_QUERIES', false),
];
```

When enabled, queries are logged at debug level from `buildQuery()`.

**Built-in caches:**
- Table schema columns: `schema_columns_{table}` — 24 hours
- Search results: when `cacheDuration > 0` in `dynamicSearchFromRequest()`

---

## Complete feature module structure

Example layout matching this project (Blog feature):

```
app/Features/Blog/Blog/
├── Actions/
│   └── GetListAction.php          # dynamicSearchFromRequest()
├── Controllers/
│   └── BlogController.php         # filterableCollect() in index
├── Data/
│   └── BlogData.php               # extends BaseData
├── Models/
│   └── Blog.php                   # AutoFilterable + IsAutoFilterable
└── Service/
    └── BlogService.php            # list() → GetListAction

app/Features/Blog/Category/Data/
└── CategoryData.php               # extends BaseData (nested pruning)
```

**Minimum files for full DynamicFilters (A + B):**

| File | Part |
|------|------|
| `Models/{Feature}.php` | A |
| `Actions/GetListAction.php` | A |
| `Data/{Feature}Data.php` | B |
| `Controllers/{Feature}Controller.php` | A + B |

---

## JSON column filtering

If a dot-path root is a **physical column** (JSON field), not a relation:

```json
[{ "id": "metadata.locale", "value": "ar", "filterFns": "equals" }]
```

Backend converts `metadata.locale` → SQL `metadata->locale` on the main table.

Requires `metadata` to exist as a column on the model's table and be in `defineFilterableAttributes()`.

---

## Virtual fields dependencies

When `?fields=` requests a computed/virtual field, the service loads required DB columns/relations:

```php
public function getVirtualFieldsDependenciesExtra(): array
{
    return [
        // Simple: list of column/relation names
        'full_name' => ['first_name', 'last_name'],

        // Structured: explicit columns + relations
        'pdf_url' => [
            'columns'   => ['pdf_path'],
            'relations' => [],
        ],

        'active_price' => [
            'columns'   => ['price'],
            'relations' => ['tieredPrices'],
        ],
    ];
}
```

The virtual field name must also appear in `getFieldSelectionMapExtra()` if it maps to a DB path.

---

## Default filters & sorts (cms vs Tools)

| Method | Wired in ParsesRequests? | Use |
|--------|--------------------------|-----|
| `cmsDefaultFilters()` | ✅ Yes | CMS dashboard defaults |
| `cmsDefaultSorts()` | ✅ Yes | CMS dashboard defaults |
| `ToolsDefaultFilters()` | ❌ No | Legacy — not called |
| `ToolsDefaultSorts()` | ❌ No | Legacy — not called |

Always use **`cmsDefault*`** methods for default behavior.

---

## Multiple filters on same column

When the frontend sends multiple filter objects with the **same `id`**, they are grouped and all applied (typically AND within the same column group):

```json
[
  { "id": "published_at", "value": "2024-01-01", "filterFns": "greaterThanOrEqualTo" },
  { "id": "published_at", "value": "2024-12-31", "filterFns": "lessThanOrEqualTo" }
]
```

Equivalent to a date range using two filters on one column.

---

## Sensitive columns auto-excluded

`IsAutoFilterable::getCachedTableColumns()` automatically excludes these from auto filter/sort/search lists:

```
password, remember_token, api_token, access_token,
secret_key, credit_card, ssn, encrypted, salt
```

To add virtual columns not in the schema:

```php
protected function getAdditionalColumns(string $table): array
{
    return ['computed_status'];
}
```

To disable auto column discovery entirely:

```php
protected bool $autoIncludeAllColumns = false; // on model instance
```

---

## BaseData + Spatie Lazy / Optional

### Lazy relations

`Lazy::whenLoaded()` properties are resolved when the relation is eager-loaded. For `fields` pruning:

- Use the **property name** in `?fields=` (e.g. `sector`, not `sector.name`) to include the whole relation object
- Use dot notation `sector.name` to include only nested keys after the relation is loaded

### Optional properties

When using `Optional::create()` for unloaded relations, the key may be omitted from output entirely — `fields`/`except` operate on keys present in the serialized array.

### Nested Data types

Properties typed as `CategoryData` (extends `BaseData`) are treated as nested DTOs — Spatie `only()`/`except()` uses the **root key** only (e.g. `category`, not `category.name`) at the collection level; deep dot pruning is handled by `applyDeepArrayInclude()`.

---

## Gzip encoding (frontend)

For large filter payloads, compress before base64:

```typescript
import pako from 'pako';

function encodeDynamicParamGzip(data: unknown): string {
  const json = JSON.stringify(data);
  const compressed = pako.deflate(json);
  const binary = String.fromCharCode(...new Uint8Array(compressed));
  return btoa(binary);
}
```

Backend `smartDecode()` tries: base64 → JSON → gzinflate → gzdecode.

URL-safe base64 (`-`, `_`) is accepted on decode.

---

## Known limitations

| Limitation | Workaround |
|------------|------------|
| HasMany sort not supported | Sort by main table column or custom `scopeSort*` |
| Duplicate rows with joins | `groupBy` in `extraOperation` or use `whereHas` |
| `orFilters` param unused | Use `advanceFilter` with OR groups |
| `ToolsDefault*` not wired | Use `cmsDefaultFilters()` / `cmsDefaultSorts()` |
| `fields` affects both DB and JSON | Be aware of dual usage; use `except` for JSON-only exclusion |
| BelongsToMany filter uses subquery | Expected — may be slower than BelongsTo JOIN |
| Invalid `filterFns` in URL | Silently skipped (tryFrom) |

---

## Unused / reserved parameters

| Parameter | Status |
|-----------|--------|
| `DynamicFilterData::$orFilters` | Defined but **not processed** by the service — reserved for future OR filter groups |
| `ToolsDefaultFilters()` / `ToolsDefaultSorts()` | Interface methods exist but **not called** — use `cmsDefault*` |

---

## Missing filter operators (add to README table)

These exist in `FilterFnsEnum` and work in SQL:

| `filterFns` | SQL |
|-------------|-----|
| `notStartsWith` | `NOT LIKE val%` |
| `notEndsWith` | `NOT LIKE %val` |
| `arrIncludesAll` | `WHERE IN` (alias) |
| `arrIncludesSome` | `WHERE IN` (alias) |

---

## See also

- [../README.md](../README.md) — main documentation
- [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md) — integration checklist
- [00-ANALYSIS-AND-FIXES.md](./00-ANALYSIS-AND-FIXES.md) — bugs fixed & recommendations
