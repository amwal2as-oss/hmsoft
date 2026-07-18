# EAV Developer Guide

---

## Step 1 — Register provider and migrate

```php
// bootstrap/providers.php
HMsoft\Tools\Features\Attribute\Providers\AttributeServiceProvider::class,
```

```bash
php artisan vendor:publish --tag=cms-eav-config
php artisan migrate
```

---

## Step 2 — Add trait to domain model

```php
use HMsoft\Tools\Features\Attribute\Traits\HasEavAttributes;

class Blog extends Model implements AutoFilterable
{
    use IsAutoFilterable, HasEavAttributes;

    public function getMorphClass(): string
    {
        return 'blogs';
    }
}
```

`HasAttributes` is deprecated — use `HasEavAttributes`.

---

## Step 3 — Define attributes (admin)

Create attribute definitions scoped to `entity_type = 'blogs'`:

> **`code` is optional.** If omitted, the backend auto-generates a unique slug from the first locale `title`.

```http
POST /api/blogs/attributes
```

```json
{
  "input_type": "number",
  "is_filterable": true,
  "locales": [
    { "locale": "ar", "title": "الوزن" },
    { "locale": "en", "title": "Weight" }
  ]
}
```

With explicit code:

```json
{
  "entity_type": "blog",
  "code": "weight",
  "input_type": "number",
  "is_filterable": true,
  "is_sortable": true,
  "locales": [
    { "locale": "ar", "title": "الوزن" },
    { "locale": "en", "title": "Weight" }
  ]
}
```

---

## Step 4 — Sync values on entity save

```php
$blog->syncEavAttributes([
    ['code' => 'weight', 'value' => 12.5],
    ['code' => 'color', 'value' => '#FFD700'],
    ['code' => 'is_featured_custom', 'value' => true],
    ['code' => 'published_on', 'value' => '2026-07-18'],
    ['code' => 'tags', 'value' => [1, 4, 7]],
    ['code' => 'extra_note', 'value' => [
        'ar' => 'ملاحظة إضافية',
        'en' => 'Extra note',
    ]],
]);
```

### Payload formats

| Key | Required | Description |
|-----|----------|-------------|
| `code` | code **or** `attribute_id` | Preferred — stable |
| `attribute_id` | code **or** `attribute_id` | Legacy support |
| `value` | Yes | Shape depends on `input_type` |

---

## Step 5 — Read values

```php
$blog->load(['eavValues.attribute', 'eavValues.translations', 'eavValues.selectedOptions']);

foreach ($blog->eavValues as $row) {
    $code = $row->attribute->code;
    // inspect typed columns or translations
}
```

---

## Input type value shapes

| input_type | `value` shape | Example |
|------------|---------------|---------|
| `text` | `{ locale: string }` | `{ "ar": "...", "en": "..." }` |
| `textarea` | `{ locale: string }` | same |
| `select`, `radio` | option id | `3` |
| `multi_select`, `checkbox` | option id array | `[1, 3, 5]` |
| `color` | hex string | `"#FF0000"` |
| `number` | numeric | `12.5` |
| `date` | ISO date string | `"2026-07-18"` |
| `boolean` | bool | `true` |

---

## Category scoping

When creating attributes for category-specific fields:

```json
{
  "code": "gem_type",
  "input_type": "select",
  "categories": [
    { "category_type": "blog_categories", "category_id": 2 }
  ]
}
```

Validate in your action before sync:

```php
// App-level example
if ($attribute->categories->isNotEmpty()) {
    // ensure $blog->category_id is in allowed set
}
```

---

## AutoFilter integration

In your model, merge EAV filter keys:

```php
protected function getFilterableExtra(): array
{
    return array_merge(
        parent::getFilterableExtra(),
        $this->getEavFilterableExtra()
    );
}
```

Or register custom filter handlers in `GetListAction`:

```php
AutoFilterAndSortService::dynamicSearchFromRequest(
    model: Blog::class,
    customFilterHandlers: [
        'eav.weight' => fn ($q, $filter) => /* join eav_values */,
    ],
);
```

See [FILTERING.md](./FILTERING.md).

---

## Legacy compatibility

```php
// Old wrapper still works
(new SyncNestedAttributesAction())->execute($blog, $payload);

// Old trait alias
use HasAttributes; // → HasEavAttributes
```

---

## Common mistakes

| Mistake | Fix |
|---------|-----|
| FQCN as `valuable_type` | Use `getMorphClass()` alias |
| Duplicate `code` per entity | Enforced by DB unique |
| JSON for multi-select values | Pass array of option IDs — pivot handles storage |
| Missing morph class on model | Defaults to table name — override recommended |
| Stale filter keys | `EavFilterRegistrar::flushEntityCache()` |
