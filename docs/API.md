# API Conventions & Endpoint Reference

> Conventions are binding from P0.2 onward (ADR-14, SRS §20). Endpoints are added to §8 by the milestone that ships them.
> **Status (P0.2):** conventions below are implemented (`ApiResponse`, `ApiExceptionRenderer`, middleware `CorrelationId`,
> `SetLocale`, `ForceJsonResponse`, `idempotent`). Routes live in `backend/routes/api_v1.php` (prefix `api/v1`, names `api.v1.*`).

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
| `meta` | Pagination, extra context; **error** responses always carry `meta.request_id` |
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
| 405 | Method not allowed | `method_not_allowed` |
| 419 | CSRF token / session expired (admin AJAX) | `session_expired` |
| 5xx | Server error; never leaks SQL, stack traces, paths, exception messages or secrets (also when `APP_DEBUG=true`) | `server_error`, `service_unavailable` (503) |

Implemented error codes (P0.2): `validation_failed`, `unauthenticated`, `forbidden`, `not_found`, `method_not_allowed`,
`session_expired`, `too_many_requests`, `service_unavailable`, `server_error`, `idempotency_key_required`,
`idempotency_key_invalid`, `idempotency_key_reused`, `idempotency_request_in_progress`. Other HTTP errors fall back to
`http_<status>`. Messages for domain codes come from `lang/{ar,en}/api.php` → `errors.<code>`; a code without a translation
gets the generic message for its status.

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

Domain errors are thrown as `BusinessRuleViolation::make('<code>', field: '<field>')` (422), `ConflictException` (409) or
`ForbiddenActionException` (403) and rendered like validation errors (the field gets the message) so admin forms and apps
display them inline. They are not reported to the error log. On HTML (admin/website) requests they render the normal error
page for their status.

## 4. Authentication

- **Mobile (customer/provider):** phone OTP → Sanctum personal access token with abilities `customer` and/or `provider`
  (ADR-05). Header: `Authorization: Bearer <token>`. Endpoints: `/auth/otp/request`, `/auth/otp/verify`, `/auth/refresh`, `/auth/logout` (section 8.5). A token carries exactly one ability, `customer` or `provider`, chosen by the `X-App` header at verification.
- **Admin:** session guard `admin` (cookie + CSRF), not token based (ADR-06).
- **Webhooks:** no user auth; signature + timestamp window + `(gateway, event_id)` uniqueness (I8, P3.2).

## 5. Headers

| Header | Direction | Purpose |
|---|---|---|
| `Accept-Language` | request | `ar` (default) or `en`. Order: authenticated user preference → session `locale` (web/admin only) → best `Accept-Language` match → `ar` |
| `Content-Language` | response | Locale actually used |
| `X-Request-Id` | both | Correlation id; accepted if it matches `[A-Za-z0-9._:-]{8,128}`, otherwise a UUID is generated. Echoed on every response and in `meta.request_id` of errors; added to every log line (Laravel Context) |
| `Idempotency-Key` | request | UUID; required on critical writes (§7) |
| `Idempotent-Replayed` | response | `true` when the body is a stored replay |
| `X-App` | request | `customer` or `provider`; required on `/auth/otp/request` and `/auth/otp/verify` (selects the token ability). Authenticated routes read the app from the token, never from this header |
| `X-App-Version`, `X-Platform` | request | App version gating (P5.6) |

## 6. Pagination, filtering, sorting

- Length-aware: `?page=2&per_page=20` (max 100) → `meta.pagination = { current_page, per_page, total, last_page }`.
- Length-aware responses add `type: "page"`.
- Cursor (feeds/history): `?cursor=…` → `meta.pagination = { type: "cursor", next_cursor, prev_cursor, per_page }`.
- Produced by `ApiResponse::paginated($paginator, ResourceClass::class)`.
- Filters: `?filter[status]=completed`, sorting: `?sort=-created_at`. Only allowlisted keys are accepted; unknown keys are ignored.

## 7. Idempotency

Required on: booking creation, offer submission, offer acceptance, provider job transitions, payments/refunds, payout operations.
The `idempotent` middleware (`App\Http\Middleware\Idempotency`, table `idempotency_keys`):

