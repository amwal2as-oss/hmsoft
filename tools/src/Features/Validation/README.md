# Validation Feature

Request-validation helpers. Message keys and route parameter names stay at the call site. This is **not** the Translations feature (Eloquent locale rows / `HasTranslations`).

## Directory Structure

```text
HMsoft/Tools/Features/Validation/
├── Support/
│   ├── LocaleValidation.php
│   └── RouteRecordIdResolver.php
├── Traits/
│   └── ResolvesUpdateRecordId.php
└── Providers/
    └── ValidationServiceProvider.php
```

## At-least-one locale field

```php
use HMsoft\Tools\Features\Validation\Support\LocaleValidation;
use Illuminate\Validation\Validator;

public static function withValidator(Validator $validator): void
{
    LocaleValidation::requireAtLeastOne($validator, 'name', 'brand::validation.at_least_one_name');
}
```

Also: `hasAtLeastOne`, `hasAtLeastOneOf`, `requireAtLeastOneOf`, `requireAtLeastOneEach`, `whenLocalesInRequest`. Pass your own trans key. The payload key is `locales`.

## Route id vs body `id`

```php
use HMsoft\Tools\Features\Validation\Support\RouteRecordIdResolver;

$brandId = RouteRecordIdResolver::resolve($payload, 'brand');
```

Route parameter first (bound model or scalar), then body `id`. `isNestedCreate()` when neither is present. `updateLocalesRules($isNestedCreate)` for `required` vs `sometimes` on `locales`.

Optional trait: `ResolvesUpdateRecordId::injectRouteRecordId($properties, 'brand')`.

## Not this feature

- Shop Store/Update Data classes and unique-on-translation-table rules
- `HasTranslations` / translation sync
- Mass patch / bulk delete
