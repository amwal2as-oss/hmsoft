# OptionalAuth — Frontend Integration Guide

How to call APIs that support **optional authentication** — same endpoint for guests and logged-in users.

---

## Core concept

| Request | Backend behavior |
|---------|------------------|
| No `Authorization` header | Guest — request succeeds |
| Valid `Bearer` token | User authenticated — personalized fields populated |
| Invalid/expired token | Treated as guest (on optional-auth routes) |

---

## When to send the token

| Route type | Send token? |
|------------|-------------|
| Public list/detail (blogs, news, decrees) | **Optional** — send if user logged in |
| User profile, logout | **Required** |
| Favorites, notifications | **Required** |
| CMS create/update/delete | **Required** + permissions |

**Best practice:** Always attach token when available via axios/fetch interceptor.

---

## Fetch example

```typescript
function apiHeaders(): HeadersInit {
  const headers: HeadersInit = { Accept: 'application/json' };
  const token = localStorage.getItem('access_token');
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  return headers;
}

// Same function works for guests and logged-in users
async function fetchBlogs(page = 1) {
  const res = await fetch(`/api/blogs?page=${page}&perPage=10`, {
    headers: apiHeaders(),
  });
  return res.json();
}
```

---

## Axios interceptor

```typescript
import axios from 'axios';

const api = axios.create({ baseURL: '/api' });

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Public endpoint — token attached if exists
const blogs = await api.get('/blogs');

// Protected endpoint — 401 if no token
try {
  await api.get('/auth/user');
} catch (e) {
  if (e.response?.status === 401) redirectToLogin();
}
```

---

## Response differences

### Guest response (blog item)

```json
{
  "id": 1,
  "title": "News article",
  "is_favorited": false
}
```

### Logged-in response (same endpoint)

```json
{
  "id": 1,
  "title": "News article",
  "is_favorited": true
}
```

Frontend should not assume `is_favorited` is meaningful when user is logged out.

---

## UI patterns

### Favorites button

```tsx
function FavoriteButton({ item, isLoggedIn }: Props) {
  if (!isLoggedIn) {
    return <button onClick={redirectToLogin}>♡ Save</button>;
  }
  return (
    <button onClick={() => toggleFavorite(item.id)}>
      {item.is_favorited ? '♥ Saved' : '♡ Save'}
    </button>
  );
}
```

### Admin CMS vs public site

Same API, different tokens:

- **Public site visitor** — no token or customer token → active content only
- **CMS admin** — admin token → sees inactive/draft records

---

## Handling 401 on protected routes

Optional auth routes **do not** return 401 for missing token. Protected routes do:

```typescript
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('access_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

Do not redirect on 401 from optional-auth GET routes — they should not 401 for guests.

---

## Sanctum SPA (cookie) mode

If using Sanctum SPA authentication with cookies:

1. Call `/sanctum/csrf-cookie` first
2. Login via `/api/auth/login`
3. Subsequent requests use session cookie — optional auth still works via `sanctum` guard

Optional auth middleware checks the same `sanctum` guard for both Bearer tokens and session cookies.

---

## TypeScript types

```typescript
export interface AuthState {
  isLoggedIn: boolean;
  accessToken: string | null;
  user: User | null;
}

export interface ListItemWithFavorite {
  id: number;
  title: string;
  is_favorited: boolean; // false when guest
}
```

---

## Common mistakes

| Mistake | Fix |
|---------|-----|
| Requiring login for public browse | Public GET should work without token |
| Not sending token after login | User misses `is_favorited` personalization |
| Expecting 401 on public GET without token | Only protected routes 401 |
| Stale token in localStorage | Handle 401 on `/auth/user` and clear token |

---

## See also

- [../README.md](../README.md)
- [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md)
