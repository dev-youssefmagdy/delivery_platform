# API Conventions & Endpoint Reference

> Conventions are binding from P0.2 onward (ADR-14, SRS §20). Endpoints are added to §8 by the milestone that ships them.
> **Status:** no API endpoints exist yet (P0.1). `routes/api_v1.php` arrives in P0.2.

## 1. Versioning and base URL

- Base path: `/api/v1`. Breaking changes require `/api/v2`; additive changes (new fields, new endpoints) stay in v1.
- Clients must ignore unknown fields and map unknown enum values to `unknown`.
- Content type: `application/json; charset=utf-8`. Timestamps: ISO-8601 UTC (`2026-10-13T19:30:00Z`).
- Identifiers exposed to clients are UUIDs, never auto-increment IDs.

## 2. Response envelope

Every API response and every admin AJAX response is produced by `App\Support\Http\ApiResponse`:

```json
{
  "success": true,
  "message": "تم إنشاء الطلب",
  "data": {},
  "meta": {},
  "errors": {}
}
```

| Field | Rule |
|---|---|
| `success` | `true` for 2xx, `false` otherwise |
| `message` | Localized human message (Arabic default, per `Accept-Language`) |
| `data` | Resource object/list, or `null` |
| `meta` | Pagination, request id, extra context |
| `errors` | Field → list of messages; `_code` holds the stable machine code for domain errors |

## 3. Errors

| HTTP | When | `errors._code` examples |
|---|---|---|
| 400 | Malformed request / bad JSON | `bad_request` |
| 401 | Missing/invalid token | `unauthenticated` |
| 403 | Authenticated but not allowed (policy/ability) | `forbidden`, `account_suspended` |
| 404 | Not found **or** not visible to caller (no IDOR hints) | `not_found` |
| 409 | State conflict / optimistic lock / race lost | `booking_already_assigned`, `stale_version` |
| 422 | Validation or business rule violation | `validation_failed`, `offer_expired`, `idempotency_key_reused` |
| 429 | Rate limited; includes `Retry-After` header and `meta.retry_after` | `too_many_requests` |
| 5xx | Server error; never leaks SQL, stack traces, paths or secrets | `server_error` |

Validation example:

```json
{
  "success": false,
  "message": "البيانات المدخلة غير صحيحة",
  "data": null,
  "meta": { "request_id": "8f6c…" },
  "errors": { "_code": "validation_failed", "phone": ["رقم الهاتف غير صالح"] }
}
```

Domain errors are thrown as `BusinessRuleViolation::make('<code>', field: '<field>')` (422) or `ConflictException` (409) and
rendered like validation errors so admin forms and apps display them inline.

## 4. Authentication

- **Mobile (customer/provider):** phone OTP → Sanctum personal access token with abilities `customer` and/or `provider`
  (ADR-05). Header: `Authorization: Bearer <token>`. Endpoints: `/auth/otp/request`, `/auth/otp/verify`, `/auth/logout` (P1.3).
- **Admin:** session guard `admin` (cookie + CSRF), not token based (ADR-06).
- **Webhooks:** no user auth; signature + timestamp window + `(gateway, event_id)` uniqueness (I8, P3.2).

## 5. Headers

| Header | Direction | Purpose |
|---|---|---|
| `Accept-Language` | request | `ar` (default) or `en`; user preference wins when authenticated |
| `X-Request-Id` | both | Correlation id; generated if absent, echoed in response and `meta.request_id` |
| `Idempotency-Key` | request | UUID; required on critical writes (§7) |
| `X-App-Version`, `X-Platform` | request | App version gating (P5.6) |

## 6. Pagination, filtering, sorting

- Length-aware: `?page=2&per_page=20` (max 100) → `meta.pagination = { current_page, per_page, total, last_page }`.
- Cursor (feeds/history): `?cursor=…` → `meta.pagination = { next_cursor, prev_cursor, per_page }`.
- Filters: `?filter[status]=completed`, sorting: `?sort=-created_at`. Only allowlisted keys are accepted; unknown keys are ignored.

## 7. Idempotency

Required on: booking creation, offer submission, offer acceptance, provider job transitions, payments/refunds, payout operations.
The `idempotent` middleware stores `(actor, route, key) → status + body` for 24 h and replays the stored response. Reusing a
key with a different payload hash returns `422 idempotency_key_reused`.

## 8. Endpoints

_None yet._ Each milestone appends a table: method, path, auth/ability, idempotent?, request fields, response resource, error codes.