- applies to unsafe methods only; a missing key → `422 idempotency_key_required`, a non-UUID → `422 idempotency_key_invalid`;
- scope is `(actor morph type + id, route name, key)`; guests share the scope `guest/0` (critical routes are authenticated);
- the request hash covers method, path, input with keys sorted recursively, and uploaded file contents;
- the first **2xx** response is stored for `IDEMPOTENCY_TTL_HOURS` (24) and replayed verbatim with `Idempotent-Replayed: true`;
  non-2xx responses are not stored, so the client can fix and retry with the same key;
- same key + different payload → `422 idempotency_key_reused`;
- same key while the first request is still running → `409 idempotency_request_in_progress` (Redis atomic lock,
  `IDEMPOTENCY_LOCK_STORE`, `IDEMPOTENCY_LOCK_SECONDS`);
- expired rows are deleted hourly by `model:prune`.

## 7a. Rate limits

Named limiters (`App\Enums\Common\RateLimiterName`, limits in `config/rate_limits.php` / `RATE_LIMIT_*` env): `api`
(120/min per user or IP, applied to the whole `api` group), `otp-request` (3/min and 10/h per phone **and** per IP),
`otp-verify` (5/min per phone+IP), `location` (30/min per user), `offers` (20/min per user), `public-forms` (5/min per IP),
`admin-login` (5/min per email+IP). Exceeding a limit → `429 too_many_requests` with `Retry-After` and `meta.retry_after`.

## 8. Endpoints

Each milestone appends a table: method, path, auth/ability, idempotent?, request fields, response resource, error codes.

### 8.1 System (P0.2)

| Method | Path | Auth | Idempotent | Response | Errors |
|---|---|---|---|---|---|
| GET | `/api/v1/health` | public (throttle `api`) | — | `200` `data = { status: "ok", checks: { database, redis, queue, scheduler } }`, each check `ok`/`failing` | `503 service_unavailable` with the same object in `meta` |

The public health response never contains latencies, error messages, connection names or heartbeat ages; those details are
kept in `HealthReport::$details` for the admin system page (P5.6). The scheduler check fails when the `system:heartbeat`
cache key is older than `HEALTH_HEARTBEAT_MAX_AGE` (180 s). Laravel's own `/up` liveness route also exists.

### 8.2 Admin AJAX endpoints (P0.3)

Session-authenticated (`web` + CSRF), under `ADMIN_PATH` (default `/admin`), all in the ADR-14 envelope via `ApiResponse` except
the DataTables protocol responses (ADR-38). Every admin write route accepts `Precognition: true` (+ optional
`Precognition-Validate-Only: <field>`): `204` + `Precognition-Success: true` when valid, `422 validation_failed` otherwise,
without running the controller. Admin routes require a signed-in admin (ADR-06): HTML requests from guests are redirected to
`/admin/login`, JSON/XHR requests get `401 unauthenticated`; a missing permission is `403 forbidden`.

| Method | Path | Ability | Response | Errors |
|---|---|---|---|---|
| POST | `/admin/locale` | public (guest: session; admin: also persisted on the admin) | `data.reload = true` | `422` |
| GET | `/admin/lookups/{lookup}?q=&ids[]=` | lookup permission (null = any admin) | `data = [{ id, text, meta }]`, max `admin.lookup_limit` (20) | `404 not_found` (unknown lookup), `403`, `422` |
| GET | `/admin/exports/{uuid}` | owner of the export | `data = { uuid, status, status_label, rows, finished, download_url }` (signed URL once completed) | `403 forbidden` |
| GET | `/admin/exports/{uuid}/download` (signed) | owner | CSV (UTF-8 BOM, formula cells neutralised) | `403 invalid signature / export_not_ready / forbidden` |
| GET | `/admin/<module>` (XHR + JSON) | `<module>.view` | Yajra DataTables JSON (`draw`, `recordsTotal`, `recordsFiltered`, `data`); `filters[...]` as query params | `403` |
| POST | `/admin/<module>/export` | `<module>.export` | `data.status_url`; audit `exported` | `403` |
| POST/PUT/DELETE | module writes | per policy | `AdminFormResponse`: `data = { redirect?, reload_table?: [ids], close_modal?, reset?, reload?, replace_html?: {selector: html} }` | `422` (incl. `errors._code` domain codes such as `code_taken`), `409 stale_version`, `403` |

Domain codes added: `stale_version` (409), `export_not_ready` (403), `export_failed`, `code_taken` (422), `demo_item_archived` (422).

