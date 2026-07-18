# EAV Feature (Hybrid Dynamic Attributes)

Enterprise-grade Entity-Attribute-Value engine for Laravel CMS APIs. Attach custom typed fields to any model with translations, category scoping, and AutoFilter support.

**Package path:** `vendor/hmsoft/tools/tools/src/Features/Attribute`

---

## What it does

| Capability | Description |
|------------|-------------|
| **Morph values** | Store attribute values on any Eloquent model via `valuable_type` morph alias |
| **10 input types** | text, textarea, select, multi_select, radio, checkbox, color, number, date, boolean |
| **Translations** | Attribute labels, option labels, and translatable text values |
| **Category scoping** | Limit attributes to specific categories per entity |
| **Typed storage** | Indexed columns for fast filter/sort (`value_number`, `value_date`, etc.) |
| **AutoFilter keys** | Dynamic filter keys like `eav.weight`, `eav.material` |
| **Config control** | Enable/disable via `config/cms_eav.php` |

---

## Quick start

### 1. Register the provider

```php
// bootstrap/providers.php
HMsoft\Tools\Features\Attribute\Providers\AttributeServiceProvider::class,
```

### 2. Publish config

```bash
php artisan vendor:publish --tag=cms-eav-config
php artisan migrate
```

### 3. Add trait to a model

```php
use HMsoft\Tools\Features\Attribute\Traits\HasEavAttributes;

class Blog extends Model
{
    use HasEavAttributes;

    public function getMorphClass(): string
    {
        return 'blogs';
    }
}
```

### 4. Sync values on create/update

```php
$blog->syncEavAttributes([
    ['code' => 'weight', 'value' => 12.5],
]);
```

### 5. Load attributes with values (edit form)

```http
GET /api/blogs/15/attributes
```

Or in PHP:

```php
$fields = $blog->getEavAttributesWithValues();
```

---

## Documentation index

| Doc | Description |
|-----|-------------|
| [ARCHITECTURE.md](./docs/ARCHITECTURE.md) | Tables, typed storage, morph aliases |
| [DATABASE_SCHEMA.md](./docs/DATABASE_SCHEMA.md) | Full column reference + indexes |
| [CONFIGURATION.md](./docs/CONFIGURATION.md) | Config keys and env vars |
| [DEVELOPER_GUIDE.md](./docs/DEVELOPER_GUIDE.md) | Traits, sync payloads, types |
| [FRONTEND_REFERENCE.md](./docs/FRONTEND_REFERENCE.md) | **Frontend / mobile integration guide** |
| [POSTMAN_API.md](./docs/POSTMAN_API.md) | **HTTP examples with request/response bodies** |
| [API_REFERENCE.md](./docs/API_REFERENCE.md) | Models, enums, services |
| [FILTERING.md](./docs/FILTERING.md) | AutoFilterAndSortService integration |

---

## Core classes

| Class | Purpose |
|-------|---------|
| `HasEavAttributes` | Trait — morph relation + `syncEavAttributes()` |
| `EavValueSyncService` | Persist values on create/update |
| `GetObjectAttributesAction` | Load definitions + values for one object |
| `EavValuePresenter` | Resolves DB row → frontend `value` shape |
| `EavFilterRegistrar` | Registers dynamic filter keys per `entity_type` |
| `InputTypeEnum` | UI input types |
| `ValueTypeEnum` | Storage strategy |
| `Attribute` | EAV definition model (`eav_attributes`) |
| `EavValue` | Stored value row (`eav_values`) |

---

## Related HMsoft features

- **Translations** — same pattern for attribute/option labels
- **DynamicFilters** — `eav.{code}` filter keys
- **Active / SortNumber** — attribute definition management
- **Audit** — optional auditing on attribute definitions
