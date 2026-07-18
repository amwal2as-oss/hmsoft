# EAV Configuration

---

## Publish config

```bash
php artisan vendor:publish --tag=cms-eav-config
```

Creates: `config/cms_eav.php`

---

## Config keys

| Key | Env | Default | Description |
|-----|-----|---------|-------------|
| `enabled` | `CMS_EAV_ENABLED` | `true` | Master switch |
| `filter_prefix` | `CMS_EAV_FILTER_PREFIX` | `eav` | AutoFilter key prefix |
| `definition_cache_ttl` | `CMS_EAV_DEFINITION_CACHE_TTL` | `3600` | Cache TTL for filter key registry |
| `tables.*` | — | `eav_*` | Override table names if needed |

---

## Example `.env`

```env
CMS_EAV_ENABLED=true
CMS_EAV_FILTER_PREFIX=eav
CMS_EAV_DEFINITION_CACHE_TTL=3600
```

---

## `EavConfig` helper

```php
use HMsoft\Tools\Features\Attribute\Support\EavConfig;

EavConfig::isEnabled();
EavConfig::filterPrefix();          // 'eav'
EavConfig::filterKey('weight');      // 'eav.weight'
EavConfig::definitionCacheTtl();
EavConfig::table('values');        // 'eav_values'
```

---

## Disable EAV

```env
CMS_EAV_ENABLED=false
```

When disabled:
- Migrations/routes not loaded
- `syncEavAttributes()` no-ops
- Filter registrar returns empty arrays

---

## Cache invalidation

After creating/updating/deleting attribute definitions:

```php
use HMsoft\Tools\Features\Attribute\Services\EavFilterRegistrar;

EavFilterRegistrar::flushEntityCache('blogs');
```

`CreateAction` does this automatically.
