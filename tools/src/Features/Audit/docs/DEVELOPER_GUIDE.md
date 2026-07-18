# Audit Developer Guide

Step-by-step reference for developers adding or maintaining audited CMS domains.

---

## Prerequisites

- `AuditServiceProvider` registered in `bootstrap/providers.php`
- `CMS_AUDIT_ENABLED=true` in `.env`
- Queue worker running (`php artisan queue:work`)
- `audit_logs` table migrated

---

## Step 1 — Add traits to your model

```php
<?php

namespace App\Features\Blog\Blog\Models;

use HMsoft\Tools\Features\Audit\Traits\Auditable;
use HMsoft\Tools\Features\Audit\Traits\HasDynamicSyncAndAudit;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use Auditable, HasDynamicSyncAndAudit;

    protected $table = 'blogs';
    protected $guarded = ['id'];
}
```

| Trait | When to use |
|-------|-------------|
| `Auditable` | Always, for single-model CRUD auditing |
| `HasDynamicSyncAndAudit` | When you sync child rows or pivot tables via `syncRelation()` |

You can use `Auditable` alone if the model has no relation sync auditing needs.

---

## Step 2 — Define a morph alias (recommended)

Override `getMorphClass()` so the ledger stores a stable short alias instead of a table name or FQCN:

```php
public function getMorphClass(): string
{
    return 'blogs';
}
```

**If omitted**, the morph alias defaults to `$model->getTable()` (e.g. `news_categories`).

### Why it matters

- Consistent `auditable_type` in `audit_logs`
- Works with `Relation::morphMap()` used by verification commands
- Cleaner API responses in audit log listings

After adding or changing morph aliases:

```bash
php artisan cache:clear
```

---

## Step 3 — Use Eloquent for all mutations

The `Auditable` trait hooks **Eloquent model events only**.

### Correct patterns

```php
// Create
Blog::create($data);

// Update
$blog->update(['title' => 'New title']);

// Delete
$blog->delete();

// Mass assignment via model instance
$blog->fill($data)->save();
```

### Patterns that bypass audit

```php
// ❌ Raw query builder
DB::table('blogs')->where('id', 1)->update(['title' => 'X']);

// ❌ Suppressed events
Blog::withoutEvents(fn () => $blog->update($data));

// ❌ Direct DB in migrations/seeders (expected — not audited)
```

---

## Step 4 — Audit relation sync with `syncRelation()`

For models using `HasDynamicSyncAndAudit`:

```php
$blog->syncRelation(
    relationName: 'categories',
    incomingData: $categoryIds,           // BelongsToMany: array of IDs
);
```

### HasMany with match key

```php
$item->syncRelation(
    relationName: 'pricings',
    incomingData: $validPricings,
    matchKey: 'currency_id',
    callbacks: [
        'beforeSave' => function (array $data, $existing) {
            // Transform row before save
            return $data;
        },
        'afterSave' => function (array $data) {
            // Side effects after each row saved
        },
    ]
);
```

### Supported relation types

| Type | `matchKey` | Sync behavior |
|------|------------|---------------|
| `BelongsToMany` | Not required | Calls `$relation->sync($incomingData)` |
| `HasMany` | **Required** | Deletes missing rows, `updateOrCreate` per item |

### What gets logged

When relation data changes, one audit entry is written with:

- Event: `created` (if old snapshot empty) or `updated`
- `old_values` / `new_values`: parent model attributes + relation key containing full relation snapshots

Example snapshot shape:

```json
{
  "id": 5,
  "title": "Gold bar",
  "pricings": [
    {"currency_id": 1, "buy": 100, "sale": 110}
  ]
}
```

---

## Step 5 — Hidden and sensitive fields

`Auditable` automatically excludes attributes listed in `$hidden`:

```php
protected $hidden = ['password', 'remember_token', 'two_fa_secret'];
```

These fields never appear in `old_values` / `new_values`.

For auth failures, only `attempted_email` is logged — **passwords are never stored**.

---

## Step 6 — Context captured per audit row

Every dispatched job includes:

| Field | Source |
|-------|--------|
| `user_id` | `auth()->id()` |
| `ip_address` | `Request::ip()` |
| `user_agent` | `Request::userAgent()` |
| `session_id` | `session()->getId()` |

In queue/console contexts, `user_id` may be `null` if no authenticated user.

---

## Real project examples

### Blog category pivot sync

```php
// app/Features/Blog/Blog/Actions/UpdateAction.php
$blog->syncRelation('categories', $data->category_ids);
$blog->syncRelation('downloads', $data->download_ids);
```

### Item pricing (HasMany + callbacks)

```php
// app/Features/Item/Item/Actions/SyncItemPricingAction.php
$item->syncRelation(
    relationName: 'pricings',
    incomingData: $validPricings,
    matchKey: 'currency_id',
    callbacks: [ /* beforeSave, afterSave */ ]
);
```

---

## Models in this project using audit traits

Common pattern across CMS domains:

- News, Blog, Decree, Legislation, Service, Sector, Statistic, PrivacyPolicy, TickerNews, Attribute, Requirement, Step, NewsGallery, etc.

Check any model under `app/Features/**/Models/` for:

```php
use Auditable, HasDynamicSyncAndAudit;
```

---

## Authentication auditing (no trait required)

Login/logout/failed login are logged automatically when `log_authentication` is enabled.

Uses morph alias `'users'` — ensure `User` model defines:

```php
public function getMorphClass(): string
{
    return 'users';
}
```

The `User` model does **not** need the `Auditable` trait for auth events.

---

## Testing audited features

### Disable audit in a test

```php
config(['cms_audit.enabled' => false]);
```

### Assert audit was written

```php
use HMsoft\Tools\Features\Audit\Models\AuditLog;

$this->assertDatabaseHas('audit_logs', [
    'auditable_type' => 'blogs',
    'auditable_id'   => $blog->id,
    'event'          => 'updated',
]);
```

### Run verification in CI

```bash
php artisan audit:verify
php artisan audit:state-match
```

---

## Common mistakes

| Mistake | Symptom | Fix |
|---------|---------|-----|
| No queue worker | No rows in `audit_logs` | Start `queue:work` |
| Audit disabled | Traits silently no-op | Set `CMS_AUDIT_ENABLED=true` |
| Raw SQL updates | State mismatch warnings | Use Eloquent |
| Forgot cache clear | Wrong morph alias in logs | `php artisan cache:clear` |
| Missing `matchKey` on HasMany | `InvalidArgumentException` | Pass `matchKey` to `syncRelation()` |
| `HasDynamicSyncAndAudit` without relation method | Silent return | Verify relation name exists |

---

## Decision tree: which trait do I need?

```
Does the model change via normal CRUD?
  └─ Yes → add Auditable

Do you bulk-sync HasMany or BelongsToMany relations?
  └─ Yes → add HasDynamicSyncAndAudit and use syncRelation()

Do you only need login tracking?
  └─ No traits on User — auth listener handles it
```

See also: [ARCHITECTURE.md](./ARCHITECTURE.md) | [CONFIGURATION.md](./CONFIGURATION.md)
