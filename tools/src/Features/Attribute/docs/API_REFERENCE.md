# EAV API Reference

---

## Enums

### `InputTypeEnum`

| Case | Value |
|------|-------|
| `Text` | `text` |
| `Textarea` | `textarea` |
| `Select` | `select` |
| `MultiSelect` | `multi_select` |
| `Radio` | `radio` |
| `Checkbox` | `checkbox` |
| `Color` | `color` |
| `Number` | `number` |
| `Date` | `date` |
| `Boolean` | `boolean` |

Methods: `valueType()`, `isTranslatable()`, `hasOptions()`, `values()`

### `ValueTypeEnum`

| Case | Value |
|------|-------|
| `String` | `string` |
| `Text` | `text` |
| `Number` | `number` |
| `Date` | `date` |
| `Boolean` | `boolean` |
| `Option` | `option` |
| `Options` | `options` |

---

## Models

| Model | Table | Purpose |
|-------|-------|---------|
| `Attribute` | `eav_attributes` | Definition |
| `AttributeTranslation` | `eav_attribute_translations` | Label translations |
| `AttributeOption` | `eav_attribute_options` | Select options |
| `AttributeOptionTranslation` | `eav_attribute_option_translations` | Option labels |
| `AttributeCategory` | `eav_attribute_categories` | Category scope |
| `EavValue` | `eav_values` | Stored value |
| `EavValueTranslation` | `eav_value_translations` | Translatable values |
| `EavValueOption` | `eav_value_options` | Multi-select pivot |

---

## Traits

### `HasEavAttributes`

| Method | Description |
|--------|-------------|
| `eavValues()` | MorphMany → `EavValue` |
| `syncEavAttributes(array $payload)` | Sync all values |
| `getEavAttributesWithValues(?categoryType, ?categoryId)` | Load definitions + values for `$this` |
| `eavEntityType()` | Returns morph alias |
| `getEavFilterableExtra()` | Filter keys for AutoFilter |
| `getEavSortableExtra()` | Sort keys for AutoFilter |

---

## Services

### `EavValueSyncService`

```php
app(EavValueSyncService::class)->sync($model, $payload, $entityType = null);
```

### `EavFilterRegistrar`

```php
EavFilterRegistrar::filterableKeysForEntity('blogs');
EavFilterRegistrar::sortableKeysForEntity('blogs');
EavFilterRegistrar::searchableKeysForEntity('blogs');
EavFilterRegistrar::flushEntityCache('blogs');
```

### `GetObjectAttributesAction`

```php
$rows = app(GetObjectAttributesAction::class)->execute(
    entityType: 'blog',
    valuableId: 15,
    categoryType: 'blog_categories',
    categoryId: 2,
);

// Or via service:
app(AttributeService::class)->forObject('blog', 15);
```

### `EavValuePresenter`

```php
EavValuePresenter::present($eavValue, $attribute);
```

---

## Admin HTTP routes

Registered under `/api` when EAV is enabled. See `Features/Attribute/Routes/api.php`.

Typical pattern:

```
GET    /api/{scope}/{valuable_id}/attributes   ← definitions + values for object
GET    /api/{scope}/attributes
POST   /api/{scope}/attributes
GET    /api/{scope}/attributes/{id}
PUT    /api/{scope}/attributes/{id}
DELETE /api/{scope}/attributes/{id}
```

`scope` route param maps to `entity_type` (singularized).

---

## Artisan

```bash
php artisan vendor:publish --tag=cms-eav-config
php artisan migrate
```

---

## Service provider

`HMsoft\Tools\Features\Attribute\Providers\AttributeServiceProvider`

- Merges `cms_eav` config
- Loads migrations (when enabled)
- Registers admin routes
- Binds `EavValueSyncService` singleton