Domain codes added in P1.1 (all `422` unless noted): `invalid_credentials`, `mfa_invalid_code`, `mfa_session_expired`, `mfa_setup_expired`,
`mfa_already_enabled`, `mfa_not_enabled`, `mfa_required_for_role`, `mfa_enrolment_required` (403), `password_incorrect`,
`password_reset_invalid`, `email_taken`, `cannot_modify_self`, `last_super_admin`, `cannot_remove_own_super_admin`, `super_admin_only`,
`role_exceeds_own_permissions`, `role_name_taken`, `role_system_protected`, `role_in_use`, `role_super_admin_protected`,
`admin_already_suspended`, `admin_already_active`; sign-in throttling answers `429 too_many_requests` with `Retry-After`.

### 8.3 Admin identity and access (P1.1)

Session + CSRF, under `ADMIN_PATH`. "Public" routes are reachable without a session; "self" routes need any signed-in admin and act on
their own account; others need the listed permission (Appendix A). All writes accept Precognition.

| Method | Path | Access | Response | Errors |
|---|---|---|---|---|
| GET/POST | `/admin/login` | public (guest) | POST `data.redirect` = intended page, or `/admin/mfa/challenge` when MFA is on | `422 invalid_credentials`, `429` |
| GET/POST | `/admin/mfa/challenge` | public (pending login) | POST `data.redirect` | `422 mfa_invalid_code / mfa_session_expired` |
| GET/POST | `/admin/forgot-password`, `/admin/reset-password[/{token}]` | public (guest) | always the same "link sent" message | `422 password_reset_invalid`, validation |
| POST | `/admin/logout`, `/admin/locale` | public | `data.redirect` / `data.reload` | — |
| GET | `/admin`, `/admin/dashboard` | self / `dashboard.view` | redirect / page | `403` |
| GET/PUT | `/admin/security`, `/admin/security/profile`, `/admin/security/password` | self | page / `data.reload` | `422 password_incorrect` |
| GET/POST | `/admin/security/mfa`, `.../mfa/recovery-codes`, `.../mfa/disable` | self | QR page; confirm → `data.redirect` to the one-time recovery codes page | `422 mfa_invalid_code / mfa_required_for_role / password_incorrect` |
| DELETE | `/admin/security/sessions[/{id}]` | self (own sessions only) | `data.reload` | `404` for foreign/current sessions |
| GET + writes | `/admin/admins` (+ `/{admin}`, `/create`, `/{admin}/edit`, `/{admin}/activity`) | `admins.view` / `admins.manage` | DataTable JSON; writes → `AdminFormResponse` | `409 stale_version`, `422` domain codes above |
| POST | `/admin/admins/{admin}/suspend`, `/reactivate`, `/reset-mfa`; DELETE `/admin/admins/{admin}` | `admins.manage` (+ policy) | `data.reload_table` / `data.reload`; **reason mandatory** for suspend and reset-mfa | `422` |
| GET + writes | `/admin/roles` (+ show/create/edit/activity) ; DELETE `/admin/roles/{role}` | `roles.view` / `roles.manage` | DataTable JSON; delete needs a reason | `409 stale_version`, `422 role_*` |
| GET | `/admin/audit-logs`, `/admin/audit-logs/{id}` | `audit_logs.view` | DataTable JSON (no payloads) / diff page | `403` |
| GET | `/admin/login-activity` | `login_activity.view` | DataTable JSON | `403` |
| GET | `/admin/lookups/admins?q=` | any admin | `[{ id, text }]` (names only) | — |

Domain codes added in P1.2 (all `422`): `country_iso_taken`, `country_iso_immutable`, `country_is_default`, `country_currency_unusable`, `currency_code_taken`,
`currency_code_immutable`, `currency_exponent_immutable`, `currency_in_use`, `policy_not_editable`, `policy_not_deletable`, `policy_not_publishable`,
`policy_arabic_required`, `setting_default_locale_unsupported`, `setting_otp_cooldown_exceeds_ttl`, `setting_offer_ttl_exceeds_search_ttl`,
`setting_radius_steps_not_ascending`.

### 8.4 Settings, markets, policy versions and public config (P1.2)

Admin (session + CSRF, all writes accept Precognition):

