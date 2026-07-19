# DateTime — Configuration

> **Full reference:** [REFERENCE.md](./REFERENCE.md)  
> Config file: `config/cms_datetime.php`  
> Publish tag: `cms-datetime-config`

The package does **not** read users, columns, or external storage. It only provides three resolver modes; your app supplies the logic when needed.

---

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `CMS_DATETIME_ENABLED` | `true` | Master switch |
| `CMS_DATETIME_REGISTER_ROUTES` | `true` | Register `/api/datetime/*` routes |
| `APP_TIMEZONE` | `UTC` | Storage timezone (DB + app) |
| `APP_API_TIMEZONE` | `Asia/Damascus` | Default / fallback API output timezone |
| `CMS_DATETIME_RESOLVER` | `config` | `config` \| `callback` \| `class` |
| `CMS_DATETIME_RESOLVER_CLASS` | — | FQCN implementing `DateTimeResolverInterface` |
| `DB_TIMEZONE` | `+00:00` | MySQL session timezone (app `config/database.php`) |

---

## Resolvers

### `config` (default)

Always returns `APP_API_TIMEZONE`. Best for single-region apps.

```env
CMS_DATETIME_RESOLVER=config
APP_API_TIMEZONE=Asia/Damascus
```

### `callback`

Register a callback in your `AppServiceProvider`. The callback returns a timezone string (or `null` to fall back to `APP_API_TIMEZONE`).

```php
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;

CmsDateTime::resolveApiTimezoneUsing(function (): ?string {
    // Your logic: user column, settings table, Redis, request header, tenant, etc.
    return Cache::get('tenant:' . tenant()->id . ':timezone');
});
```

```env
CMS_DATETIME_RESOLVER=callback
```

### `class`

Provide a resolver class in your app:

```php
namespace App\Support;

use HMsoft\Tools\Features\DateTime\Contracts\DateTimeResolverInterface;
use HMsoft\Tools\Features\DateTime\Support\DateTimeConfig;

final class AppDateTimeResolver implements DateTimeResolverInterface
{
    public function apiTimezone(): string
    {
        $tz = DB::table('business_settings')->value('timezone');

        if (is_string($tz) && DateTimeConfig::isValidTimezone($tz)) {
            return $tz;
        }

        return DateTimeConfig::defaultApiTimezone();
    }
}
```

```env
CMS_DATETIME_RESOLVER=class
CMS_DATETIME_RESOLVER_CLASS=App\\Support\\AppDateTimeResolver
```

> `CMS_DATETIME_RESOLVER=custom` is still accepted as an alias for `class` (backward compatible).

---

## Automatic Wiring

When `CMS_DATETIME_ENABLED=true`, the service provider registers:

1. `Date::serializeUsing()` — Eloquent JSON dates → API timezone
2. `Carbon::toApi()` macro
3. Spatie `data.date_timezone` + `CmsDateTimeCast` for input

No extra setup required beyond config (and optional callback/class resolver).

---

## Date-Only Fields

Fields like `birth_date` with format `Y-m-d` are **not** timezone-converted (by design).

Only datetime fields (typically keys ending in `_at`) are transformed.

---

## Disabling

```env
CMS_DATETIME_ENABLED=false
```

Disables routes and automatic serialization hooks.
