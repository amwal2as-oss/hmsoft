# Translation response fallback

Central locale fallback for API responses. Lives in `hmsoft/tools` so EAV and every consuming app share one engine.

**Package path:** `tools/src/Features/Translations`

---

## What it does

If the requested locale is `en` but `title` is `null` (or the locale row is missing), the field is taken from another language instead of returning null.

Order:

1. Requested locale (`app()->getLocale()` or an explicit `$locale` argument)
2. `cms_translations.fallback_locale` → else `cms_localization.fallback_locale` → else `app.fallback_locale`
3. Remaining `supported_locales`
4. Any other locale that still has a non-empty value

Empty means `null`, and blank strings when `treat_blank_string_as_empty` is true. `0` and `false` are kept.

The `translations` bag in the JSON stays honest (per-locale values, including nulls). Only display fields such as `title` / `name` / `label` receive fallback.

---

## Supported translation shapes

No model-specific branches. The same calls work for items, categories, EAV attributes, and option labels.

| Shape | Example |
|-------|---------|
| Parent model with loaded `translations` | `TranslatableResponse::resolve($item)` |
| Singular `translation` relation | used only when the collection is not loaded |
| Collection / list of rows | `[['locale' => 'ar', 'title' => '...'], ...]` |
| Locale-keyed map | `['ar' => ['title' => '...'], 'en' => ['title' => null]]` |
| Scalar map (EAV text values) | `['ar' => 'نص', 'en' => null]` |

Field names (`title`, `name`, `label`, `placeholder`, …) are detected from the row, or passed explicitly.

---

## API

```php
use HMsoft\Tools\Features\Translations\Support\TranslatableResponse;

$resolved = TranslatableResponse::resolve($model);
$title = $resolved['title'] ?? null;

$translations = TranslatableResponse::map($model, ['title', 'description']);

$title = TranslatableResponse::value($model, 'title');
$text  = TranslatableResponse::pickScalar(['en' => null, 'ar' => 'نص']);

$data = TranslatableResponse::process($data); // fill empty top-level keys from translations
```

### EAV

```php
TranslatableResponse::value($attribute, 'title');
TranslatableResponse::value($option, 'label');
TranslatableResponse::pickScalar($eavValue->translations->pluck('value_text', 'locale')->all());
```

`AttributeData` and `AttributeOptionData` already use this for `title` / `label`.

---

## Configuration

Publish:

```bash
php artisan vendor:publish --tag=cms-translations-config
```

Creates `config/cms_translations.php`. All keys are optional; defaults merge from the package.

| Key | Default | Purpose |
|-----|---------|---------|
| `fallback_locale` | `null` → `cms_localization.fallback_locale` → `app.fallback_locale` | Second locale to try |
| `supported_locales` | `null` → `cms_localization.supported_locales` | Extra locales after fallback |
| `ignored_keys` | `id`, `locale`, timestamps, userstamps | Not copied onto the response |
| `ignore_foreign_keys` | `true` | Skip `*_id` columns |
| `relations.many` | `translations` | Collection relation on the parent |
| `relations.one` | `translation` | Current-locale relation |
| `treat_blank_string_as_empty` | `true` | Fall back when the string is blank |

Do not hardcode project locales inside the resolver. Change them in config (or in `cms_localization.php`).

Example for this ecommerce app:

```php
// config/cms_translations.php
return [
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'ar'),
    'supported_locales' => ['ar', 'en'],
];
```

---

## Override the engine

Bind a custom class that implements `TranslationResolver`:

```php
use HMsoft\Tools\Features\Translations\Contracts\TranslationResolver;

public function register(): void
{
    $this->app->singleton(TranslationResolver::class, MyTranslationResolver::class);
}
```

`TranslatableResponse::*` will call your class. Extend `DefaultTranslationResolver` when you only need to tweak one rule.

---

## Provider

Registered from `HMsoftToolsServiceProvider`. You may also list it in `bootstrap/providers.php`:

```php
HMsoft\Tools\Features\Translations\Providers\TranslationsServiceProvider::class,
```