| Method | Path | Access | Response | Errors |
|---|---|---|---|---|
| GET | `/admin/settings[?tab=<group>]` | `settings.view` (fields read-only without `settings.manage`) | page, one tab per group (`localization`, `support`, `otp`, `booking`, `dispatch`, `providers`, `safety`, `apps`, `security`, `rate_limits`) | `403` |
| PUT | `/admin/settings/{group}` | `settings.manage` | `data.redirect` to the tab; unchanged save answers "nothing changed"; body `settings[<key>]` (+ hidden `fingerprint`) | `422` field errors keyed `settings.<key>` or a `setting_*` code, `409 stale_version`, `404` unknown group |
| GET + writes | `/admin/countries`, `/admin/currencies` (+ show/create/edit/activity); DELETE | `markets.manage` | DataTable JSON; writes -> `AdminFormResponse`; delete takes an optional reason | `409 stale_version`, `422 country_* / currency_*` |
| GET + writes | `/admin/policy-versions` (+ show/create/edit/activity); DELETE (drafts) | `policies.manage` | DataTable JSON; writes -> `AdminFormResponse` | `409 stale_version`, `403` for non-drafts, `422 policy_*` |
| POST | `/admin/policy-versions/{id}/publish` | `policies.publish` | `data.reload_table` / `data.reload`; **reason mandatory** | `422`, `403` (not a draft) |

Public (no authentication, throttle `api`, `Cache-Control: public, max-age=30`, `ETag` / `If-None-Match` -> `304`, `Vary: Accept-Language`):

| Method | Path | Response |
|---|---|---|
| GET | `/api/v1/config/app` | see below |
| GET | `/api/v1/locales` | `data = [{ code, name (native), direction: "rtl"\|"ltr", is_default }]` for the supported locales |

`data` of `/config/app` (no secrets; empty `policies` / `features` are `{}`):

```json
{
  "default_locale": "ar",
  "locales": ["ar", "en"],
  "country": { "iso2": "EG", "phone_code": "+20", "timezone": "Africa/Cairo" },
  "currency": { "code": "EGP", "exponent": 2, "symbol": { "ar": "ج.م", "en": "EGP" } },
  "digits_preference": "auto",
  "support": { "phone": null, "email": null, "whatsapp": null },
  "maintenance": false,
  "apps": {
    "customer": { "min_version": "1.0.0", "force_update": false },
    "provider": { "min_version": "1.0.0", "force_update": false }
  },
  "policies": { "terms": { "id": "<uuid>", "version": 3, "published_at": "2026-10-19T10:00:00Z" } },
  "features": {}
}
```

`digits_preference` is `auto` (follow the language), `western` or `arabic_indic`. A client must block only when `maintenance` is true, or when `apps.<own>.force_update`
is true and its version is below `apps.<own>.min_version` (customer app = `customer`, driver app = `provider`). Unknown keys are ignored (Section 1).

### 8.5 Mobile identity (P1.3)

Bearer tokens (Sanctum), ability `customer` or `provider`; a customer token is `403 forbidden` on provider-only routes and vice versa. Every `/me` route accepts either ability and
only ever acts on the authenticated user (there is no user id in any path). Account status gates: `account.active` answers `403` with `account_suspended`, `account_deleted`,
`account_deletion_pending`, `account_verification_required` (and `account_restricted` where a route asks for strict checking, such as booking creation later). `GET /me`, `GET /me/consents`,
`DELETE /me/account-deletion-requests/{id}` and `POST /auth/logout` are status-safe so the app can always show the right screen and let the user leave.

