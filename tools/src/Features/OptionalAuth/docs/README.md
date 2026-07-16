# OptionalAuth — Documentation Index

> **GitHub main doc:** [../README.md](../README.md)

OptionalAuth lets API routes work for **both guests and authenticated users** on the same endpoint.

| Component | File |
|-----------|------|
| Middleware | `OptionalAuthMiddleware.php` |
| Provider | `OptionalAuthServiceProvider.php` |

---

## Documentation files

| File | Audience | Description |
|------|----------|-------------|
| [../README.md](../README.md) | **Everyone** | Main doc — setup, patterns, use cases |
| [00-ANALYSIS-AND-NOTES.md](./00-ANALYSIS-AND-NOTES.md) | Backend devs | Design notes & limitations |
| [01-BACKEND-ARCHITECTURE.md](./01-BACKEND-ARCHITECTURE.md) | Backend devs | Middleware internals |
| [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md) | Backend devs | bootstrap/app.php setup |
| [03-FRONTEND-GUIDE.md](./03-FRONTEND-GUIDE.md) | Frontend devs | When to send Bearer token |
| [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md) | Backend devs | Printable checklist |
| [05-COMPLETE-API-REFERENCE.md](./05-COMPLETE-API-REFERENCE.md) | Backend devs | Guard parameters & API |

---

## Quick comparison

| | `optional.auth` | `auth:sanctum` |
|---|-----------------|----------------|
| No token | ✅ Request continues (guest) | ❌ 401 Unauthorized |
| Valid token | ✅ User set | ✅ User set |
| Invalid token | Guest (silent)* | ❌ 401 |

\* Sanctum typically leaves guard unauthenticated without throwing when used via optional middleware loop.

---

## Quick start

```php
// bootstrap/app.php — global on API
OptionalAuthMiddleware::class . ':sanctum',

// Controller — handle both cases
$userId = auth()->id(); // null = guest
```

See [../README.md](../README.md) for full examples.
