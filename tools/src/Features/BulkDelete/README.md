# BulkDelete Feature

Validate and constrain bulk deletes where `ids` is either a non-empty list or `true` (every row in the query’s current scope).

Table names stay at the call site. This feature does not know about items, users, or any shop table.

## Directory Structure

```text
HMsoft/Tools/Features/BulkDelete/
├── Rules/
│   └── IdsOrAllRule.php
├── Support/
│   └── BulkDeleteIds.php
├── Providers/
│   └── BulkDeleteServiceProvider.php
└── lang/
    ├── en/bulk_delete.php
    └── ar/bulk_delete.php
```

## Payload

```json
{ "ids": [1, 2, 3] }
```

```json
{ "ids": true }
```

Send `true` as a JSON boolean, not the string `"true"`.

## Usage

```php
use HMsoft\Tools\Features\BulkDelete\Support\BulkDeleteIds;
use Spatie\LaravelData\Data;

class BulkDeleteWidgetData extends Data
{
    /** @param  list<int>|true  $ids */
    public function __construct(public readonly mixed $ids) {}

    public static function rules(): array
    {
        return BulkDeleteIds::rules('widgets');
    }
}
```

Do not type the property as `array|bool`: Spatie infers both `array` and `boolean` rules and validation always fails. Keep `mixed` and validate in `rules()`.

Custom `exists` (or other item rules):

```php
BulkDeleteIds::rulesWith([
    'integer',
    Rule::exists('about_us', 'id')->where(fn ($query) => $query->where('type', 'value')),
]);
```

Constrain a query, then keep the feature’s own delete style (`delete()`, `each->delete()`, …):

```php
BulkDeleteIds::constrain(Widget::query(), $data->ids)->delete();
```

`ids === true` leaves the query unfiltered (still whatever scopes you already applied). `isAll($ids)` when you need a special branch.

## Translations

Default message key is `bulk_delete.ids_invalid` (app `lang/{locale}/bulk_delete.php`).

Optional publish:

```
php artisan vendor:publish --tag=hmsoft-bulk-delete-lang
```

Package namespace fallback: `bulk_delete::bulk_delete.ids_invalid`. Inject a different key:

```php
BulkDeleteIds::rules('widgets', messageKey: 'your.key');
new IdsOrAllRule('your.key');
```

## Not this feature

- Feature-specific delete actions and DTOs
- Media’s `BulkDeleteMediaData` (array of ids only)
- Mass patch