| Method | Path | Auth | Notes / errors |
|---|---|---|---|
| POST | `/auth/otp/request` | `X-App`; limiter `otp-request` (per phone, IP and `device_id`; settings `rate_limit_otp_request_*`) | body `phone` (E.164), optional `purpose`, `device_id`. `200` `data = { expires_in, resend_in, length }`, identical for every number. `429 otp_resend_too_soon` (+`Retry-After`, `meta.retry_after`) inside the cooldown, `503 otp_delivery_failed`, `422` |
| POST | `/auth/otp/verify` | `X-App`; limiter `otp-verify` | body `phone`, `code`, `device{device_id, platform, app_version?, push_token?}`. `200` `data = { token, token_type, expires_at, abilities, is_new_user, user }`. `422 otp_invalid` (`meta.attempts_remaining`), `otp_expired`, `otp_attempts_exceeded`; `403 account_suspended` / `account_deleted` after a valid code |
| POST | `/auth/refresh` | token | re-issues the token of this device (old one revoked); same payload as verify; `403` for blocked statuses |
| POST | `/auth/logout` | token | revokes the presented token only and clears that device's push token |
| GET | `/me` | token | `data = { id, app, name, phone, phone_verified, email, photo_url (signed, 15 min), preferred_locale, status, profile_complete, created_at, customer_profile?, consents{pending[{id,type,version}]}, deletion_request? }` |
| PATCH | `/me` | token + `account.active` | `name`, `email`, `preferred_locale`; customer tokens also `accessibility_needs`, `emergency_contact_name/phone`, `marketing_opt_in`. Phone, status and ids are ignored |
| POST | `/me/photo` | token + `account.active` | multipart `photo` (jpg/png/webp, 5 MB, 64-8000 px); re-encoded to JPEG (metadata stripped), stored on the private disk; `422 photo_invalid` |
| GET | `/me/consents` | token | `data = { required[], pending[], accepted[] }` for the app's required policy types (terms, privacy) |
| POST | `/me/consents` | token + `account.active` | `policies[]` (ids), optional `marketing_opt_in`; only current published versions: `422 policy_version_not_current` |
| POST | `/me/devices` | token + `account.active` | `device_id`, `platform`, `app_version?`, `push_token?`, `locale?`; a push token moves to the newest holder |
| POST | `/me/account-deletion-requests` | token + `account.active` + `Idempotency-Key` | optional `reason`; account becomes `deletion_pending`; `409 deletion_request_exists` |
| DELETE | `/me/account-deletion-requests/{id}` | token | cancels a pending request and restores the previous status; `404` for another user's id, `422 deletion_request_not_pending` |
| GET | `/policies/{type}` | public, `ETag` | current published text in the request language (Arabic fallback): `data = { id, type, version, title, content, published_at }`; `404` if none published |
| GET | `/media/users/{uuid}/photo` | signed URL only | streams the private photo; `403` without a valid signature |

New domain codes: `otp_invalid`, `otp_expired`, `otp_attempts_exceeded`, `otp_resend_too_soon` (429), `otp_delivery_failed` (503), `account_suspended`, `account_deleted`,
`account_deletion_pending`, `account_verification_required`, `account_restricted` (all 403), `policy_version_not_current`, `photo_invalid`, `deletion_request_not_pending` (422), `deletion_request_exists` (409).
Admin: `GET /admin/otp-challenges` (permission `otp_challenges.view`, read-only DataTable; filters phone/state/delivery/app/IP/date; masked phone, no codes).

### 8.6 Service catalog (P1.4)

