# Audit API Reference

HTTP endpoints, data objects, Artisan commands, and Laravel events.

---

## HTTP API

**Base prefix:** `/api/audit`  
**Middleware:** `api` (default Laravel API stack)

Routes are registered only when `AuditConfig::shouldRegisterRoutes()` is true.

### List audit logs

```http
GET /api/audit
```

**Query params:** Supports HMsoft DynamicFilters (pagination, sorting, filtering via request params consumed by `AutoFilterAndSortService`).

**Response:** Paginated collection of `AuditLogData` via `CmsResponse::success()`.

**Includes:** `user` relation loaded as `id, first_name, last_name, email`.

**Default sort:** Latest `id` first.

---

### Show single audit log

```http
GET /api/audit/{id}
```

**Response:** Single `AuditLogData` object.

---

### Security note

`AuditLogController` contains a commented permission check. **Protect these routes** with your authorization middleware before production:

```php
// Example — adjust to your permission system
Route::prefix('audit')
    ->middleware(['auth:sanctum', 'can:view-audit-logs'])
    ->group(/* ... */);
```

---

## `AuditLogData` DTO

**Namespace:** `HMsoft\Tools\Features\Audit\Data\AuditLogData`  
**Extends:** `HMsoft\Tools\Features\DynamicFilters\Data\BaseData`

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | `?int` | Ledger row ID |
| `user_id` | `?int` | Actor user ID |
| `event` | `?string` | Event type |
| `auditable_type` | `?string` | Morph alias |
| `auditable_id` | `?string` | Target record ID |
| `old_values` | `?array` | Previous state |
| `new_values` | `?array` | New state |
| `ip_address` | `?string` | Client IP |
| `user_agent` | `?string` | Client UA |
| `session_id` | `?string` | Session ID |
| `previous_hash` | `?string` | Chain link |
| `hash` | `?string` | Row fingerprint |
| `created_at` | `?string` | Timestamp |
| `actor` | `?array` | `{ id, first_name, last_name, email }` when user loaded |

### Factory

```php
AuditLogData::fromModel($auditLog);
AuditLogData::filterableCollect($paginator);
```

---

## `AuditLog` model

**Namespace:** `HMsoft\Tools\Features\Audit\Models\AuditLog`  
**Table:** `audit_logs`  
**Traits:** `IsAutoFilterable` (DynamicFilters)

### Relationships

```php
$log->user();       // BelongsTo User
$log->auditable();  // MorphTo target model
```

### Constants

```php
AuditLog::UPDATED_AT = null;  // Append-only — no updated_at column
```

---

## `ProcessAuditLogJob`

**Namespace:** `HMsoft\Tools\Features\Audit\Jobs\ProcessAuditLogJob`  
**Interface:** `ShouldQueue`

### Constructor parameters

| Param | Type | Description |
|-------|------|-------------|
| `$type` | `string` | Morph alias (`auditable_type`) |
| `$id` | `int\|string` | Record ID |
| `$event` | `string` | Event name |
| `$old` | `array` | Old values |
| `$new` | `array` | New values |
| `$context` | `array` | `{ user_id, ip_address, user_agent, session_id }` |

### Dispatch example

```php
ProcessAuditLogJob::dispatch(
    'blogs',
    $blog->id,
    'updated',
    ['title' => 'Old'],
    ['title' => 'New'],
    [
        'user_id'    => auth()->id(),
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'session_id' => session()->getId(),
    ]
);
```

Early-returns when `AuditConfig::isEnabled()` is false.

---

## `AuditConfig`

**Namespace:** `HMsoft\Tools\Features\Audit\Support\AuditConfig`

See [CONFIGURATION.md](./CONFIGURATION.md) for all methods.

---

## Artisan commands

Registered only when audit is enabled (`AuditConfig::isEnabled()`).

| Command | Class | Description |
|---------|-------|-------------|
| `audit:verify` | `VerifyAuditLedger` | Verify hash chain integrity |
| `audit:state-match` | `VerifySystemState` | Compare live DB vs latest ledger state |

See [VERIFICATION.md](./VERIFICATION.md) for usage details.

### Publish config

```bash
php artisan vendor:publish --tag=cms-audit-config
```

---

## Laravel events (listeners)

Registered when `AuditConfig::shouldLogAuthentication()` is true.

| Laravel event | Listener | Audit event |
|---------------|----------|-------------|
| `Illuminate\Auth\Events\Login` | `LogAuthenticationEvent` | `logged_in` |
| `Illuminate\Auth\Events\Failed` | `LogAuthenticationEvent` | `login_failed` |
| `Illuminate\Auth\Events\Logout` | `LogAuthenticationEvent` | `logged_out` |

---

## Service provider

**Class:** `HMsoft\Tools\Features\Audit\Providers\AuditServiceProvider`

| Phase | Action |
|-------|--------|
| `register()` | Merges `cms_audit` config |
| `boot()` | Migrations, routes, morph map, commands, auth listeners (all config-gated) |

---

## Route definitions

File: `Features/Audit/Routes/api.php`

```php
Route::prefix('audit')->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::get('/{audit}', [AuditLogController::class, 'show']);
});
```

Mounted under `/api` by the service provider.

---

## Scheduled verification (package reference)

File: `Features/Audit/Routes/console.php` (reference schedule — import in your app's `routes/console.php` if desired):

```php
Schedule::command('audit:verify')->hourly();
Schedule::command('audit:state-match')->dailyAt('03:00');
```

Only effective when audit commands are registered and scheduler runs.
