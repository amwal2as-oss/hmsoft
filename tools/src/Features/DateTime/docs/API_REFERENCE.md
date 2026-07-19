# DateTime — API Reference

> **Full reference:** [REFERENCE.md](./REFERENCE.md)

> Base prefix: `/api/datetime`  
> Auth: not required (timezone comes from the configured resolver)

---

## GET `/api/datetime/config`

Returns active datetime configuration and the **resolved** API timezone for the current request context.

### Response

```json
{
  "success": true,
  "data": {
    "storage_timezone": "UTC",
    "default_api_timezone": "Asia/Damascus",
    "resolved_api_timezone": "Asia/Damascus",
    "resolver": "config",
    "date_format": "Y-m-d\\TH:i:sP"
  }
}
```

### When to use

- Frontend boot — know which timezone API responses use
- Debug resolver (`config` vs `callback` vs `class`)
- Admin settings screens

---

## GET `/api/datetime/now`

Returns current server time in UTC and in the resolved API timezone.

### Response

```json
{
  "success": true,
  "data": {
    "storage_timezone": "UTC",
    "api_timezone": "Asia/Damascus",
    "now_utc": "2026-07-19T06:30:00+00:00",
    "now_api": "2026-07-19T09:30:00+03:00"
  }
}
```

### When to use

- Sync client clocks / display "server time"
- Health checks
- Debug timezone configuration

---

## POST `/api/datetime/convert`

Convert a datetime string between storage (UTC) and API timezone.

### Body

```json
{
  "value": "2026-07-19T06:30:00+00:00",
  "direction": "to_api"
}
```

| Field | Values |
|-------|--------|
| `value` | ISO8601 or parseable datetime string |
| `direction` | `to_api` — UTC → API TZ |
| | `to_storage` / `to_utc` — API TZ → UTC |

### Response

```json
{
  "success": true,
  "data": {
    "input": "2026-07-19T06:30:00+00:00",
    "direction": "to_api",
    "storage_timezone": "UTC",
    "api_timezone": "Asia/Damascus",
    "result": "2026-07-19T09:30:00+03:00"
  }
}
```

### When to use

- Frontend/dev tools debugging conversions
- Admin panels showing both UTC and local time
- Testing custom resolver behavior

---

## Other API datetime fields

All standard API resources (`created_at`, `updated_at`, `published_at`, audit logs, notifications, etc.) are automatically formatted via `CmsDateTime::toApi()`.

Format: **ISO8601 with offset** — e.g. `2026-07-19T09:30:00+03:00`

---

## Audit API

Audit endpoints (`GET /api/audit`) use package `AuditLogData` which applies:

- `created_at` → `CmsDateTime::toApi()`
- `old_values` / `new_values` → recursive `_at` field conversion

No separate audit datetime configuration needed.
