# DateTime Feature (HMsoft Tools)

> **Package path:** `vendor/hmsoft/tools/tools/src/Features/DateTime`  
> **Config:** `config/cms_datetime.php`  
> **Core class:** `HMsoft\Tools\Features\DateTime\Support\CmsDateTime`

Store timestamps in **UTC**, return API responses in a configurable timezone (e.g. `Asia/Damascus`).

The package provides **tools only** — it does not know about users, database columns, or external storage. You customize timezone resolution via config, callback, or resolver class.

---

## Documentation

| Document | Description |
|----------|-------------|
| **[REFERENCE.md](./REFERENCE.md)** | **Complete reference — start here** |
| [CONFIGURATION.md](./CONFIGURATION.md) | Config keys & resolver modes |
| [API_REFERENCE.md](./API_REFERENCE.md) | HTTP endpoints |
| [DEVELOPER_GUIDE.md](./DEVELOPER_GUIDE.md) | Practical coding guide |

---

## Quick Start

```env
APP_TIMEZONE=UTC
APP_API_TIMEZONE=Asia/Damascus
CMS_DATETIME_ENABLED=true
CMS_DATETIME_RESOLVER=config
```

```php
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;

CmsDateTime::toApi($model->created_at);   // UTC → API ISO8601
CmsDateTime::fromApi($input);             // API → UTC Carbon
```

```bash
php artisan vendor:publish --tag=cms-datetime-config
```

```http
GET /api/datetime/config
GET /api/datetime/now
POST /api/datetime/convert
```

See **[REFERENCE.md](./REFERENCE.md)** for full API, architecture, and examples.