Public, no authentication, throttle `api`, `Cache-Control: public, max-age=30`, `ETag` / `If-None-Match` -> `304`. Only **active** entries that have a **published version** are returned
(a category also needs an active family). Names, descriptions and labels are translation maps `{ "ar": "...", "en": "..." }` (always JSON objects, `{}` when empty): the app picks its
language, falling back to `ar`, so no refetch is needed when the language changes. Ids are integers.

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/service-families` | `data = [{ id, code, kind, name, description, icon, sort_order, categories_count }]` for active families that own at least one visible category. `kind` is `ServiceFamilyKind` |
| GET | `/api/v1/service-categories?lat=&lng=&booking_type=&service_family_id=` | `lat`/`lng` (both or none; (0,0) and out-of-range are `422`) filter by the **serving zone** (8.7): only categories the zone has enabled **and** that are open right now (zone hours, category `service_hours`, zone override hours) are returned, each with `zone_availability: { zone_id, overrides }`; outside every active zone `data = []`. With a position `meta.service_area = { in_service_area, zone_id, city_id, restricted_pickup }`. Without a position every visible category is returned (`zone_availability: null`). `booking_type` filters on the allowed booking types |
| GET | `/api/v1/vehicle-categories?service_category_id=` | active vehicle classes, limited to the ones linked to the category when given |

A category is served **as of its published version** (ADR-65): admin edits of the draft never change what a client fetched until an admin publishes. `data[]` of `/service-categories`:

```json
{
  "id": 7, "code": "ride_taxi", "sort_order": 20, "icon": "car", "map_marker": "car",
  "version": { "id": 31, "number": 2, "published_at": "2026-10-20T10:00:00Z" },
  "family": { "id": 1, "code": "rides", "kind": "passenger_city", "name": { "ar": "...", "en": "..." } },
  "name": { "ar": "...", "en": "..." }, "description": { "ar": "..." },
  "booking": { "allowed_booking_types": ["on_demand", "scheduled"], "supports_stops": true, "max_stops": 3, "service_hours": [{ "day": 1, "from": "08:00", "to": "22:00" }] },
  "pricing": { "negotiation_enabled": true, "reference_estimate_enabled": true, "min_customer_fare_minor": 1500, "max_customer_fare_minor": 150000, "counter_offer_min_pct": 70, "counter_offer_max_pct": 200 },
  "dispatch": { "offer_ttl_seconds": 120, "search_ttl_seconds": 300, "dispatch_radius_steps_km": [2.0, 4.0, 7.0], "dispatch_batch_size": 10 },
  "limits": { "max_passengers": 4, "max_luggage": 2, "max_weight_kg": null, "max_volume_m3": "0.50" },
  "safety": { "pin_required": true, "pickup_proof_required": false, "delivery_proof_types": [], "recipient_otp_required": false },
  "payments": { "payment_methods": ["cash", "card"], "cash_allowed": true, "tipping_enabled": true, "coupon_eligible": true },
  "vehicle_category_ids": [3],
  "vehicle_categories": [{ "id": 3, "code": "taxi", "name": { "ar": "..." }, "icon": "car", "map_marker": "car", "passenger_capacity": 4, "luggage_capacity": 2, "max_payload_kg": null, "max_volume_m3": null }],
  "form_schema": { "version": 4, "fields": [ /* see below */ ] }
}
```

`dispatch` and `safety.pin_required` are the **effective** values: a null category field falls back to the platform setting (`CategoryPolicyResolver`). Money is integer minor units in the market
currency (`/config/app`). Decimal limits (`max_weight_kg`, `max_volume_m3`, `max_payload_kg`) are decimal strings. `version.id` is what bookings will reference (P2.3).

**Form schema field** (`form_schema.fields[]`), validated by the server (`ServiceFormValidator`, for a booking's `service_details`) and rendered by `DynamicFormRenderer`:

```json
{ "key": "origin_floor", "type": "integer", "label": { "ar": "...", "en": "..." }, "help": { "ar": "..." } | null,
  "required": true, "min": 0, "max": 40,
  "options": [{ "value": "sofa", "label": { "ar": "...", "en": "..." } }],
  "visible_if": { "field": "origin_elevator", "op": "eq", "value": false } | null }
