# Audit Configuration

All audit behavior is controlled through `config/cms_audit.php`. The package merges defaults from the vendor path; publish to override in your app.

---

## Publish config

```bash
php artisan vendor:publish --tag=cms-audit-config
```

Creates: `config/cms_audit.php`

After changes:

```bash
php artisan config:clear
```

---

## Config file structure

```php
<?php

return [
    'enabled' => env('CMS_AUDIT_ENABLED', true),

    'log_model_events'     => env('CMS_AUDIT_LOG_MODEL_EVENTS', true),
    'log_relation_sync'    => env('CMS_AUDIT_LOG_RELATION_SYNC', true),
    'log_authentication'   => env('CMS_AUDIT_LOG_AUTHENTICATION', true),
    'register_routes'      => env('CMS_AUDIT_REGISTER_ROUTES', true),
    'load_migrations'      => env('CMS_AUDIT_LOAD_MIGRATIONS', true),
];
```

---

## Configuration keys

| Key | Env variable | Default | Applies when |
|-----|--------------|---------|--------------|
| `enabled` | `CMS_AUDIT_ENABLED` | `true` | Always — master switch |
| `log_model_events` | `CMS_AUDIT_LOG_MODEL_EVENTS` | `true` | `enabled=true` |
| `log_relation_sync` | `CMS_AUDIT_LOG_RELATION_SYNC` | `true` | `enabled=true` |
| `log_authentication` | `CMS_AUDIT_LOG_AUTHENTICATION` | `true` | `enabled=true` |
| `register_routes` | `CMS_AUDIT_REGISTER_ROUTES` | `true` | `enabled=true` |
| `load_migrations` | `CMS_AUDIT_LOAD_MIGRATIONS` | `true` | `enabled=true` |

---

## `AuditConfig` helper

**Namespace:** `HMsoft\Tools\Features\Audit\Support\AuditConfig`

| Method | Returns true when |
|--------|---------------------|
| `isEnabled()` | Master switch is on |
| `shouldLogModelEvents()` | Enabled + model events toggle |
| `shouldLogRelationSync()` | Enabled + relation sync toggle |
| `shouldLogAuthentication()` | Enabled + auth toggle |
| `shouldRegisterRoutes()` | Enabled + routes toggle |
| `shouldLoadMigrations()` | Enabled + migrations toggle |

Use in custom code:

```php
use HMsoft\Tools\Features\Audit\Support\AuditConfig;

if (AuditConfig::shouldLogModelEvents()) {
    // custom audit-related logic
}
```

---

## Enable / disable scenarios

### Scenario A — Full audit (production CMS)

```env
CMS_AUDIT_ENABLED=true
QUEUE_CONNECTION=database
```

All toggles remain `true` (defaults). Run queue worker continuously.

---

### Scenario B — No audit system (lightweight API / dev sandbox)

```env
CMS_AUDIT_ENABLED=false
```

**What stops:**

| Component | Behavior |
|-----------|----------|
| `Auditable` trait | No Eloquent listeners registered |
| `HasDynamicSyncAndAudit` | `syncRelation()` still syncs; audit dispatch skipped |
| Auth listener | Not registered |
| `/api/audit` routes | Not registered |
| Migrations | Not auto-loaded from package |
| `ProcessAuditLogJob` | No-ops if already queued |
| Artisan commands | `audit:verify`, `audit:state-match` not registered |

Models may still use `Auditable` / `HasDynamicSyncAndAudit` traits — they become inert when disabled.

---

### Scenario C — Audit writes only, no admin API

```env
CMS_AUDIT_ENABLED=true
CMS_AUDIT_REGISTER_ROUTES=false
```

Ledger still records changes; `/api/audit` endpoints are not exposed.

---

### Scenario D — Auth logging only (no model events)

```php
// config/cms_audit.php
'enabled' => true,
'log_model_events'  => false,
'log_relation_sync' => false,
'log_authentication' => true,
```

Useful for login monitoring without full CMS change tracking.

---

### Scenario E — Runtime override (tests)

```php
config(['cms_audit.enabled' => false]);
```

Traits read config at boot/dispatch time — set before model operations in tests.

---

## Provider registration

The `AuditServiceProvider` must remain registered in `bootstrap/providers.php` even when audit is disabled, so config is merged and the app can toggle audit without code changes.

Optional hard removal (not recommended for toggle-based workflows):

```php
// Comment out when audit is never used in this project
// HMsoft\Tools\Features\Audit\Providers\AuditServiceProvider::class,
```

---

## Queue configuration

Audit jobs implement `ShouldQueue`. Required `.env`:

```env
QUEUE_CONNECTION=database   # or redis, sqs, etc.
```

Without a worker, jobs accumulate but are not lost (with persistent queue drivers).

See [DEPLOYMENT.md](./DEPLOYMENT.md) for worker setup.

---

## Cache considerations

Morph map cache key: `system_morph_map`

Clear after:

- Adding/removing audited models
- Changing `getMorphClass()` return values

```bash
php artisan cache:clear
```

---

## Checklist: enabling audit on a new project

1. Set `CMS_AUDIT_ENABLED=true`
2. Publish config: `php artisan vendor:publish --tag=cms-audit-config`
3. Run migrations: `php artisan migrate`
4. Configure queue driver and start worker
5. Add `Auditable` / `HasDynamicSyncAndAudit` to models
6. Clear cache after model registration
7. Schedule verification commands (see [VERIFICATION.md](./VERIFICATION.md))
