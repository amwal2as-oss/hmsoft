# DateTime — Developer Guide

> **Full reference:** [REFERENCE.md](./REFERENCE.md)

The package is **user-agnostic**. It converts UTC storage ↔ API timezone using tools you configure. Where the timezone comes from is entirely your app's responsibility.

---

## Architecture

```
Storage (UTC)                    API Output (resolved TZ)
─────────────────                ─────────────────────────────
MySQL datetime/timestamp    →    CmsDateTime::toApi()
Eloquent Carbon (UTC)       →    Date::serializeUsing()
Spatie Data DateTime        →    DateTimeInterfaceTransformer
Manual string fields        →    BaseData::formatApiDate() or CmsDateTime::toApi()
Audit old_values/new_values →    CmsDateTime::transformArray()
```

---

## In Data Classes (HMsoft BaseData)

### Preferred: use `DateTime` type

Spatie auto-converts on output:

```php
public readonly ?\DateTime $created_at,
public readonly ?\DateTime $updated_at,
```

### String datetime fields

Use the BaseData helper:

```php
use HMsoft\Tools\Features\DynamicFilters\Data\BaseData;

class NewsData extends BaseData
{
    public static function fromModel(News $news): self
    {
        return new self(
            published_at: self::formatApiDate($news->published_at),
            created_at: $news->created_at, // DateTime — auto
        );
    }
}
```

Or directly:

```php
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;

published_at: CmsDateTime::toApi($news->published_at),
```

---

## Saving API Input

```php
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;

$model->published_at = CmsDateTime::fromApi($data->published_at);
$model->save();
```

Spatie Data input uses `CmsDateTimeCast` automatically when creating Data from requests.

---

## Custom Timezone — Callback (recommended for simple cases)

Register once in `AppServiceProvider::boot()`:

```php
use HMsoft\Tools\Features\DateTime\Support\CmsDateTime;
use HMsoft\Tools\Features\DateTime\Support\DateTimeConfig;

CmsDateTime::resolveApiTimezoneUsing(function (): ?string {
    // Example: read from authenticated user's timezone column
    $user = auth()->user();
    $tz = $user?->timezone ?? null;

    if (is_string($tz) && DateTimeConfig::isValidTimezone($tz)) {
        return $tz;
    }

    return null; // falls back to APP_API_TIMEZONE
});
```

```env
CMS_DATETIME_RESOLVER=callback
```

The package never calls `auth()` — **your callback does**.

---

## Custom Timezone — Resolver Class (complex logic)

For settings tables, multi-tenant, Redis, external APIs:

```php
namespace App\Support;

use HMsoft\Tools\Features\DateTime\Contracts\DateTimeResolverInterface;
use HMsoft\Tools\Features\DateTime\Support\DateTimeConfig;
use Illuminate\Support\Facades\Cache;

final class AppDateTimeResolver implements DateTimeResolverInterface
{
    public function apiTimezone(): string
    {
        $tz = Cache::remember('app.api_timezone', 300, function () {
            return DB::table('settings')->where('key', 'timezone')->value('value');
        });

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

---

## Example: User Column (app-side only)

If you want per-user timezone from `users.timezone`:

1. Add migration in **your app** (not the package)
2. Use callback or class resolver as shown above
3. Set `CMS_DATETIME_RESOLVER=callback` or `class`

The package has no `user_column` mode — that logic lives in your app.

---

## Audit Integration

Package `AuditLogData` automatically uses:

```php
created_at: CmsDateTime::toApi($log->created_at),
old_values: CmsDateTime::transformArray($log->old_values),
new_values: CmsDateTime::transformArray($log->new_values),
```

Timezone follows the active resolver for the current request.

---

## Carbon Macro

```php
$model->created_at->toApi(); // same as CmsDateTime::toApi($model->created_at)
```

---

## API Surface

| Method | Description |
|--------|-------------|
| `CmsDateTime::toApi($value)` | UTC → resolved API timezone string |
| `CmsDateTime::fromApi($value)` | API input → UTC Carbon |
| `CmsDateTime::apiTimezone()` | Current resolved timezone |
| `CmsDateTime::transformArray($data)` | Recursive `_at` key conversion |
| `CmsDateTime::resolveApiTimezoneUsing(callable)` | Register callback resolver |
| `DateTimeConfig::isValidTimezone($tz)` | Validate PHP timezone identifier |

---

## Checklist for New Endpoints

- [ ] Use `DateTime` type in Data classes when possible
- [ ] Use `formatApiDate()` or `CmsDateTime::toApi()` for string datetime fields
- [ ] Use `CmsDateTime::fromApi()` when saving user-provided datetimes
- [ ] Do **not** use `toDateTimeString()` for API output
- [ ] Keep `birth_date` as `Y-m-d` without time conversion

---

## Testing

```bash
php artisan tinker
>>> HMsoft\Tools\Features\DateTime\Support\CmsDateTime::toApi(now());
>>> HMsoft\Tools\Features\DateTime\Support\CmsDateTime::apiTimezone();
```

```http
GET /api/datetime/now
GET /api/datetime/config
```
