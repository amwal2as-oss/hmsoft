# Audit Feature (Zero-Trust Ledger)

Cryptographically chained audit logging for Laravel CMS APIs. Records who changed what, when, and from where — with tamper detection built in.

**Package path:** `vendor/hmsoft/tools/tools/src/Features/Audit`

---

## What it does

| Capability | Description |
|------------|-------------|
| **Model auditing** | Auto-logs `created`, `updated`, `deleted` on Eloquent models via the `Auditable` trait |
| **Relation auditing** | Logs bulk sync of `HasMany` / `BelongsToMany` relations via `HasDynamicSyncAndAudit` |
| **Auth auditing** | Logs login, logout, and failed login attempts |
| **Hash chain** | Each row is SHA-256 linked to the previous row — tampering breaks the chain |
| **Verification** | Artisan commands detect ledger tampering and raw SQL bypass |
| **Config control** | Entire system can be enabled/disabled via `config/cms_audit.php` |

---

## Quick start

### 1. Register the provider

```php
// bootstrap/providers.php
HMsoft\Tools\Features\Audit\Providers\AuditServiceProvider::class,
```

### 2. Publish config (optional)

```bash
php artisan vendor:publish --tag=cms-audit-config
```

### 3. Enable auditing

```env
CMS_AUDIT_ENABLED=true
QUEUE_CONNECTION=database
```

Run migrations and start a queue worker:

```bash
php artisan migrate
php artisan queue:work
```

### 4. Add traits to a model

```php
use HMsoft\Tools\Features\Audit\Traits\Auditable;
use HMsoft\Tools\Features\Audit\Traits\HasDynamicSyncAndAudit;

class Blog extends Model
{
    use Auditable, HasDynamicSyncAndAudit;

    public function getMorphClass(): string
    {
        return 'blogs';
    }
}
```

### 5. Always use Eloquent (never raw SQL)

```php
// ✅ Audited
$blog->update(['title' => 'New title']);

// ❌ Bypasses audit completely
DB::table('blogs')->where('id', 1)->update(['title' => 'New title']);
```

---

## Configuration at a glance

| Env variable | Default | Purpose |
|--------------|---------|---------|
| `CMS_AUDIT_ENABLED` | `true` | Master on/off switch |
| `CMS_AUDIT_LOG_MODEL_EVENTS` | `true` | Model create/update/delete |
| `CMS_AUDIT_LOG_RELATION_SYNC` | `true` | `syncRelation()` changes |
| `CMS_AUDIT_LOG_AUTHENTICATION` | `true` | Login / logout / failed login |
| `CMS_AUDIT_REGISTER_ROUTES` | `true` | `/api/audit` endpoints |
| `CMS_AUDIT_LOAD_MIGRATIONS` | `true` | `audit_logs` table migration |

Set `CMS_AUDIT_ENABLED=false` when a project has no audit infrastructure.

See [CONFIGURATION.md](./docs/CONFIGURATION.md) for full details.

---

## Documentation index

| Doc | Description |
|-----|-------------|
| [ARCHITECTURE.md](./docs/ARCHITECTURE.md) | Hash chain, data flow, components, morph map |
| [CONFIGURATION.md](./docs/CONFIGURATION.md) | Config keys, env vars, enable/disable scenarios |
| [DEVELOPER_GUIDE.md](./docs/DEVELOPER_GUIDE.md) | Traits, `syncRelation`, morph aliases, best practices |
| [API_REFERENCE.md](./docs/API_REFERENCE.md) | HTTP routes, DTOs, Artisan commands, events |
| [VERIFICATION.md](./docs/VERIFICATION.md) | `audit:verify` and `audit:state-match` |
| [DEPLOYMENT.md](./docs/DEPLOYMENT.md) | Queue workers, scheduling, shared hosting |

---

## Core classes

| Class | Purpose |
|-------|---------|
| `Auditable` | Trait — hooks Eloquent model events |
| `HasDynamicSyncAndAudit` | Trait — audits relation sync operations |
| `ProcessAuditLogJob` | Queued job — writes hash-chained ledger rows |
| `AuditConfig` | Static helper — reads `cms_audit` config |
| `AuditServiceProvider` | Bootstraps migrations, routes, morph map, listeners |
| `LogAuthenticationEvent` | Listener — auth event → audit job |
| `AuditLog` | Eloquent model for `audit_logs` table |
| `VerifyAuditLedger` | Command — `audit:verify` |
| `VerifySystemState` | Command — `audit:state-match` |

---

## Related HMsoft features

- **DynamicFilters** — powers paginated audit log listing (`AutoFilterAndSortService`)
- **Response** — `CmsResponse` wrapper for audit API responses
- **Active / Translations / Media** — often combined with audited models in CMS domains
