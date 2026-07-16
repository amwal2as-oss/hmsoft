# Localization API Reference

## Middleware

| Alias | Class | Description |
|-------|-------|-------------|
| `set.locale` | `HMsoft\Tools\Features\Localization\Middleware\SetLocaleMiddleware` | Sets application locale per request |

Register alias automatically via `LocalizationServiceProvider`.

---

## LocaleResolver

**Namespace:** `HMsoft\Tools\Features\Localization\Support\LocaleResolver`

### `resolve(?Request $request = null): string`

Returns the resolved locale code. Does **not** call `App::setLocale()`.

Use when:

- Route model binding runs before middleware
- Unit / feature tests
- Queued jobs that receive a stored locale

### `apply(?Request $request = null): string`

Same as `resolve()`, then:

- `App::setLocale($locale)`
- Persists locale to session when session is available

Same behavior as `SetLocaleMiddleware`.

### `determineFromRequest(Request $request, ?array $supportedLocales, ?string $fallbackLocale): string`

Low-level helper with explicit locale lists (testing / custom pipelines).

---

## Configuration keys

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `cms_localization.supported_locales` | `string[]` | `['ar','en']` | Allowed locale codes |
| `cms_localization.fallback_locale` | `string` | `ar` | Used when detection fails |
| `cms_localization.detectors.route_parameter` | `string` | `locale` | Route param name |
| `cms_localization.detectors.header` | `string` | `Accept-Language` | Header name |
| `cms_localization.detectors.session_key` | `string` | `locale` | Session key |

Publish tag: `cms-localization-config`

---

## HTTP headers (clients)

| Header | Required | Example | Notes |
|--------|----------|---------|-------|
| `Accept-Language` | Recommended | `ar` or `ar-SA,ar;q=0.9` | Primary API locale detector |
| `Accept` | Optional | `application/json` | Standard |

First two characters of each language tag are matched against `supported_locales` (`en-US` → `en`).

---

## Artisan

```bash
php artisan vendor:publish --tag=cms-localization-config
```

---

## Service provider

`HMsoft\Tools\Features\Localization\Providers\LocalizationServiceProvider`

- Merges default config from package
- Registers `set.locale` middleware alias
- Publishes `config/cms_localization.php`

---

## Translations trait interaction

`HMsoft\Tools\Features\Translations\Traits\HasTranslations`:

```php
public function translation(): HasOne
{
    return $this->hasOne(...)
        ->where('locale', app()->getLocale());
}
```

Ensure `set.locale` runs before controllers (and before binding when slugs are locale-scoped) so `translation()` returns the correct row.
