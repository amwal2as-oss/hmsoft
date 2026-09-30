# Client ownership

Resolve a guest device id from a request header, and optionally scope a query to “this user **or** this guest client (null user column)”.

Identity, crypto, and rate limiting stay in the app.

## Directory structure

```text
HMsoft/Tools/Features/ClientOwnership/
├── Support/
│   └── ClientOwnershipResolver.php
├── Providers/
│   └── ClientOwnershipServiceProvider.php
├── config/
│   └── client_ownership.php
└── README.md
```

## Header

```php
use HMsoft\Tools\Features\ClientOwnership\Support\ClientOwnershipResolver;

$clientId = ClientOwnershipResolver::clientIdFromRequest();
```

Default header is `X-Client-Id`. Change `config/client_ownership.php` (`header`) in a clone.

`fromRequest()` returns `['client_id' => ?, 'user_id' => auth()->id()]`.

## Query scope

```php
ClientOwnershipResolver::applyOwnershipScope($query);
```

Rows match the current user **or** the current client id with a null user column. With neither user nor client, the query matches nothing (`0 = 1`).

Column names: `user_id_column` / `client_id_column` in config (defaults `user_id` / `client_id`).

## Not this feature

- Linking guest rows to a user after login (app Identity action)
- Crypto `ClientContext`, decrypt/encrypt, handshake
- Rate limiting / CORS allow-lists
