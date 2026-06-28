# Accounting API Documentation

The REST API is **versioned** under `/api/v1` and authenticated with **Laravel Sanctum** bearer tokens. Endpoints are **scoped by token abilities** — e.g. a token with `invoices:read` can list invoices but not create them, and cannot reach `bills:*` routes.

## Machine-readable spec

The canonical, always-current description is the OpenAPI document:

```
GET /api/v1/openapi.json
```

It lists every versioned endpoint with its required ability and is served without authentication (so docs/tooling can read it). Point Swagger UI / Redoc / Postman at that URL.

## Authentication

```
Authorization: Bearer <token>
```

Mint tokens with the abilities the client needs:

```php
$user->createToken('integration', ['invoices:read', 'invoices:write'])->plainTextToken;
```

Ability convention: `<resource>:read` for GET, `<resource>:write` for POST/PUT/DELETE. Resources: `invoices`, `bills`, `estimates`, `journal-entries`, `chart-of-accounts`, `general-ledger` (read-only).

## Versioning

- `/api/v1/*` — canonical, scoped endpoints.
- The original unversioned paths (`/api/invoices`, …) remain as **back-compat aliases** (Sanctum-authenticated, not ability-scoped) and may be removed in a future major version.

## Rate limits

`60 req/min` default; `30/min` for read-heavy bank fetches; `10/min` for sync operations. See `routes/api.php`.

> Applying the per-verb ability convention to **all** v1 resources (currently fully applied to `invoices` and `bills`) is mechanical follow-up; the OpenAPI spec already documents the intended scope for each.
