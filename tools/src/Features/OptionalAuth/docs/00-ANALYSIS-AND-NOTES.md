# OptionalAuth — Analysis & Notes

---

## Feature overview

OptionalAuth is a **single middleware** that attempts authentication across one or more guards without rejecting unauthenticated requests.

**Problem it solves:** Public website/CMS frontend needs to call the same list/detail APIs as logged-in users, but with extra fields (favorites, admin visibility) when a Sanctum token is present.

**Alternative without OptionalAuth:** Duplicate routes (public vs authenticated) or always require auth — both are worse for SPA/mobile clients.

---

## Design strengths

- Minimal surface area — one middleware, ~35 lines
- Guard-parameterized (`:sanctum`, `:web`, multiple guards)
- Sets `Auth::shouldUse($guard)` so `auth()` helper uses correct guard
- Sets `$request->user()` resolver for `$request->user()` in controllers/middleware
- Composes with Laravel Sanctum stateful API (`statefulApi()` in bootstrap)

---

## Limitations & notes

### 1. Does not validate token expiry explicitly

Relies on guard's `check()` — Sanctum handles token validation internally. Invalid/expired tokens result in **guest** behavior, not 401 (when using optional middleware alone).

### 2. Service provider is minimal

`OptionalAuthServiceProvider` does not register the alias — registration is done in app's `bootstrap/app.php`. This is intentional for Laravel 11+ style bootstrap.

### 3. Not a replacement for authorization

Optional auth only **identifies** the user. Route protection still requires:

- `auth:sanctum` — must be logged in
- `permission:feature` — must have CMS permission (app middleware)

### 4. Invalid token = silent guest

Frontend may think user is logged in (localStorage token) but API treats as guest if token expired. Frontend should handle 401 on protected routes and refresh/logout.

### 5. Multiple guards

If no guard parameter passed, loops **all** guards from `config('auth.guards')` — first match wins. Prefer explicit `:sanctum` for API.

---

## Integration with other features

| Feature | How OptionalAuth helps |
|---------|------------------------|
| **Favorites** | `IsFavoritedResolver` uses `auth()->id()` — false for guests |
| **Active scope** | `ApplyActiveScopeForNotAdmin` — guests see active-only content |
| **Permissions** | `CheckFeaturePermission` aborts 401 if `$request->user()` null |
| **Sanctum** | Token in `Authorization: Bearer` header parsed by sanctum guard |

---

## Verification checklist

- [ ] `GET /api/blogs` works **without** Authorization header
- [ ] Same request **with** valid Bearer token sets `auth()->user()`
- [ ] `is_favorited` true/false based on login state
- [ ] Guest sees only active records (models with `ApplyActiveScopeForNotAdmin`)
- [ ] Admin with token sees inactive records
- [ ] `POST /api/blogs` returns 401 for guest (permission middleware)
- [ ] `GET /api/auth/user` returns 401 for guest (auth:sanctum)

---

## See also

- [../README.md](../README.md)
- [05-COMPLETE-API-REFERENCE.md](./05-COMPLETE-API-REFERENCE.md)
