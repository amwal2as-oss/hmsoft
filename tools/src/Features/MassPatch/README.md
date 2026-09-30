# MassPatch Feature

Apply the same field values to every row in a resource’s current query scope. This is **not** `updateAll` (per-row ids) and **not** bulk-delete.

The consuming app owns which models opt in, extra/denied fields, and any app-specific datetime casts.

## Directory Structure

```text
HMsoft/Tools/Features/MassPatch/
├── Actions/MassPatchAction.php
├── Contracts/MassPatchable.php
├── Contracts/MassPatchWriter.php
├── Data/MassPatchRequestData.php
├── Http/HandlesMassPatch.php
├── Providers/MassPatchServiceProvider.php
├── Support/
├── Traits/IsMassPatchable.php
├── Writers/EloquentMassPatchWriter.php
├── config/mass_patch.php
└── lang/{en,ar}/mass_patch.php
```

## Payload

A flat JSON object. No `ids`. Absent keys are left untouched.

```json
{ "is_active": true }
```

```json
{
  "is_active": true,
  "from_time": "2026-09-01T00:00:00+03:00",
  "to_time": null
}
```

Success: `{ "affected": N }`. Empty body or a list `[]` is 422.

## Usage

Model:

```php
use HMsoft\Tools\Features\MassPatch\Contracts\MassPatchable;
use HMsoft\Tools\Features\MassPatch\Traits\IsMassPatchable;

class Widget extends Model implements MassPatchable
{
    use IsMassPatchable;
}
```

Controller:

```php
use HMsoft\Tools\Features\MassPatch\Http\HandlesMassPatch;

class WidgetController
{
    use HandlesMassPatch;

    public function massPatch(Request $request): JsonResponse
    {
        return $this->massPatchResponse(new Widget(), $request);
    }
}
```

Nested resource: pass a query constraint as the third argument.

Optional model hooks: `getMassPatchableExtra()`, `getMassPatchDeniedExtra()`, `getMassPatchRulesExtra()`, `prepareMassPatch()`, `authorizeMassPatch()`, `constrainMassPatchQuery()`.

## Config

```
php artisan vendor:publish --tag=hmsoft-mass-patch-config
php artisan vendor:publish --tag=hmsoft-mass-patch-lang
```

| Key | Meaning |
|-----|---------|
| `datetime_cast_classes` | Extra FQCNs treated as datetime (app schedule casts). Default `[]` |
| `messages.*` | Trans keys for success / empty payload / unknown fields |
| `schedule_pairs` | Field pairs that get `after_or_equal` |

Default trans keys: `mass_patch.updated`, `mass_patch.values_required`, `mass_patch.unknown_fields`.

## Not this feature

- Shop `getMassPatchDeniedExtra` / `prepareMassPatch` on Item, User, Offer, …
- App datetime casts (register them in `datetime_cast_classes`)
- Bulk delete