```

| `type` | Answer value in `service_details` | `min` / `max` mean |
|---|---|---|
| `text` / `textarea` | string | length (default max 255 / 2000) |
| `integer` / `decimal` | number | value |
| `weight` | number (kg) | value (default min 0.01) |
| `boolean` | `true` / `false` | - |
| `select` | one option `value` | - |
| `multiselect` | list of option values (distinct) | item count (default max = options) |
| `date` / `time` / `datetime` | `Y-m-d` / `H:i` / `Y-m-d\TH:i` (market local time) | - |
| `contact` | `{ name (max 100), phone (E.164) }`, both required when present | - |
| `photo` | list of attachment references (strings) | count (default max 10) |
| `item_checklist` | `[{ item: option value, quantity: 1..999 }]`, distinct items | item count |
| `dimensions` | `{ length, width, height }` in cm, all required when present | each side (default min 0.01) |

`visible_if.op` is one of `eq neq in not_in gt gte lt lte filled empty` (`filled` / `empty` carry no `value`). The controlling field must come earlier in the schema. Hidden fields are never
required and any value sent for them is dropped; keys not in the schema are rejected (`service_details.<key>` validation error). Unknown field types or operators must be ignored/hidden by clients (forward compatibility).

New domain codes (admin): `catalog_code_taken`, `catalog_code_immutable`, `catalog_last_active_category`, `family_has_categories`, `vehicle_category_in_use`, `category_requires_published_version`,
`category_family_inactive`, `category_has_active_bookings`, `category_not_deletable`, `category_no_changes`, `category_arabic_name_required`, `category_requires_booking_type`,
`category_requires_vehicle_category`, `form_schema_invalid` (all `422`).

Admin (permission `services.manage`; vehicle categories `vehicle_categories.manage`): `/admin/service-families`, `/admin/service-categories` (+ `/{id}/vehicle-categories`, `/{id}/form-schema`, `/{id}/publish`, `/{id}/activate`, `/{id}/deactivate`, `/{id}/versions`),
`/admin/vehicle-categories` (+ activate / deactivate); lookups `service-families`, `service-categories`, `vehicle-categories`.

### 8.7 Geography and maps (P1.5)

Coordinates are WGS84 decimals; `lat` in [-90, 90], `lng` in [-180, 180]; `(0, 0)` and non-numeric values are rejected with `422 validation_failed`. Positions are decided with MySQL `ST_Contains` on the zone polygons (ADR-69).
Overlapping zones resolve to the smallest one. Opening hours are evaluated in the **city's timezone**.

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/api/v1/service-areas/resolve?lat=&lng=` | public (`api` throttle) | `data = { in_service_area, point, city: {id,name,timezone,center}\|null, zone: {id,name,restricted_pickup}\|null, local_time, categories: [{ service_category_id, code, available, reason, opens_at, overrides }] }`. `categories` lists what the zone enables (active + published); `available=false` carries `reason` = `zone_closed` \| `category_closed` \| `override_closed` and `opens_at` when known. Outside every active zone: `in_service_area=false`, `city`/`zone` null, `categories=[]` (still `200`) |
| GET | `/api/v1/places/search?q=&lat=&lng=&limit=` | `customer`/`provider` token, active account, `throttle:60,1` | type-ahead; `lat`/`lng` only bias ranking. `data = [{ id, title, address, lat, lng, precision, provider }]`. `precision` is `exact` \| `street` \| `approximate`: **only `exact` may be shown as a precise address**. `q` needs >= 2 characters (`422 maps_query_too_short`). Zero hits are `200` with `[]` |
| GET | `/api/v1/places/reverse?lat=&lng=` | same | `data` = one place or `null` when the provider knows nothing there (used by the map pin) |
| GET | `/api/v1/routes/estimate?origin=lat,lng&destination=lat,lng&stops[]=lat,lng&service_category_id=` | same | route only: `data = { distance_m, duration_s, polyline (encoded, precision 5), provider, origin_zone_id, origin_city_id }`; pricing is added by P2.3. The **origin must be inside an active zone** (`422 outside_service_area`) and, when `service_category_id` is given, the category must be available there now (`422 category_unavailable`) before any paid maps call is made |
| GET | `/api/v1/me/saved-places` | `customer` token | `data = [{ id (uuid), label, type, lat, lng, address: {title,line,precision,provider}\|null, created_at }]`, home first, then work, then others |
| POST | `/api/v1/me/saved-places` | `customer` | `{ label (<=60), type: home\|work\|other, lat, lng, address? }` -> `201`. One `home` and one `work` per customer (`422 saved_place_type_taken`), at most `GEO_SAVED_PLACES_MAX` (20) in total (`422 saved_place_limit_reached`). `address` is a client-supplied snapshot of the geocoder answer (a hint, never used for decisions) |
| PUT | `/api/v1/me/saved-places/{uuid}` | `customer` | same body; replaces |
| DELETE | `/api/v1/me/saved-places/{uuid}` | `customer` | another customer's place and a missing one are both `404` |

Error codes (`errors._code`): `maps_unavailable` (**503**: provider down, quota/credential problem, or no maps driver configured), `outside_service_area`, `category_unavailable`, `route_not_found`, `route_too_many_stops`,
`maps_query_too_short`, `saved_place_type_taken`, `saved_place_limit_reached` (all other `422`). A maps outage is never turned into an empty result or a straight-line guess.

Admin (permission `service_areas.manage`): `/admin/cities` (+ activate / deactivate with reason), `/admin/service-zones` (+ activate, suspend with reason, `PUT /{id}/availability` matrix, `POST /overlaps` live overlap check),
`/admin/coverage-preview` (GET page, POST preview); lookup `cities`. Admin codes: `city_has_zones`, `city_country_inactive`, `zone_no_categories`, `zone_not_active`, `zone_active_cannot_delete`, `zone_invalid_polygon`,
`zone_city_missing`, `zone_availability_invalid`, `zone_override_fare_range`, `zone_override_radius`.
