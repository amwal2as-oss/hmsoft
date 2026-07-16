# OptionalAuth — Setup Checklist

---

## Package setup

```
[ ] OptionalAuthServiceProvider registered in bootstrap/providers.php
[ ] OptionalAuthMiddleware imported in bootstrap/app.php
[ ] Middleware alias: optional.auth → OptionalAuthMiddleware
[ ] Global api append: OptionalAuthMiddleware:sanctum (or per-route)
[ ] Middleware priority: OptionalAuth before SubstituteBindings
[ ] statefulApi() enabled if using Sanctum SPA cookies
```

---

## Route design

```
[ ] Public GET routes — NO auth:sanctum middleware
[ ] Write/CMS routes — auth:sanctum and/or permission:* middleware
[ ] User-specific routes (favorites, profile) — auth:sanctum required
[ ] Document which endpoints are public vs protected for frontend team
```

---

## Application code

```
[ ] DTOs/resolvers null-check auth()->id() (e.g. IsFavoritedResolver)
[ ] Models needing public active-only filter use ApplyActiveScopeForNotAdmin
[ ] Controllers do not assume auth()->user() is non-null on public routes
[ ] Permission middleware used for CMS mutations
```

---

## Verify

```bash
# 1. Guest can read
curl -s http://localhost/api/blogs | jq '.data[0].is_favorited'
# Expected: false

# 2. User with token gets personalization
curl -s http://localhost/api/blogs \
  -H "Authorization: Bearer TOKEN" | jq '.data[0].is_favorited'
# Expected: true/false based on favorites

# 3. Guest blocked from write
curl -s -o /dev/null -w "%{http_code}" -X POST http://localhost/api/decrees
# Expected: 401

# 4. Guest blocked from /auth/user
curl -s -o /dev/null -w "%{http_code}" http://localhost/api/auth/user
# Expected: 401
```

---

## Required vs optional

| Item | Required |
|------|----------|
| Service provider registration | ✅ |
| Middleware on API group | ✅ (global or per-route) |
| Sanctum configured | ✅ |
| `auth:sanctum` on public GET | ❌ Do NOT add |
| Guest-safe resolver code | ✅ Recommended |
| ApplyActiveScopeForNotAdmin | Optional (public CMS sites) |

---

## See also

- [../README.md](../README.md)
- [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md)
