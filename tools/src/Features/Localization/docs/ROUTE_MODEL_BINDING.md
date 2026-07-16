# Route Model Binding & Slugs

## Overview

Show endpoints can accept **numeric ID** or **translation slug**:

```
GET /api/news/12
GET /api/news/my-article-slug
```

Slug lookup is **locale-aware**: the resolver prefers the translation row matching the request locale.

## HMsoft integration

### 1. Locale source

Slug binding must know the request locale **before** or **during** route binding.

Use the package resolver (not raw `app()->getLocale()` when middleware may not have run):

```php
use HMsoft\Tools\Features\Localization\Support\LocaleResolver;

$locale = LocaleResolver::resolve();
```

`LocaleResolver` uses the same priority as `SetLocaleMiddleware` (route param → `Accept-Language` → session → fallback).

### 2. App trait example

This project uses `App\Traits\ResolvesRouteBindingByIdOrSlug` on models implementing `App\Contracts\HasTranslatableSlug`:

- `News`, `Blog`, `Service`, `Legislation`, `Decree`

Flow:

1. Numeric value → `WHERE id = ?`
2. String value → `WHERE translation.slug = ? AND translation.locale = ?` (current locale first)
3. Fallback → same slug in any locale

### 3. Routes

Remove `whereNumber()` from **public show** routes so slugs are allowed:

```php
Route::get('/{news}', [NewsController::class, 'show']);
Route::get('/{news}/download-pdf', [NewsController::class, 'downloadPdf']);
```

Keep `whereNumber()` on **admin mutate** routes (update/delete) if those should stay ID-only.

## Client example

```http
GET /api/news/government-update-2026
Accept: application/json
Accept-Language: ar
Authorization: Bearer …
```

Returns the news item whose **Arabic** translation has slug `government-update-2026`.

If the slug exists only in English, send `Accept-Language: en` or rely on the fallback query (any locale).

## Models without slug

Do **not** add `ResolvesRouteBindingByIdOrSlug` to models without a `slug` column on translations (categories, sectors, downloads, etc.). Those endpoints remain **ID-only**.

## Checklist

- [ ] `set.locale` prepended + priority before `SubstituteBindings` ([MIDDLEWARE_ORDER.md](./MIDDLEWARE_ORDER.md))
- [ ] Show routes allow non-numeric parameters
- [ ] Slug models implement `HasTranslatableSlug` + binding trait
- [ ] Client sends `Accept-Language` on every localized request
