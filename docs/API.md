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
    "customer": { "min_version": "1.0.0", "force_update": false, "platforms": { "android": { "min_version": "1.0.0", "latest_version": null, "force_update": false, "store_url": "https://..." } } },
    "provider": { "min_version": "1.0.0", "force_update": false }
  },
  "policies": { "terms": { "id": "<uuid>", "version": 3, "published_at": "2026-10-19T10:00:00Z" } },
  "features": { "wallet": true, "offers": true, "referrals": false, "loyalty": false },
  "flags": { "referrals": { "apps": ["customer"], "city_ids": [], "rollout_percent": 50 } }
}
```

P5.6: `apps.<app>.platforms` appears for every platform that has an app-version row (`min_version`, `latest_version`, `force_update`, `store_url`); a client should read its own platform entry and
open `store_url` on the forced-update screen. `features.<key>` is the master switch (the legacy setting when no flag row exists); `flags.<key>` lists targeting for keys that have a row (`apps` empty = both,
`city_ids` empty = all). Client evaluation: enabled, own app in `apps` (if any), own city in `city_ids` (if any), and when `rollout_percent` < 100 the bucket `crc32("<key>|<stable user or device id>") % 100`
is below the percentage. The server's own check is authoritative.

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

### 8.8 Customers (P1.6)

Customer inbox (mobile, `customer` token; status-safe so a suspended customer can still read why):

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/api/v1/me/messages` | `customer` token | newest 50 help messages from support: `data = [{ id (uuid), subject, body, read, sent_at }]`; driver tokens get `403` |
| POST | `/api/v1/me/messages/{uuid}/read` | `customer` token | marks one read; another customer's uuid is `404` |

Admin (`/admin/customers`, customers addressed by uuid, soft-deleted/anonymised accounts included). Permissions: `customers.view` (listing, show, lazy tabs), `customers.view_pii` (reveal, saved places), `customers.update` (help message), `customers.suspend` (suspend, restrict, reactivate), `customers.process_deletion`, `customers.export`.

| Method | Path | Notes |
|---|---|---|
| GET | `/admin/customers` | Yajra listing; filters `search`, `statuses[]`, `created_at[from|to]`, `city_ids[]`, `deletion_requested` |
| POST | `/admin/customers/export` | queued CSV (masked phones), `customers.export`, audited; poll `admin/exports/{uuid}` |
| GET | `/admin/customers/{uuid}` | show page; tabs lazy-load `/tabs/{consents|devices|saved-places|deletion-requests|messages|audit}` (each its own DataTable) |
| POST | `/admin/customers/{uuid}/reveal` | `data = {phone, email, emergency_contact_phone}`, audited (`phone_revealed`) |
| POST | `/admin/customers/{uuid}/suspend` | `reason` (required), `expires_at` (`Y-m-d H:i`, admin timezone, future) |
| POST | `/admin/customers/{uuid}/restrict`, `/reactivate` | `reason` required |
| POST | `/admin/customers/{uuid}/messages` | `subject`, `body`, `reason`; audited (`message_sent`) |
| POST | `/admin/customers/{uuid}/export-data` | queued JSON personal-data export, audited against the customer |
| POST | `/admin/customers/{uuid}/deletion-requests/{request}/approve|reject` | `reason` required; approve anonymises (ADR-75) |

All write routes support Precognition (`422` / `204`). There is no create, update or delete route. Codes: `customer_status_transition_invalid`, `customer_anonymised`, `suspension_expiry_in_past`, `customer_has_active_bookings`, `customer_has_open_financial_items`, `customer_is_provider`, `deletion_request_not_pending`.

### 8.9 Provider onboarding (P2.1)

Mobile endpoints need a **`provider` token** (a customer token gets `403`). Reads (`GET`) are status-safe so a suspended driver can still see why; writes need an active account (`account.active`). Creating `POST`s take an `Idempotency-Key`. Another provider's vehicle, photo or document is always `403`, never a different data shape. IDs of vehicles, documents, photos and the application are uuids; cities and service/vehicle categories keep their numeric ids.

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/cities` | public, cacheable: `data = [{ id, name:{ar,en}, timezone, center }]` (active cities) |
| GET | `/api/v1/provider/application` | `data = { started: false }` before an application exists, else `{ started, id, status (ProviderStatus), status_reason (only for rejected / more_information_required), provider_type, city_id, name, date_of_birth, services:[{service_category_id, status}], editable, submitted_at, steps:{profile,services,vehicles,documents,declarations}, resume_step (a step key or "review"), can_submit, tasks:[{task, reference, vehicle_id}], required_documents:[{document_type:{id,code,name,description,applies_to,requires_expiry,requires_number,allowed_mimes,max_size_kb}, mandatory, state (null = not uploaded \| pending \| approved \| rejected \| expired), vehicle_id, expires_at, document}], expiring_documents:[…same], vehicles:[Vehicle], pending_policy_ids:[…], support:{phone,email,whatsapp} }`. Everything is computed from the data on every call |
| POST | `/api/v1/provider/applications` | `{ provider_type, city_id, name, date_of_birth (Y-m-d, not for company_fleet; >= 18 years), service_category_ids[]? }` -> `201` the application at `documents_pending`; `409 application_exists` |
| PATCH | `/api/v1/provider/application` | any subset of the same fields; allowed while the application is editable (an approved provider can only change `service_category_ids`: new ones start as `requested`); `422 application_locked` otherwise |
| POST | `/api/v1/provider/application/submit` | -> `under_review`; `422 application_incomplete` with `meta.tasks` when anything is open (also resubmission after `more_information_required` / `rejected`) |
| GET | `/api/v1/provider/eligibility` | `data = { eligible, reasons:[{ reason (EligibilityReason), reference }] }`, computed on each call |
| GET/POST | `/api/v1/provider/vehicles` | POST `{ vehicle_category_id, make, model, year, color, plate, passenger_capacity?, payload_kg?, volume_m3?, capabilities[] }` -> `201` a `draft` vehicle. The plate is normalised (latin digits, no spaces/punctuation, upper case; Arabic letters kept) and unique across providers: `422 vehicle_plate_taken` |
| GET/PATCH/DELETE | `/api/v1/provider/vehicles/{uuid}` | PATCH only while `draft` or `rejected` (`422 vehicle_not_editable`); DELETE only a never-submitted draft |
| POST/DELETE | `/api/v1/provider/vehicles/{uuid}/photos[/{photo}]` | multipart `photo` + `kind` (front, back, side, interior, other); re-encoded to JPEG, max 8 photos; photo URLs are signed (5 min) |
| POST | `/api/v1/provider/vehicles/{uuid}/submit-review` | needs the minimum photos and every mandatory vehicle document uploaded: `422 vehicle_incomplete` with `meta.tasks`; -> `under_review` |
| GET | `/api/v1/provider/documents`, `/{uuid}` | the driver's documents (provider and vehicle ones): `{ id, document_type, vehicle_id, status, rejection_reason, number_masked, issued_at, expires_at, mime, size_bytes, uploaded_at, reviewed_at, file_url }` |
| POST | `/api/v1/provider/documents` | multipart `document_type` (code), `file`, `number?`, `issued_at?`, `expires_at?` (Y-m-d), `vehicle_id?` (required for vehicle documents) -> `201`; rate limited (`rate_limit_provider_uploads_per_minute`); replaces a pending upload of the same type |
| GET | `/api/v1/provider/documents/{uuid}/file`, `/provider/vehicle-photos/{uuid}/file` | **signed (5 minutes) and owner-only**; `nosniff`, `private, no-store` |

Error codes (`errors._code`): `application_exists` (409), `application_locked`, `application_incomplete`, `vehicle_incomplete` (both with `meta.tasks`), `application_profile_incomplete`, `provider_birth_date_required`, `provider_underage`, `provider_city_unavailable`, `provider_service_unavailable`, `document_type_unknown`, `document_number_required`, `document_expiry_required`, `document_already_expired`, `document_issue_date_future`, `document_dates_inconsistent`, `document_vehicle_required`, `document_type_not_allowed`, `document_too_large`, `document_unreadable`, `document_image_too_small`, `document_image_too_large`, `document_pdf_active_content`, `document_infected` (all 422), `file_scanner_unavailable` (**503**), `vehicle_limit_reached`, `vehicle_not_editable`, `vehicle_not_deletable`, `vehicle_photo_limit_reached`, `vehicle_plate_taken`, `vehicle_plate_invalid`, `vehicle_year_invalid`, `vehicle_category_unavailable`. `OnboardingTask` values: `complete_profile`, `select_services`, `add_vehicle`, `add_vehicle_photos`, `submit_vehicle`, `upload_document`, `replace_rejected_document`, `renew_expired_document`, `fix_vehicle`, `accept_policies`, `submit_application`. `EligibilityReason` values: `not_approved`, `provider_suspended`, `account_blocked`, `document_missing`, `document_not_approved`, `document_expired`, `no_approved_vehicle`, `vehicle_not_approved`, `service_not_approved`, `vehicle_category_mismatch`. The driver terms (`driver_terms`) are part of `/me` `consents.pending` for the provider app and are accepted with `POST /me/consents`.

Admin (ADR-78..82; all write routes support Precognition, dangerous ones need a `reason`):

| Area | Permission | Routes |
|---|---|---|
| Providers | `drivers.view` (+ `view_pii` reveal, `suspend`, `customers.process_deletion` for deletion requests) | `GET /admin/providers` (filters `search`, `statuses[]`, `provider_types[]`, `service_category_ids[]`, `city_ids[]`, `online`, `expiring_within_days`), `GET /{uuid}`, lazy tabs `/{uuid}/tabs/{services\|vehicles\|documents\|history\|deletion-requests\|audit}`, `POST /{uuid}/reveal`, `/suspend` (`reason`, `permanent`, `expires_at`), `/reactivate`, `/services/{category}/approve\|reject`, `/deletion-requests/{request}/approve\|reject` |
| Verification | `drivers.view` / `verify` / `reject` | `GET /admin/verification` (filters `statuses[]`, `provider_types[]`, `city_ids[]`, `waiting_hours`, `assigned_admin_ids[]`, `unassigned`), `GET /{uuid}` (review screen), `POST /{uuid}/claim`, `/approve` (`approved_services[]`, `note?`), `/request-information` (`reason`, `document_issues[docUuid]`), `/reject` (`reason`) |
| Vehicles | `vehicles.view` / `approve` / `reject` | `GET /admin/vehicles` (filters `plate`, `vehicle_category_ids[]`, `statuses[]`, `provider_ids[]`), `GET /{uuid}`, tabs `documents`, `audit`, `POST /{uuid}/approve`, `/reject` (`reason`), `POST /admin/vehicle-photos/{uuid}/url` |
| Documents | `documents.view` (files), `drivers.verify` / `reject`, `vehicles.approve` / `reject` | `POST /admin/provider-documents\|vehicle-documents/{uuid}/url` (signed URL, `mode` view or download), `/approve`, `/reject` (`reason`); signed `GET /admin/provider-files/{provider-documents\|vehicle-documents\|vehicle-photos}/{uuid}` |
| Document types | `documents.manage_types` | `GET/POST /admin/document-types`, `GET /{id}`, `/{id}/edit`, `PUT /{id}` (`lock_version`), `/activate`, `/deactivate` (`reason`), `DELETE /{id}` (`reason`, only unused types), `GET /{id}/requirements`, `POST /{id}/requirements` (`reason`), `DELETE /{id}/requirements/{requirement}` (`reason`); lookup `providers` (`drivers.view`) |

Scheduled: `documents:check-expiry` (daily 02:00), `providers:reactivate-expired` (every minute).

### 8.10 Availability, live location and realtime (P2.2)

`provider` token only; reads are status-safe, writes need an active account. Positions are `{ lat, lng, recorded_at (ISO 8601, ms allowed), accuracy_m?, heading? (0-360), speed_mps?, source? (gps\|fused\|network) }`; `(0, 0)` and out-of-range values are `422`.

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/provider/availability` | `data = { availability, eligibility }`. `availability = { online, online_since, vehicle_id, last_offline_reason (OfflineReason\|null), active_booking_id, tracking: { state (TrackingState), interval_seconds, stale_after_seconds }, realtime: { channel: "private-provider.{uuid}" } }`; `eligibility = { eligible, reasons[] }` computed with the **working** scope (includes `location_stale` while offline, which is normal) |
| POST | `/api/v1/provider/availability/online` | `{ vehicle_id? (uuid), location: {position} }` -> the availability. `422 provider_not_eligible` with `meta.reasons = [{reason, reference}]`; `422 vehicle_not_usable` (field `vehicle_id`); `422 outside_service_area`. Idempotent: going online while online only updates the vehicle and position |
| POST | `/api/v1/provider/availability/offline` | `{ confirm?: bool }` -> the availability; `409 active_job_blocks_offline` when a job is in progress and `confirm` is not true (the job then keeps being tracked, `tracking.state` stays `active`/`to_pickup`) |
| POST | `/api/v1/provider/location` | `{ samples: [position] }` (1..`LOCATION_MAX_BATCH` = 20), rate limited (`rate_limit_location_per_minute`, default 30). `data = { received, applied, flagged, stale, tracking: { state, interval_seconds } }`. `flagged` samples (impossible speed, future timestamp) are stored with an anomaly flag and do not move the driver; `stale` are older than the last trusted position. `409 location_not_tracking` when the provider is neither online nor serving a booking |
| POST | `/api/v1/broadcasting/auth` | pusher-protocol private channel authorization (`socket_id`, `channel_name`) with the bearer token (`customer` or `provider`): `{ auth }` or `403` |

`TrackingState`: `off`, `idle`, `to_pickup`, `active` (sampling interval from `config/location.php`: 0 / 30 / 10 / 5 s). `OfflineReason`: `provider_choice`, `stale_location`, `no_longer_eligible`, `account_changed`. New `EligibilityReason` values (working scope only): `location_stale`, `active_job_exists`, `cash_debt_limit_exceeded`. New error codes: `provider_not_eligible`, `vehicle_not_usable`, `outside_service_area`, `active_job_blocks_offline`, `location_not_tracking`.

**Realtime (ADR-84..87).** Channels: `private-customer.{userUuid}`, `private-provider.{providerUuid}`, `private-booking.{bookingUuid}`, `private-admin.operations` (`bookings.view`). Every event is `{ booking_uuid, sequence, occurred_at, payload }` and a client refetches when `sequence` skips: `provider.availability_changed` (`{online, reason}`, provider channel, sequence per provider), `booking.requested` (provider channel; payload `{wave, distance_m, pickup_area:{lat,lng} (rounded ~1 km), search_expires_at, request}`; **no phone or address**), `booking.search_expanding` (booking channel; `{wave, radius_km}`), `booking.location_updated` (since P2.5 on `private-customer.{uuid}`, not the booking channel; `{lat, lng, heading, speed_mps, accuracy_m, recorded_at}`).

Scheduled: `providers:sweep-stale` (every 30 s), `locations:prune` (daily 03:30).

### 8.11 Reference pricing and customer bookings (P2.3)

`customer` token only. Reads are status-safe (a suspended or restricted customer can still read history); `POST` and `PATCH` use `account.active:strict` (a `restricted` account gets `403 account_restricted`) and **require an `Idempotency-Key`** (UUID; `422 idempotency_key_required`). Every read is scoped to the owner: another customer's booking answers `404`, exactly like a missing one. The server never accepts totals, statuses, snapshots, provider or payment state from the client (extra body fields are ignored).

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/routes/estimate?origin=lat,lng&destination=lat,lng&stops[]=&service_category_id=&service_details[key]=` | As before plus, when `service_category_id` is given, `price_guidance = { currency, low_minor, recommended_minor, high_minor, factors: [{code, label, amount_minor, quantity, unit}], formula_version, notice }` or `null` with `price_guidance_unavailable_reason` (`no_fare_policy`, `reference_estimate_disabled`). Guidance only: the customer chooses the fare. No demand, supply or time-of-day multiplier exists. `factors.code`: `base`, `distance` (unit `km`), `duration` (unit `min`), `stops`, `surcharge` (admin label), `minimum_fare` |
| POST | `/api/v1/bookings` | Body: `{ service_category_id, booking_type, pickup:{lat,lng,address?:{title,line}}, dropoff?:{...}, stops?:[{lat,lng,address?}], scheduled_at? (ISO 8601), passengers?, service_details?:{}, proposed_fare_minor, currency? (market currency), payment_method? (default cash) }`. `201` with the booking (below) and `meta.replayed` (true when the same key already created it). On-demand bookings are `searching_offers` at once (dispatch starts after commit); bookings with `scheduled_at` stay `draft` until `scheduled_search_lead_minutes` before the time |
| GET | `/api/v1/bookings` | Paginated (`meta.pagination`, `per_page` 1..50, default 20), newest first. Filters: `status[]`, `service_category_id`, `from`, `to` (`Y-m-d`), `in_flight` (bool). Rows: `{id, code, status, status_label, booking_type, service:{id,code,name{ar,en}}, proposed_fare:{amount_minor,currency}, payment:{method,status}, created_at}` |
| GET | `/api/v1/bookings/{booking}` | Booking uuid. Detail below |
| PATCH | `/api/v1/bookings/{booking}/request` | Any of `pickup`, `dropoff`, `stops` (array, may be empty), `scheduled_at`, `passengers`, `service_details`, `proposed_fare_minor`; at least one. Only while `searching_offers` / `offers_received`; the whole request is validated again, active offers are invalidated (`invalidated_request_changed`), the search window restarts and dispatch starts over. `409 booking_not_editable` once a provider is selected or the search ended; `409 booking_request_changed_meanwhile` when another change got in first |

**Booking detail** (`data`): `{ id, code (BK-XXXXXX), status, status_label, booking_type, service, proposed_fare, payment, created_at, pickup:{lat,lng,address}, dropoff, stops:[{position,lat,lng,address}], scheduled_at, passengers, service_details, reference_estimate (same shape as price_guidance plus available:false/reason), route:{distance_m,duration_s,polyline}, search_expires_at, request_version, offers:{active_count,best_fare_minor}, provider, vehicle, agreed_fare, agreed_scope, trip_pin (null until selection, P2.4), allowed_actions[] (computed by the server; update_request, select_offer), timeline:[{type: status|note, status, previous_status, reason (customer/system codes only), actor, at, sequence}] }`. The trip PIN and any provider data are never part of it before their stage.

**Errors (422 unless noted, `errors._code`; field in brackets).** `outside_service_area` [pickup], `category_unavailable` [service_category_id], `dropoff_required` / `dropoff_outside_service_area` [dropoff], `booking_type_not_allowed`, `scheduled_at_required` / `scheduled_at_not_allowed` / `scheduled_too_soon` / `scheduled_too_far` [scheduled_at], `stops_not_supported` / `too_many_stops` [stops], `passengers_required` / `passengers_mismatch` / `capacity_exceeded` [passengers], `luggage_exceeded`, `weight_exceeded`, `fare_below_minimum` / `fare_above_maximum` [proposed_fare_minor], `currency_mismatch`, `payment_method_unavailable` [payment_method], `service_details.*` (form schema errors, `validation_failed`), `503 maps_unavailable`, `409 customer_has_active_booking`, `403 account_restricted|account_suspended|...`, `422 idempotency_key_reused`.

**Statuses** (`BookingStatus`, 15 values, graph in Plan §7.2): `draft`, `searching_offers`, `offers_received`, `provider_selected`, `provider_heading_to_pickup`, `provider_arrived`, `in_progress`, `delivery_attempt_failed`, `completed_pending_payment`, `completed`, `disputed`, `cancelled_by_customer`, `cancelled_by_provider`, `cancelled_by_operations`, `expired_no_provider`. `PaymentStatus` (11 values) is a separate axis; P2.3 only writes `pending`.

**Realtime on `private-booking.{uuid}`** (sequence = `bookings.sequence`, shared with the status events): `booking.status_changed` (`{status, previous_status, request_version}`), `booking.request_changed` (`{request_version, changed[]}`), plus the P2.2 events. **Scheduled:** `bookings:expire-searches` every 5 s (expires searches whose window ended, releases scheduled bookings that are due); a delayed `ExpireBookingSearch` job ends each window at the exact second.

**Admin (no API):** Pricing > Fare policies (`fare_policies.manage`), the Bookings tab of a customer, filter "has active booking" in Customers.

### 8.12 Offers, counter-offers and selection (P2.4)

Money is integer minor units. Provider writes need an active account, `Idempotency-Key` (UUID) on the three create/choose calls and the `offers` rate limit (20/min) on the two provider submissions. Offer cards and offer events are private to the customer (ADR-96).

**Provider (`provider` token)**

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/provider/requests/available` | Open requests dispatched to the provider (searching, window open, not declined). `data` = masked cards `{ id, status, booking_type, service, pickup_area:{name,lat,lng} (2 decimals), dropoff_area, distance_to_pickup_m, proposed_fare, counter_offer:{allowed,min_minor,max_minor}, details:{distance_m,duration_s,stops,passengers,luggage_count,cargo_weight_kg,scheduled_at,payment_method,restricted_pickup}, expires_at, my_offer }`. Never address, name or phone. An ineligible provider gets `data: []`, `meta.eligible: false` and `meta.eligibility` (typed reasons). Listing marks the requests seen |
| POST | `/api/v1/provider/bookings/{booking}/offers/accept-customer-fare` | Body: `{ eta_minutes?, note? }` (the amount is the customer's fare, read server side). `201` (or `200` for a repeat / revision) with the offer |
| POST | `/api/v1/provider/bookings/{booking}/offers` | Counter-offer: `{ amount_minor, currency?, note?, scope? (object, max 4 KB), eta_minutes? }`. With an active offer on the booking this appends the next version (limited by `max_revisions`). `data` = offer + `fare_hints[]` + `earnings_preview {available:false, gross_minor, commission_minor:null, net_minor:null}`; `meta.replayed` |
| POST | `/api/v1/provider/offers/{offer}/withdraw` | While active. `409 offer_not_withdrawable` once chosen or closed; `403 offer_not_yours` |
| POST | `/api/v1/provider/bookings/{booking}/skip` | "Not for me": records the response, no penalty. `409 offer_withdraw_first` while an offer is active |
| GET | `/api/v1/provider/offers` | Own offers, newest first: `{ id, booking_id, status, state (pending/selected/rejected/expired/invalidated/withdrawn), kind, amount, version, revision_count, note, scope, eta_seconds, distance_m, expires_at, closed_at }` |
| GET | `/api/v1/provider/bookings/{booking}` | Full booking, only for the selected provider (others 404): addresses, stops, service details, `customer:{name,phone}`, `agreed_fare`, `agreed_scope` |

**Customer (`customer` token)**

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/bookings/{booking}/offers` | Active, unexpired offers, cheapest first: `{ id, status, kind, is_counter_offer, amount, version, revision_count, note, scope, eta_seconds, distance_m, expires_at, provider:{id,display_name,photo_url,rating:{average,count},completed_jobs}, vehicle:{category,make,model,year,color,plate,capabilities,passenger_capacity} }`; `meta.booking_status` |
| POST | `/api/v1/bookings/{booking}/offers/{offer}/accept` | Atomic selection (strict account, Idempotency-Key). `200` with the booking (`provider`, `vehicle`, `agreed_fare`, `agreed_scope`, `trip_pin` while the driver is on the way) and `meta.replayed` |
| POST | `/api/v1/bookings/{booking}/offers/{offer}/reject` | Declines one offer; the booking returns to `searching_offers` if it was the last one and the window is open |

`GET /bookings/{booking}` now also carries `provider`, `vehicle`, `agreed_fare`, `agreed_scope`, `trip_pin` and `allowed_actions` may contain `select_offer` (status `offers_received`).

**Errors.** 409: `booking_already_assigned`, `offer_expired`, `offer_not_active`, `offer_provider_unavailable` (customer accept/reject: `meta.booking` holds the fresh booking; `meta.booking_status` / `meta.offer_status` on all), `booking_not_open_for_offers`, `booking_search_expired`, `offer_not_withdrawable`, `offer_withdraw_first`, `offer_idempotency_conflict`. 403: `offer_not_dispatched`, `offer_not_yours`. 422: `provider_not_eligible` (`meta.reasons`), `offer_vehicle_missing`, `counter_offers_disabled`, `offer_amount_invalid`, `offer_amount_out_of_bounds` (`meta.min/max`), `offer_currency_mismatch`, `offer_amount_same_as_fare`, `offer_limit_reached`, `offer_revisions_exceeded`. Another customer's booking or offer is 404.

**Realtime (payload `{booking_uuid, sequence, occurred_at, payload}`)**: on `private-customer.{uuid}`: `booking.offer_received` / `booking.offer_updated` (`{offer: card, booking_id}`), `booking.offer_expired` (`{offer_id, booking_id, status, state}`), `booking.provider_selected` (`{offer: card, booking_id, status}`); on `private-provider.{uuid}`: `booking.offer_expired` and `booking.offer_updated` for the provider's own offers (`{offer_id, booking_id, status, state}`), `booking.provider_selected` for the winner (`{offer_id, booking_id, status, agreed_fare}`). The booking channel carries only `booking.status_changed`.

**Scheduler / jobs:** `offers:expire` every 5 s; `ExpireOffer` delayed per offer (critical queue). **QA:** `php artisan qa:race-accept {booking}` and `qa:race-offer {booking} {provider}` (local only).

### 8.13 Trip lifecycle, cancellation, share, ratings, reports (P2.5)

Money is integer minor units. Every write needs an `Idempotency-Key` (UUID); `cancel` with `preview=true` does not. Provider writes need an active account and ownership (another provider's booking is `404`). The booking is returned as the caller sees it, with server-computed `allowed_actions`. Errors use the envelope: `409 booking_transition_invalid`, `422 booking_pin_required` / `booking_proof_required`, `409 trip_pin_not_ready` / `trip_pin_locked`, `422 trip_pin_invalid` (`meta.attempts_remaining`), `422 trip_pin_not_required`, `409 booking_not_cancellable`, `422 cancellation_reason_not_allowed` / `cancellation_note_required` / `no_show_too_early`, `409 cancellation_fee_changed` (`meta.cancellation` = fresh decision), `409 share_not_available`, `422 rating_not_allowed` / `rating_window_closed` / `rating_tag_invalid` / `rating_disabled`, `409 rating_already_submitted`, `422 report_not_available` / `report_limit_reached`.

**Provider (`provider` token)**

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/provider/bookings/active` | The current job or `data: null`. Same shape as the show endpoint. The paginated history is `GET /api/v1/provider/bookings` (8.14) |
| GET | `/api/v1/provider/bookings/{booking}` | Now also `trip {heading_at, arrived_at, arrival_verified, free_wait_ends_at, started_at, completed_at, pin {required, verified, locked, attempts_remaining}, support_flagged}`, `cancellation_terms {free_wait_seconds, reasons[], provider_fees[], redispatch}`, `final_fare`, `allowed_actions[]` (`acknowledge`, `arrived`, `verify_start_pin`, `start`, `complete`, `cancel`, `rate_customer`, `report`) and `customer {name, phone (null unless direct exposure), phone_masked, contact_mode, rating}` |
| POST | `/api/v1/provider/bookings/{booking}/acknowledge` | `provider_selected` to `provider_heading_to_pickup` |
| POST | `/api/v1/provider/bookings/{booking}/arrived` | Body (all optional): `{ lat, lng, accuracy_m, recorded_at }`. Stores GPS evidence, starts the free-wait timer; never blocked by missing GPS |
| POST | `/api/v1/provider/bookings/{booking}/verify-start-pin` | `{ pin }` (4-8 digits). Wrong entries are counted; at `ride_pin_max_attempts` the start is locked (`409 trip_pin_locked`) and support is flagged |
| POST | `/api/v1/provider/bookings/{booking}/start` | Needs the verified PIN (when required) and the pickup proof (when required) |
| POST | `/api/v1/provider/bookings/{booking}/complete` | Needs the delivery proofs of the category (none for rides). Final fare = agreed fare snapshot; cash gives `payment_status = cash_due` and status `completed` |
| POST | `/api/v1/provider/bookings/{booking}/cancel` | `{ reason_code, note?, preview? }` (provider reasons: `customer_no_show`, `customer_unreachable`, `vehicle_problem`, `emergency`, `wrong_request_details`, `unsafe_pickup`, `other`). `meta {cancellation, committed, redispatched, replayed}`; with auto re-dispatch `data` is null and the booking goes back to searching |
| POST | `/api/v1/provider/bookings/{booking}/rating` | Rates the customer (setting `rating_customer_by_provider_enabled`): `{ stars 1-5, tags[], comment? }` |
| POST | `/api/v1/provider/bookings/{booking}/reports` | `{ category, description }` |

**Customer (`customer` token)**

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/bookings/{booking}/cancel` | `{ reason_code, note?, preview?, accepted_fee_minor? }` (customer reasons: `changed_mind`, `wrong_pickup`, `found_another_ride`, `driver_too_far`, `driver_asked_to_cancel`, `waited_too_long`, `other`). `preview=true`: `data` null, `meta.cancellation` = `{actor, stage, stage_label, fee {amount_minor, currency}, fee_formatted, free, basis, message, recipient, policy_id, rule, grace_ends_at, decided_at}` and `committed: false`. Commit: the booking (`cancellation {by, reason_code, fee, basis, cancelled_at}`); a due fee needs `accepted_fee_minor` equal to the preview |
| POST / DELETE | `/api/v1/bookings/{booking}/share` | `201 { url, expires_at }` (token shown once); `DELETE` stops sharing |
| POST | `/api/v1/bookings/{booking}/rating` | Once, finished trips, inside the window: `{ stars, tags[], comment? }` |
| POST | `/api/v1/bookings/{booking}/reports` | `{ category (safety, behaviour, fare_dispute, route_or_delay, lost_item, other), description }` |

`GET /bookings/{booking}` now also returns `trip {selected_at, heading_at, arrived_at, free_wait_ends_at, started_at, completed_at, pin {required, verified, locked}, provider_location_visible, support_flagged, rating_window_ends_at}`, `cancellation`, `final_fare`, `receipt` (completed bookings) and `allowed_actions` with `cancel`, `share`, `rate`, `report`; `trip_pin` disappears once the driver entered it. Provider ratings carry `provisional` (`rating {average, count, provisional}`).

**Public page.** `GET /t/{token}` (HTML, noindex) and `GET /t/{token}/data` (JSON poll): status, provider first name, vehicle, last position while fresh, planned duration, expiry. Invalid / expired / revoked / finished: the same 404.

**Realtime (payload `{booking_uuid, sequence, occurred_at, payload}`).** `booking.status_changed` (`{status, previous_status, request_version, actor}`) on `private-customer.{uuid}`, `private-provider.{uuid}` (assigned and previous provider) and `private-booking.{uuid}`; `booking.location_updated` on `private-customer.{uuid}` only (selection to completion); `booking.support_flagged` (`{reason}`) on both party channels. After a provider cancel with re-dispatch the customer sees `provider_selected` -> `searching_offers` and the old provider's app gets the same status event.

### 8.14 Provider job history and admin operations (P2.8)

**Provider (`provider` token, read-only, status-safe)**

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/provider/bookings` | Paginated job history, newest first (bookings the provider holds or held to their end). Query `page`, `per_page` (1-50, default 20). Item: `{id, code, status, status_label, booking_type, service {id, code, name}, customer {name}, agreed_fare, final_fare, payment {method, status}, selected_at, completed_at, cancelled_at, created_at}`; no phone numbers, no addresses (use the detail endpoint). Standard pagination envelope (`meta.pagination`) |

**Admin panel (session guard, not part of the mobile API).** Pages and AJAX endpoints under `/admin`, all behind `can:` middleware and, for writes, FormRequests with a mandatory `reason` (min/max from `config('admin.reason')`):

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/admin/bookings`, `/admin/bookings/{uuid}` | `bookings.view` | Listing (Yajra, Appendix C filters) and show page; lazy tabs under `/admin/bookings/{uuid}/tabs/{timeline,offers,dispatch,snapshots,safety,ratings,audit}`. Exact addresses/coordinates need `bookings.view_pii` (the view is audited); phones are always masked |
| GET | `/admin/bookings/no-offer-queue` | `bookings.view` | Searching bookings without an active offer for `booking.operations.no_offer_after_seconds`, plus `expired_no_provider` within `expired_window_hours` |
| GET | `/admin/bookings/{uuid}/override-forms/{form}` | `bookings.view` (+ the action's ability) | Modal fragment of an operator action (`cancel_by_operations`, `extend_search`, `redispatch_now`, `force_transition`, `lift_pin_lock`, `contact`); `?context=queue` reloads the queue table after success |
| POST | `/admin/bookings/{uuid}/cancel` | `bookings.cancel` | `{reason, fee: waive\|apply_policy}`; ends as `cancelled_by_operations`, closes active offers, fee decision stored on the booking |
| POST | `/admin/bookings/{uuid}/extend-search` | `bookings.manage` | `{reason, extend_seconds 30..1800}` while searching |
| POST | `/admin/bookings/{uuid}/redispatch` | `bookings.manage` | `{reason}` restarts dispatch from wave 1 while searching; offers untouched |
| POST | `/admin/bookings/{uuid}/force-transition` | `bookings.assign_override` | `{reason, target_status}`; only targets the transition graph allows (heading, arrived, in progress, delivery failed, completed(-pending-payment), disputed, expired). Starting the trip without the verified PIN also needs `bookings.pin_override` |
| POST | `/admin/bookings/{uuid}/lift-pin-lock` | `bookings.pin_override` | `{reason}`; clears attempts, lock and the PIN support flag; the PIN is never shown |
| POST | `/admin/bookings/{uuid}/contact` | `bookings.manage` | `{reason, subject, body}`; in-app message to the customer |
| GET | `/admin/operations/map`, `/admin/operations/map/snapshot` | `bookings.view` | Live map page and JSON snapshot (`providers[] {id, lat, lng, category, busy, age_s}`, `bookings[] {id, code, lat, lng, category, status, status_label}`, `generated_at`, `truncated`) |
| GET | `/admin/offers` | `offers.view` | Offers monitor; the DataTables JSON also carries `stats {submitted, selected, conversion, flagged, response}` |

Errors (envelope): `403 override_forbidden`, `409 override_not_available` (status does not allow it), `422 override_extension_invalid`, `422 override_target_invalid`, `422 override_fee_policy_unavailable`, `422 override_reason_required`, plus the guard errors of the transition service.

**Realtime (admin).** Private channel `admin.operations` (authorized for admins with `bookings.view` through `/broadcasting/auth` on the admin session). Events (payload envelope `{booking_uuid: null, sequence, occurred_at, payload}`, one stream-wide sequence): `operations.booking_changed` `{uuid, status, previous_status}`, `operations.provider_changed` `{uuid, online}`, `operations.provider_moved` `{uuid, lat, lng, busy}` (throttled per provider). Hints only: the map refetches its snapshot on a gap.



### 8.15 Payments, tips and cash (P3.2)

Money is integer minor units. Every write needs an `Idempotency-Key` (UUID). The trip amount is never sent by the client: it is the booking's stored price breakdown. A payment becomes `paid` only from the gateway's verified webhook or a server-side gateway query (never from the app returning from the payment page). With no gateway configured, online endpoints answer `503 payments_not_configured` and booking creation accepts only the base methods (cash) until one is configured.

**Customer (`customer` token)**

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/bookings/{booking}/payment-intents` | Body `{payment_method_id?, method? (card\|mobile_wallet), wallet_phone?}`. Booking must be `completed_pending_payment` with a card / wallet method. Creates the payment (`pending` / `requires_action`) and returns it with `action {client_secret, url}` to open the gateway UI. A second call while an attempt is open returns the same attempt (200, `replayed`) instead of charging twice; `422 payment_needs_review` while a previous attempt awaits an operator. `201` on create |
| POST | `/api/v1/bookings/{booking}/tips` | Body `{amount_minor, payment_method_id?, method?, wallet_phone?}`. After the booking is `completed`, only if the service allows tipping and `tips_enabled`; never preselected; `amount_minor` between 1 and `tip_max_basis_points` of the fare; one tip per booking (`422 tip_already_given`). Commission-free by default (rule version may set a tip commission) and posted to the provider wallet when paid |
| POST | `/api/v1/payments/{payment}/refresh` | The app asks the platform to re-check one of the customer's own payments with the gateway (after returning from the payment page, or the app was closed). Read-only until the gateway itself says paid. Throttled 30/min. Returns the payment |
| GET | `/api/v1/me/payment-methods` | Active saved methods `{id, type, brand, last4, is_default, status, created_at}`. The gateway token never leaves the server |
| POST | `/api/v1/me/payment-methods/setup-intent` | Body `{type: card\|mobile_wallet}`. Creates a `pending` method and returns `action`; the method becomes `active` only when the gateway's verified webhook reports it. `422 payment_setup_not_supported` for gateways that save cards during a payment (Paymob) |
| DELETE | `/api/v1/me/payment-methods/{paymentMethod}` | Forgets the token (best effort at the gateway); another customer's method is a 404 |

Payment resource: `{id, booking_id, kind (trip\|tip), method, gateway, status {code, label}, amount {minor, currency}, refunded_minor, attempt, failure_code, saved_method, expires_at, paid_at, created_at, action}`. `status.code` is one of `pending, requires_action, authorized, paid, failed, partially_refunded, refunded, cash_due, cash_collected, reconciliation_required, not_required`. The booking's `payment {method, status}` mirrors its trip payment; `allowed_actions` gains `pay` and `tip`.

**Provider (`provider` token)**

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/provider/bookings/{booking}/cash-collected` | The provider confirms the cash of their completed cash booking: `cash_due` -> `cash_collected` (replay answers 200 with `meta.replayed`). `422 cash_not_collectable` otherwise; another provider's booking is a 404. `allowed_actions` gains `confirm_cash_collected` |

**Gateway callback (no user auth, signature only)**

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/payment-gateways/{gateway}/webhook` | `gateway` is `sandbox` (local/testing) or `paymob`; only the configured one answers (others 404). Body as sent by the gateway, verified over the raw body (constant-time HMAC; replay window where the gateway signs a timestamp). Answers `{received, outcome}`: `200` processed / duplicate / ignored, `202` unmatched or amount mismatch, `400` replay window, `401` invalid signature, `500` processing failed (the gateway retries). Own rate limit `payment-webhooks` (setting `rate_limit_payment_webhooks_per_minute`) |

**Realtime.** `payment.status_changed` `{payment_uuid, kind, payment_status}` on `private-customer.{uuid}` and `private-provider.{uuid}` (payload envelope as the booking events, sequence = the booking's counter). A hint: refetch the booking.

**Errors (envelope `errors._code`).** `payments_not_configured` (503), `payment_gateway_unavailable` (503), `payment_method_unsupported`, `booking_not_payable`, `payment_nothing_to_pay`, `payment_needs_review`, `wallet_phone_required`, `payment_method_not_found`, `tips_not_available`, `tip_booking_not_completed`, `tip_amount_invalid`, `tip_amount_too_large` (`meta.max`), `tip_already_given`, `cash_not_collectable`, `payment_transition_invalid` (409).

**Admin panel (session guard).** Pages under `/admin`, behind `can:` policies; writes use FormRequests (Precognition) with a mandatory `reason`:

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/admin/payments`, `/admin/payments/{uuid}` | `payments.view` (or `refunds.request` / `refunds.approve`) | Listing (filters: status, kind, method, gateway, reference, created range, amount range) and show page (details, event timeline, refunds tab, activity); payment tabs on the booking and customer pages (`/admin/bookings/{uuid}/tabs/payments`, `/admin/customers/{uuid}/tabs/payments`) |
| POST | `/admin/payments/{uuid}/refunds` | `refunds.request` | `{amount_minor, reason, idempotency_key}`; only collected online payments (`paid`, `partially_refunded`); at most what is still refundable. At or above `refund_dual_approval_threshold_minor` the refund waits for a second admin, below it runs on the requester's authority |
| POST | `/admin/payments/{uuid}/sync` | `finance.reconcile` | `{reason}` asks the gateway and applies its answer (verified query) |
| GET | `/admin/payment-refunds`, `/admin/payment-refunds/{uuid}` | as payments | Refund listing and page |
| POST | `/admin/payment-refunds/{uuid}/approve`, `/reject` | `refunds.approve` | Approve (optional note) or reject (`reason`); never by the requester (`403 refund_self_approval`). Approval sends the refund to the gateway; the ledger gets the compensating entry when the gateway confirms |
| GET | `/admin/gateway-webhook-events`, `/{uuid}` | `payments.view` | Every callback received with its outcome (failures highlighted, filter "needs attention") |
| POST | `/admin/gateway-webhook-events/{uuid}/reprocess` | `finance.reconcile` | `{reason}` processes a failed / unmatched / amount-mismatch event again (audited) |
| GET / POST | `/admin/payment-reconciliation`, `/run`, `/{uuid}` | `finance.view` or `payments.view`; run: `finance.reconcile` | Reports of `payments:reconcile` |

Console: `php artisan payments:reconcile [--sweep-only]` (scheduled: sweep every 5 minutes, full comparison hourly).

### 8.16 Coupons (P3.3)

Money is integer minor units. **A discount is never sent by the client**: the booking request carries only a `coupon_code`, the server finds the coupon, evaluates its rules, reserves it under a lock and later prices the trip itself. Every response with a coupon decision carries an explanation code (`reason_code`).

**Customer (`customer` token)**

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/coupons/validate` | A quote, nothing is reserved. Body `{coupon_code, service_category_id, proposed_fare_minor, payment_method?, pickup?: {lat, lng}, scheduled_at?}`. Always `200` with `{valid, reason_code, message, coupon, discount: {amount_minor, currency}}`; an ineligible coupon is an answer (`valid: false`), not an error. The attempt is logged. Throttled 30/min |
| GET | `/api/v1/me/coupons` | `{available, history}`: the active automatic campaigns the customer can still use, and their last 20 coupon uses `{id, status (reserved\|committed\|released\|reversed), coupon {code, title}, booking, discount {amount_minor, currency, final}, reserved_at}`. Codes are never listed |
| POST | `/api/v1/bookings` | Accepts `coupon_code` (letters, digits, `-`, `_`, max 40). A refused code answers `422` with `errors._code = coupon_<reason>`, `field coupon_code` and `meta.reason_code`; the booking is not created. With no code the best active automatic campaign is applied silently. Any `discount*` field in the body is ignored |

Coupon summary: `{id, code, automatic, title {ar,en}, description, terms, summary, discount_type, starts_at, ends_at}`. Budget, counters and funding are never returned.

Booking `receipt` (completed trips) gains `coupon_applied`, `discount {amount_minor, currency}` and `customer_payable {amount_minor, currency}`. A booking whose coupon covers the whole payable completes by itself: `payment.status = not_required`, status `completed`, no payment intent (`POST .../payment-intents` would answer `payment_nothing_to_pay`).

**Reason codes** (`reason_code`, error `coupon_<code>`): `eligible, not_found, not_active, not_started, expired, exhausted, category_not_eligible, service_family_not_covered, service_category_not_covered, service_excluded, vehicle_not_covered, city_not_covered, zone_not_covered, payment_method_not_covered, new_customers_only, returning_customers_only, outside_schedule, below_minimum_fare, customer_limit_reached, global_limit_reached, budget_exhausted, stacking_not_allowed, currency_mismatch, no_discount`.

**Admin panel (session guard).** Pages under `/admin/coupons`, behind `can:` policies; writes use FormRequests (Precognition) and AJAX forms:

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/admin/coupons`, `/admin/coupons/{uuid}` | `coupons.view` (or `coupons.manage`) | Listing (collapsed filters: code, status, discount type, funding source, service family, active on date, validity range) and page (details with the customer preview, versions, redemptions, attempts, performance, test eligibility, activity) |
| POST / PUT | `/admin/coupons`, `/admin/coupons/{uuid}` | `coupons.manage` | Create (as a draft) / edit with the eligibility builder. Changing the money terms of a non-draft coupon adds a version and needs a `reason` |
| POST | `/admin/coupons/{uuid}/publish`, `/resume` | `coupons.manage` | Draft or paused -> active (scheduled when the start is ahead) |
| POST | `/admin/coupons/{uuid}/pause`, `/archive` | `coupons.manage` | Mandatory `reason`, audited |
| POST | `/admin/coupons/{uuid}/test` | `coupons.view` | `{customer_id, service_category_id, fare_minor, payment_method?, city_id?, zone_id?, at?}` -> decision, reason and per-funder amounts; logged as an `admin_test` attempt |
| GET | `/admin/coupons/{uuid}/tabs/{redemptions,attempts,performance}` | `coupons.view` | Lazy tabs (DataTables with collapsed filters) |
| POST | `/admin/coupons/export` | `coupons.export` | Queued CSV of the listing with its filters, audited |
| GET | `/admin/customers/{uuid}/tabs/coupons` | `customers.view` + `coupons.view` | The customer's coupon uses |

Console: `php artisan coupons:sync-status` (every minute): scheduled -> active, open -> expired, active -> exhausted when the committed uses or spend reach the limit or budget, exhausted -> active when capacity returned, and release of reservations whose booking ended without a trip.

### 8.17 Earnings, payouts and wallet (P3.4)

Provider endpoints need a `provider` token; reads are status-safe, writes need an active account. Money is integer minor units. Customer endpoints need a `customer` token.

| Method | Path | Notes |
|---|---|---|
| GET | `/provider/earnings/summary?period=day\|week\|month&date=` | Totals (jobs, gross, commission, provider-funded and platform-funded discount, tips, net, total earned, cash collected), `by_category`, daily `series` and the `wallet` position (`balance_minor`, `reserved_minor`, `available_minor`, `cash_debt_minor`, limits). Week starts Saturday, market timezone. |
| GET | `/provider/earnings/transactions?from=&to=&per_page=` | Booking-level breakdown (paginated): gross fare, commission, discount by funder (a provider-funded coupon is already inside `provider_net`), tax, tip, `provider_net`, `provider_earning`, `cash_collected`, payment status. |
| GET | `/provider/wallet/transactions` | Wallet statement lines (paginated): signed `change`, `balance_after`, type, description. |
| GET | `/provider/payout-methods` | The provider's methods: type, label, `last4`, `verified`, `is_default`. Details are never returned. |
| POST | `/provider/payout-methods` | `Idempotency-Key`. `{type: bank_account\|mobile_wallet, label, details: {holder_name, bank_name, account_number} \| {holder_name, wallet_number}, is_default?}`. Details are stored encrypted; finance must verify before use. |
| PATCH / POST / DELETE | `/provider/payout-methods/{id}`, `/{id}/default`, `DELETE /{id}` | Changing the details drops the verification. 409 `payout_method_in_use` while an open payout uses the method. |
| GET | `/provider/payouts` | History (paginated) with `meta.balance` (`wallet_minor`, `reserved_minor`, `available_minor`, `min_minor`, `max_minor`). |
| GET | `/provider/payouts/{id}` | One request: amounts, `status`, masked method, transfer reference, rejection / failure reason, timestamps. |
| POST | `/provider/payouts` | `Idempotency-Key`. `{amount_minor, payout_method_id}`. The fee and the approval path are the server's. The amount is reserved in the ledger in the same transaction (the wallet balance drops by it). 422 `payout_amount_below_minimum` / `payout_amount_above_maximum` (`meta.min`, `meta.max`), `payout_insufficient_balance` (`meta.max`), `payout_method_not_verified`, `payout_provider_not_approved`. A replay with the same key and body returns the first request; a different body is 422 `idempotency_key_reused`. |
| POST | `/provider/payouts/{id}/cancel` | `Idempotency-Key`. Only while `requested` / `under_review`; the reservation is reversed. 409 `payout_not_cancellable`. |
| GET | `/me/wallet` | Customer wallet(s): `{id, currency, balance}`. |
| GET | `/me/wallet/transactions` | Customer wallet statement (paginated), same line shape as the provider's. |

Payout statuses: `requested`, `under_review`, `approved`, `processing`, `paid`, `failed`, `rejected`, `cancelled`, `reversed`.

Admin (session + Precognition; permission in brackets):

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/admin/payout-requests`, `/{uuid}` | `payouts.view` | Listing (collapsed filters: status, provider, amount range, requested range, approver) and detail with timeline. |
| POST | `/admin/payout-requests/{uuid}/review` | `payouts.review` | requested -> under_review. |
| POST | `/admin/payout-requests/{uuid}/approve` | `payouts.approve` | Optional `reason` note. At or above the threshold the first call records the first approval; a DIFFERENT admin's second call approves (403 `payout_same_approver`). |
| POST | `/admin/payout-requests/{uuid}/reject` | `payouts.approve` | Mandatory `reason`; reservation reversed. |
| POST | `/admin/payout-requests/{uuid}/processing`, `/paid`, `/failed` | `payouts.review` | `paid`: `transfer_reference` (required), `evidence` (PDF / image, optional), `reason` note; `failed`: mandatory `reason`, reservation reversed. 409 `payout_state_conflict` from any other state. |
| POST | `/admin/payout-requests/{uuid}/reverse` | `payouts.approve` | Paid transfer returned by the bank; mandatory `reason`. |
| GET | `/admin/payout-requests/{uuid}/evidence` | `payouts.view` | The receipt (private disk). |
| GET / POST | `/admin/payout-batches`, `/{uuid}` | `payouts.view`; create `payouts.review` | `POST /admin/payout-batches` (mandatory note) batches all approved payouts of one currency. |
| POST | `/admin/payout-batches/{uuid}/export` | `payouts.review` | Streams the transfer file (decrypted destinations), audited, marks the batch exported. |
| POST | `/admin/payout-methods/{uuid}/verify`, `/unverify` | `payouts.review` | Mandatory reason / note. |
| GET / POST | `/admin/adjustment-requests`, `/{uuid}` | `wallets.view`; create `wallets.adjust` | `{ledger_account_id, direction, amount_minor, reference?, reason, idempotency_key}`. |
| POST | `/admin/adjustment-requests/{uuid}/approve`, `/reject`, `/cancel` | `wallets.approve_adjustment`; cancel: the requester | Never the requester (403 `adjustment_self_approval`); approval posts the ledger entry. |
| GET / POST | `/admin/cash-reconciliations`, `/{uuid}` | `finance.view`; open / settle `finance.reconcile` | Open: `{provider_id, period_from, period_to, notes?, idempotency_key}`. Settle: `{amount_minor, method, reference, reason}` (422 `cash_settlement_exceeds_debt`). |
| POST | `/admin/cash-reconciliations/{uuid}/cancel` | `finance.reconcile` | Mandatory reason. |
| GET | `/admin/providers/{uuid}/statement?from=&to=` | `finance.view` + `reports.export` | Wallet statement CSV with opening / closing balance and the ledger balance; audited. |
| GET | `/admin/providers/{uuid}/tabs/earnings`, `/tabs/payouts` | `drivers.view` (+ `payouts.view` for payouts) | Lazy tabs. |
| GET | `/admin/lookups/finance-providers`, `/wallets` | `payouts.view` or `finance.view`; `wallets.adjust` | Remote selects for the finance filters and the adjustment form. |

## P3.5 — Cancellation fees, no-shows, fare adjustments, receipts

Cancel (`POST /bookings/{b}/cancel`, `POST /provider/bookings/{b}/cancel`) now accepts optional `lat`, `lng`, `accuracy_m`, `recorded_at` (GPS evidence of a no-show). New reasons: customer `driver_no_show`, provider `customer_no_show` (existing). The decision gains `payer`, `provider_share_bp`, `penalty` and `evidence`; fees are posted to the ledger on commit. Errors: `provider_no_show_too_early`, `cancellation_cooldown_active` and `customer_fee_debt_outstanding` (422 on `POST /bookings`).

| Method | Path | Notes |
|---|---|---|
| GET | `/bookings/{b}/adjustments`, `/provider/bookings/{b}/adjustments` | Adjustments with `requested_by_me`, `can_answer`, `can_withdraw`. |
| POST | `/bookings/{b}/adjustments`, `/provider/bookings/{b}/adjustments` | Idempotency-Key. `{type: extra_stop|waiting|toll_parking|scope_change, amount_minor?, reason, details?, evidence?{reference,note}}`; `waiting` may omit the amount when the policy has a per-minute rate. Errors: `adjustment_not_allowed_now` (409), `adjustment_open_limit` (409), `adjustment_amount_invalid`, `adjustment_evidence_required`, `adjustment_type_not_allowed` (422). |
| POST | `.../adjustments/{a}/approve`, `/reject` | Idempotency-Key, `{note?}`. Only the other party (403 `adjustment_needs_counterpart`); 409 `adjustment_not_pending` / `adjustment_expired`. |
| POST | `.../adjustments/{a}/withdraw` | The requester only. |
| GET | `/bookings/{b}/receipt`, `/provider/bookings/{b}/receipt` | Immutable receipt (customer: fare lines; provider: earnings) + `html_url` (signed, 15 min, `lang`). 409 `receipt_not_available` before completion. |
| GET | `/receipts/{uuid}?as=&lang=&signature=` | Printable HTML receipt (ar RTL / en), noindex. |

Booking resources add `adjustments_total` and `current_total`; `allowed_actions` add `request_adjustment` and `view_receipt`. Realtime: `booking.adjustment_changed` on `private-customer.{uuid}` and `private-provider.{uuid}` with `{adjustment_uuid, event, status, type, requester, amount_minor, currency, adjustments_total_minor}`.

Admin: `GET /admin/booking-adjustments`, `/{uuid}` (`bookings.view`), `POST /admin/booking-adjustments/{uuid}/void` (`bookings.manage`, reason), booking tabs `adjustments` and `receipt`, `POST /admin/cancellation-policies/{id}/preview` (calculator).


## P3.6 — Notifications (inbox, settings, devices)

Both tokens (`customer`, `provider`); status-safe (a suspended account can still read why). Everything is scoped to the caller and to the app of the token: a foreign id answers `404`.

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/me/notifications` | The inbox of the calling app, newest first. Query `page`, `per_page` (1-50, default 20), `unread=1`. Item `{id, event, category, title, body, read, link {type, id}\|null, created_at, read_at}`; `link.type` is `booking`, `payout`, `documents`, `application`, `wallet`, `support`, `message`, `inbox`; the app must cope with a target that no longer exists. `meta.pagination` and `meta.unread_count` |
| POST | `/api/v1/me/notifications/{id}/read` | Idempotent. `data` = the entry, `meta.unread_count` |
| POST | `/api/v1/me/notifications/read-all` | `meta.unread_count = 0` |
| GET | `/api/v1/me/notification-preferences` | `data.categories[] {category, label, description, essential, opt_in_required, channels[] {channel (push\|sms\|email), enabled, locked}}`. Essential categories (booking, account, payments, safety) have push `locked: true`; marketing is off until switched on |
| PUT | `/api/v1/me/notification-preferences` | `{preferences: [{category, channel, enabled}]}` (max 64). `422 notification_essential_locked` when a locked channel is switched off. Response = the new matrix |
| POST | `/api/v1/me/devices` | (P1.3) now the way push tokens are registered: `device_id`, `platform`, `app_version?`, `push_token?`, `locale?` |

**Push payload (FCM data).** `notification_id`, `event`, `category`, `link_type`, `link_id` (strings, no personal data); title/body are the lock-screen text of the recipient's language. Android channel id `platform_default`.

**Relation to `GET /me/messages`.** Help messages from support stay readable there (customer only); each one also creates an inbox entry (`link.type = message`, `link.id` = the message id). See ADR-132.

Admin (session guard): `/admin/notification-templates` (CRUD without delete, activate / deactivate, `POST /preview`), `/admin/notification-deliveries` (read-only, `POST /{uuid}/retry` with a reason), `/admin/push-campaigns` (CRUD, `schedule`, `send`, `cancel`), `POST /admin/notifications/{uuid}/read` and `/read-all` (the bell). Permissions: `notifications.manage`, `push_campaigns.manage`.

## P3.7 — Booking chat and support

Tokens: `customer` and `provider` (the driver paths are `/provider/...`). Everything is scoped to the caller; a non-participant gets `403`, an unknown booking `404`. Writes need an `Idempotency-Key`. Error codes: `chat_forbidden`, `chat_closed`, `chat_empty`, `chat_message_too_long`, `ticket_category_not_allowed`, `ticket_limit_reached`, `ticket_rate_limited`, `ticket_link_invalid`, `ticket_closed`, `ticket_empty_reply`, `ticket_too_many_attachments`, `appeal_not_available`, `appeal_already_open`, plus the upload codes of documents (`document_type_not_allowed`, `document_infected`, ...).

### Chat (ADR-137)

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/bookings/{booking}/messages` · `/api/v1/provider/bookings/{booking}/messages` | Latest 50, oldest first. `?after={message}` newer ones, `?before={message}` older ones. Item `{id, sender (customer\|provider), mine, body\|null, attachment {mime, size_bytes, url (signed, 5 min, needs the token)}\|null, purged, read_at, created_at}`. `meta`: `role`, `can_send`, `closes_at`, `unread`, `has_more`, `max_chars` |
| POST | same path | multipart or JSON: `body` and/or `attachment` (one image). Needs `Idempotency-Key`; 30/min. `403 chat_closed` after the window. Phone-like digit runs are masked. `201` + the message |
| POST | `.../messages/read` | `{up_to?}`; marks the other side's messages read; `meta.marked_read` |
| GET | `.../messages/{message}/attachment` | signed + token; participants only |

Realtime (own channel only, never text): `booking.message_received` `{message_uuid, sender, has_attachment, unread}` and `booking.messages_read` `{reader, read_count}`. Push event `booking.message_received` (link `chat` -> booking id). `allowed_actions` of the booking include `chat` and `appeal`.

### Support (ADR-138..140)

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/support/categories` | Categories the app may choose (`value`, `label`) |
| GET | `/api/v1/support/tickets` | Own tickets of this app, newest activity first; `status=active\|finished`, `page`, `per_page`. Item `{id, number, category, category_label, status, status_label, subject, source, booking_id, booking_code, can_reply, resolution, last_message_at, created_at}` |
| POST | `/api/v1/support/tickets` | `category`, `subject`, `body`, `booking_id?`, `payment_id?`, `payout_id?` (ids the caller owns), `attachments[]` (max 3). `201` + ticket with `messages` |
| GET | `/api/v1/support/tickets/{ticket}` | Ticket + public thread `messages[] {id, from (me\|support), body, attachments[] {id, name, mime, size_bytes, url}, created_at}`. Internal notes, assignee, group and SLA are NEVER returned |
| POST | `/api/v1/support/tickets/{ticket}/messages` | `body`, `attachments[]`; reopens a resolved ticket within 7 days |
| GET | `/api/v1/support/tickets/{ticket}/attachments/{attachment}` | signed + token |
| POST | `/api/v1/bookings/{booking}/cancellation-appeal` · `/api/v1/provider/bookings/{booking}/cancellation-appeal` | `{reason}` (10+ chars): ticket with the fee decision snapshot |
| POST | `/api/v1/bookings/{booking}/reports` (and the driver twin) | now creates a ticket; response keeps `{id, category, status (open\|closed)}` and adds `ticket_id`, `ticket_number` |

Admin (session guard): `/admin/support-tickets` (inbox + `{ticket}`, `activity`, `chat` (audited), `attachments/{uuid}`, `POST messages|claim|assign|escalate|resolve|reopen|priority|link-finance`), `/admin/macros` (CRUD without delete, activate / deactivate), `/admin/customers/{customer}/tabs/tickets`. Permissions: `support.view`, `support.manage`, `support.view_all`, `macros.manage`.



## P3.8 — Wallet debt settlement and feature flags

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/me/wallet/settle-debt` | Customer token, `Idempotency-Key`. Body as the payment intent (`payment_method_id?`, `method?`, `wallet_phone?`); the amount is the server's current wallet debt. Returns a payment (`kind: debt`) with `action`; `201`, or `200` when an open attempt is returned. `422 payment_nothing_to_pay` without debt. Paid only from the verified gateway answer, which credits the wallet. |

`GET /config/app` `features` now holds `wallet` and `offers` (settings `feature_wallet_enabled`, `feature_offers_enabled`, default true). Payment resource `kind` may be `debt`.


## P4.1 — Courier / parcel (ADR-146..151)

`POST /api/v1/bookings` (parcel): `booking_type: parcel`, `service_details.recipient {name, phone}` (+ optional `sender`), and **`restricted_goods_policy_version`** (uuid of the current `restricted_goods` policy from `GET /policies/restricted_goods`). Errors: `422 restricted_goods_ack_required | restricted_goods_ack_stale | restricted_goods_policy_unavailable`. Booking responses (customer and provider) add `contacts[] {role, name, phone, phone_masked, contact_mode}` (provider: only the selected provider; `phone` only with direct exposure and while the job is in flight; the customer always gets `phone: null`) and `delivery` (null for rides): `{custody {status,label}, proofs {pickup_required, delivery_required[], return_required[], present {pickup[],delivery[],return[]}, current_stage}, recipient_otp {required, issued, verified, locked, delivery_status, attempts_used, attempts_remaining, max_attempts, resend_available_at, resends_left}, attempts {count, max, retry_available, pending, items[]}, awaiting_resolution, restricted_goods_acknowledged_at}`. The timeline's `note` entries carry `custody` (`picked_up` ... `returned`). The recipient code is never returned. New `allowed_actions`: provider `upload_proof`, `mark_in_transit`, `arrived_dropoff`, `verify_recipient_otp`, `resend_recipient_otp`, `report_delivery_attempt`, `resolve_delivery_attempt`; customer `view_proofs`, `resolve_delivery_attempt`.

### Provider (`/api/v1/provider`, all POSTs need `Idempotency-Key`)

| Method | Path | Notes |
|---|---|---|
| GET | `/bookings/{booking}/proofs` | Own job's proofs `{id, type, type_label, stage, taken_at, details, file {url (signed, 5 min), mime, size_bytes}}` |
| POST | `/bookings/{booking}/proofs` | `type` = `photo\|signature` (multipart `file`), `checklist` (`items[] {code, checked}`), `weight` (`weight_kg`), optional `note`. Stage is derived by the server (`409 proof_not_allowed_now` outside arrived / in progress). `201` + `{proof, booking}`; same picture again = `200` replay. `409 proof_already_recorded \| proof_limit_reached`; `422 proof_type_not_uploadable \| proofs_not_supported \| proof_checklist_invalid \| proof_weight_*`; file errors as documents; `503 file_scanner_unavailable`. 20/min |
| GET | `/bookings/{booking}/proofs/{proof}/file` | signed + token |
| POST | `/bookings/{booking}/custody` | `{event: in_transit\|arrived_dropoff}`; `409 custody_transition_invalid \| custody_not_active` |
| POST | `/bookings/{booking}/verify-recipient-otp` | `{code}`; `422 recipient_otp_invalid` (`attempts_remaining`), `409 recipient_otp_locked \| recipient_otp_not_ready`. Replay when already verified |
| POST | `/bookings/{booking}/recipient-otp/resend` | `429 recipient_otp_resend_too_soon`, `422 recipient_otp_resend_limit`, `409 recipient_otp_locked` |
| POST | `/bookings/{booking}/delivery-attempts` | `{outcome: recipient_unavailable\|recipient_refused\|wrong_address\|otp_failed\|restricted_item\|other, reason? (required for other, restricted_item), evidence? (picture)}` -> `201` `{attempt, booking}` in `delivery_attempt_failed`; `409 custody_not_at_dropoff \| delivery_attempt_not_allowed`; repeat = `200` replay |
| POST | `/bookings/{booking}/delivery-attempts/{attempt}/resolve` | `{resolution: retry\|return_to_sender\|operations}`; `422 delivery_attempts_exhausted`, `409 delivery_attempt_already_resolved` |
| POST | `/bookings/{booking}/complete` | now also `422 booking_proof_required` (stage-aware) and `422 booking_recipient_otp_required` |

### Customer (`/api/v1/bookings/{booking}`)

| Method | Path | Notes |
|---|---|---|
| GET | `/proofs` | Own booking only; the pickup PIN proof is never listed |
| GET | `/proofs/{proof}/file` | signed + token, owner only (other customers 404) |
| POST | `/delivery-attempts/{attempt}/resolve` | `{resolution}` as above (retry / return_to_sender / operations); Idempotency-Key |

Push events: `delivery.picked_up | arrived_dropoff | delivered | attempt_failed | attempt_resolved | returned | recipient_otp_locked` (link: booking). Admin: Safety tab (custody, code state, attempts, proofs), Bookings filters `custody_statuses[]`, `parcel_sizes[]`, `is_fragile`, override `POST /admin/bookings/{id}/lift-recipient-otp-lock` (`bookings.pin_override`, reason, audit).

## P4.2 — Furniture moving & freight (ADR-152..157)

### Offers (`POST /provider/bookings/{booking}/offers`, `.../offers/accept-customer-fare`)
Moving / freight categories: a counter-offer needs `scope` (JSON Schema in `config/offer_scope.php`, version 1):
`{vehicle_class (your vehicle's category code), helpers 0-10, packing none|wrap_only|full_pack, stairs {handling not_needed|included|extra_per_floor, included_floors}, assembly none|disassembly|assembly|both, loading_equipment[] (hand_trolley, forklift, crane, tail_lift, straps_blankets, pallet_jack), waiting_allowance_minutes, extra_stops_included, tolls|parking included|at_cost_with_evidence|excluded, exclusions?}`.
Errors: `422 offer_scope_required | offer_scope_invalid (meta.scope_errors path => keyword) | offer_scope_vehicle_mismatch | offer_vehicle_capacity_insufficient (meta.shortfalls payload|volume)`. Accept-customer-fare stores the booking's `requested_scope`. Offer resources (both apps) add `scope_summary` (`[{key, label, value}]`).

### Booking (customer and selected provider)
`load` (`weight_kg`, `volume_m3`, `declared_value_minor`, `pickup_window {start,end}`, `items[]`, `attachments {photos, videos, documents}`), `requested_scope`, `requested_scope_summary`, `agreed_scope`, `agreed_scope_summary`; stops carry `contact {name, phone|null, phone_masked, note}` (provider: selected only). Request cards (pre-selection) add `summary.load`, `summary.pickup_window`, `summary.requested_scope` (no contacts, no addresses). Create / edit accept `stops[].contact {name, phone, note}`; `service_details` per seeded schema v2 (`items[] {item, quantity, note?, weight_kg?, dimensions?}`, `items_notes`, `estimated_weight`, `estimated_volume`, `pickup_window_start/_end` `Y-m-dTH:i`, `pieces`, `photos[]` / `documents[]` = attachment ids). New errors: `volume_exceeded`, `pickup_window_incomplete | _in_past | _invalid | _too_long`, `attachment_invalid`, `attachment_limit_exceeded`.

### Attachments
| Method | Path | Notes |
|---|---|---|
| POST | `/bookings/attachments` | multipart `kind` photo\|video\|document + `file`; Idempotency-Key; `201 {id, kind, mime, size_bytes, attached:false}`; `409 attachment_pending_limit`, `422 document_*`, `503 file_scanner_unavailable` |
| DELETE | `/bookings/attachments/{id}` | unreferenced uploads only (`409 attachment_in_use`) |
| GET | `/bookings/{booking}/attachments`, `/attachments/{id}/file` | owner; signed URL |
| GET | `/provider/bookings/{booking}/attachments`, `/attachments/{id}/file` | selected provider only (404 otherwise); signed URL |

### Adjustments (`POST /bookings/{b}/adjustments`, `/provider/bookings/{b}/adjustments`)
New `type` values `extra_helper` (`details.helpers` 1-5), `stairs` (`details.floors`). Scoped bookings require `details.scope_item` (helpers, stairs, extra_stops, waiting_allowance, tolls, parking, ...; implied when the type has one item). Errors: `adjustment_scope_item_required | _invalid`, `adjustment_within_scope`, `adjustment_floors_required`, `adjustment_helpers_invalid`. Approved adjustments feed `adjustments_total`, the final fare, the price breakdown and the receipt (`adjustments[].scope_item`, `booking.agreed_scope`).


## P4.3 — Flutter flows: contract additions (ADR-158)

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/provider/offer-scope` | Provider token. `data = { version, vehicle_class (code of the vehicle category the provider offers with, null without a current vehicle), schema (the JSON Schema of config/offer_scope.php) }`. The scope builder renders its enums and bounds; the scope sent with a counter-offer must carry `vehicle_class` |

`GET /provider/requests/available` cards: `details` now also carries `scope_required` (bool), `load {weight_kg, volume_m3}`, `pickup_window {start, end}`, `requested_scope` (object, `{}` when none) and `requested_scope_summary [{key, label, value}]`. They are not personal data (the dispatch payload already carried them); no contacts or addresses were added.

Client notes: attachment ids of videos go into the same `photos` field of the form; `restricted_goods_policy_version` is the id from `GET /policies/restricted_goods`; stops send `contact {name, phone, note}`; adjustments of a scoped booking send `details {scope_item, helpers?, floors?}` and `evidence.reference` for `toll_parking`.


## P4.4 — Contract addition (ADR-165)

`delivery.proofs.checklist_items` (list of strings, e.g. `package_sealed`, `package_undamaged`, `label_matches`, `recipient_checked`) is now part of the `delivery` block of a booking in both apps: the codes a checklist proof may carry, read from `config/courier.php`. Apps should render these instead of a hard-coded list; unknown codes keep being refused with the existing proof validation error. No other API change: the admin screens (contacts reveal, proof and attachment streams, attempt resolution) are admin routes, not API.


## P5.1 — Safety (ADR-167..172)

Both apps (`customer` and `provider` tokens), reachable by an account in any standing. Responses use the usual envelope; error texts are localized by the server.

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/safety/incidents` | Body `booking_id` (uuid), `trigger` `sos`\|`report`, `category` (required for a report; an SOS defaults to `emergency`), `narrative` (required for a report), `lat`, `lng`, `accuracy_m`, `captured_at`, `callback_phone`, `notify_trusted_contacts` (default true). **No Idempotency-Key** and exempt from the per-minute limiter for an SOS: a repeated press joins the open case (`joined_existing: true`). A report is limited per hour (`429 safety_report_rate_limited`). `201 data` = the case plus `actions [{code, status, message}]` (what was done: `incident_recorded`, `location_captured`, `safety_team`, `trusted_contacts`, `emergency_services` with status `not_contacted`) and `emergency_numbers [{key, label, number}]`, `emergency_notice`. Errors: `sos_not_available` (no assigned provider or job finished), `report_not_available`. |
| GET | `/safety/incidents`, `/safety/incidents/{id}` | The person's own cases of this app. Detail adds `narrative`, public `timeline`, `trusted_contacts` (name, status), `attachments` (signed URLs), `actions`, `emergency_numbers`. Staff notes, severity and priority are never returned. |
| POST | `/safety/incidents/{id}/updates` | `{body}`; Idempotency-Key. |
| POST | `/safety/incidents/{id}/attachments` | multipart `file` (jpeg, png, webp, pdf, max `safety.attachments.max_kilobytes`); Idempotency-Key; `422 safety_too_many_attachments`, `document_*`, `503 file_scanner_unavailable`. |
| GET | `/safety/incidents/{id}/attachments/{attachment}` | signed; uploader only. |
| GET / POST | `/safety/trusted-contacts` | `{items [{id, name, phone_masked, relationship, notify_on_sos, share_trip, share_location_on_sos, consent_confirmed_at}], max}`. POST body `name`, `phone` (E.164), `relationship?`, `consent_confirmed` (must be true), the three flags; Idempotency-Key. Errors `trusted_contact_consent_required | _phone_invalid | _is_self | _duplicate`, `trusted_contacts_limit_reached`. |
| PATCH / DELETE | `/safety/trusted-contacts/{id}` | A changed phone resets the consent time. |
| POST / DELETE | `/safety/bookings/{booking}/share` | Send the trip link to the contacts with `share_trip`, or revoke it. `201 {url, expires_at, contacts {total, sent, failed, skipped}}`; `422 trusted_contacts_none_for_trip`, `409 share_not_available`, `429 safety_trip_share_rate_limited`. Either side of the booking. |

`GET /config/app` gains `safety {emergency_numbers [{key, number}], sos_hold_seconds, trusted_contacts_max, emergency_dispatch: false}` (numbers by market country from `config/safety.php`; labels are the apps' own). Notifications: `safety.case_update` now has `link_type = safety` (`link_id` = case uuid; older safety-ticket notices still carry a ticket uuid, the apps fall back to the ticket on 404); admins get `safety.incident_raised` (`link_type = incident`) and a realtime `operations.safety_incident` hint on `private-admin.operations`.


## P5.2 — Rating reports and appeals (ADR-173..177)

Both apps (`customer` and `provider` tokens), reachable by an account in any standing. Rating ids are the `id` (uuid) of `RatingResource`. Another person's rating is a `404`.

| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/ratings/{rating}/report` | The rated side reports a rating written about them. Body `reason` (`abusive`, `spam`, `irrelevant`, `false_info`, `personal_info`, `other`), `details` (optional, max 1000). Idempotency-Key, 20/hour. `201`; `409 rating_report_exists`, `422 rating_not_reportable` (already hidden). A report never changes the public average. |
| POST | `/api/v1/ratings/{rating}/appeal` | Body `message` (10-1000). The writer of a HIDDEN review (`grounds hidden_review`) or the person a low rating (<= 2 stars, still counted or under review) is about (`unfair_rating`). Idempotency-Key, 10/hour. `201 {id, rating_id, grounds {code,label}, status {code,label}, created_at, decided_at}`; `409 rating_appeal_exists`, `422 rating_appeal_not_allowed`, `422 rating_appeal_window_closed` (30 days). |
| GET | `/api/v1/ratings/{rating}` | The rating the person WROTE: `RatingResource` (with `moderation_status` `visible`\|`hidden`\|`under_review`) plus `appeal` (null or the appeal object above). Opens the `review.hidden` notification (`link_type inbox`, `link_id` = rating id). |
| GET | `/api/v1/ratings/appeals` | `{items: [appeal ...]}` the person's appeals in this app. The staff reason is internal and never returned. |

`RatingResource` (response of the existing rating endpoints) gains `moderation_status`. Risk signals have no API: they are internal (SRS 15).

## P5.3 — Admin reporting endpoints (ADR-178..181)

No `/api/v1` endpoint changes. Admin (session) endpoints added:

| Method | Path | Notes |
|---|---|---|
| GET | `/admin/dashboard?filters[range][from]=&filters[range][to]=&filters[timezone]=&filters[currency]=&filters[city_ids][]=` | Full page; with `X-Requested-With` returns only the widgets partial; with `Precognition: true` returns 204 or 422 (`filters.range.to`). Needs `dashboard.view`; each KPI / chart needs the permission of its drill-down listing. |
| GET | `/admin/reports` | Hub of the reports the admin may open (`reports.view` + the report's own permission). |
| GET | `/admin/reports/{report}` | Page and, for the table, the Yajra JSON (`data`, `recordsTotal`, `chart`). Reports: booking-funnel, supply-demand, fares, provider-acceptance, completion, cancellation, coupon-roi, provider-earnings, commissions, payout-ageing, payment-reconciliation, support-sla, safety-trends, document-expiry, retention, referral (placeholder). Filters: `range`, `timezone`, `currency`, `city_ids`, `zone_ids`, `category_ids`, `group_by` (date, city, zone, category). |
| POST | `/admin/reports/{report}/export` | Queues the CSV (`reports.export`); audited; `pii` stored only when the admin holds `reports.export_pii`; poll `admin/exports/{uuid}` as for every export. |


## P5.4 — CMS content (ADR-182..187)

Public (no token), `GET`, JSON envelope `{success, message, data}`, `Cache-Control: public, max-age=60` with a strong `ETag` (send `If-None-Match`, get `304`), `Vary: Accept-Language`. The language comes from `Accept-Language` (`ar` default); an item without text in the requested language is served in Arabic only when it is configured to fall back (`fallback_to_ar`, default on), otherwise it is omitted (lists) or `404` (single items). Only PUBLISHED content is ever returned; drafts, in-review, scheduled and archived content never are. All `*_html` fields are already sanitized (allowlist) and safe to inject; every other text field is plain text and must be escaped. The website (P5.5) calls the same `App\Services\Content\ContentReadService` instead of HTTP.

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/content/banners?placement=&audience=` | `data` = list of `{id, kind (banner\|announcement), placement, audience, title, body, image {url, alt, width, height, variants {320,960,1600}} \| null, cta {label, url} \| null, starts_at, ends_at, locale}`. `placement` is one of `app_home`, `app_booking`, `web_home`, `web_top_bar`, `web_services` (empty = all); `audience` `customer`\|`provider` also returns banners for `all`. Only banners inside their display window. Invalid values `422`. |
| GET | `/api/v1/content/faqs?category=&service=` | `data {categories [{slug, name, description}], faqs [{id, question, answer_html, category_slug, service_category_code, locale}]}` ordered by `sort_order`. `category` is a category slug, `service` a service category code (the question tied to that service). |
| GET | `/api/v1/content/pages/{slug}` | `data {id, kind (home\|generic\|service\|city), slug, title, summary, hero_image, service_category {code, name} \| null, city {id, name} \| null, locale, body_html, blocks [{type, title, body_html, items [{title, text}], cta {label, url} \| null, + faqs \| cities \| app_links by type}], seo {title, description, og_title, og_description, og_image, canonical_url, noindex, available_locales}, published_at, updated_at}`. `404` when not published (or no text in the language and no fallback). The homepage is `home`. |
| GET | `/api/v1/content/articles?type=help\|blog&category=&limit=` | Help-center / blog list without body: `{id, type, slug, title, summary, category {slug, name}, cover_image, locale, published_at, updated_at}`. |
| GET | `/api/v1/content/articles/{slug}` | The list item plus `body_html` and `seo`. |
| GET | `/api/v1/content/menus` | `data` keyed by location (`header`, `footer_explore`, `footer_legal`): `{id, location, title, items [{label, url, new_tab}]}`. |
| GET | `/api/v1/content/app-links` | `[{app (customer\|driver), platform (android\|ios\|huawei), url}]` of ACTIVE links. |
| GET | `/api/v1/content/legal/{type}` | The current published `policy_versions` text through the same service: `{type, version, title, content, content_format: "plain_text", published_at}`. `content` is PLAIN TEXT: escape it and keep line breaks. `404` when the type has no published version. The existing `GET /policies/{type}` is unchanged. |

Admin (session) endpoints added under `/admin/content-pages|content-articles|content-faqs|content-banners|content-menus` (CRUD, listings with Yajra + collapsed filters), `/admin/content-categories`, `/admin/content-app-links`, `/admin/content-media` and the shared workflow routes `POST /admin/content/{page|article|faq|banner|menu}/{id}/{submit|reject|publish|schedule|revert|archive|restore}` (reason mandatory except submit; `scheduled_at` for schedule), `GET .../preview?locale=`, `GET .../revisions`, `POST .../revisions/{n}/restore`. Lookups `content-media`, `faq-categories`, `article-categories`.

## P5.5 — Public website routes (ADR-188..192)

Not part of `/api/v1`: HTML routes of the `web` group (CSRF, session cookie). Arabic is unprefixed, English under `/en`; paths come from `lang/{locale}/site.php`. Forms answer JSON when `Accept: application/json` (envelope `{success, message, data: {reference}}`; errors `{errors: {field: [..]}}`), the Precognition header returns 204 / 422 without side effects, and a plain POST redirects back with the reference.

| Method | Path (en) | Notes |
|---|---|---|
| GET | `/`, `/en` | Home |
| GET | `/customer-app`, `/apps`, `/become-a-driver`, `/how-it-works`, `/safety`, `/about`, `/contact`, `/services`, `/cities`, `/faq`, `/blog`, `/legal` | Pages (Arabic equivalents in `site.paths`) |
| GET | `/services/{slug}`, `/cities/{slug}`, `/help/{slug}`, `/blog/{slug}`, `/legal/{type}` | CMS service / city pages, help and blog articles, legal text; unknown or unpublished = 404 |
| POST | `/become-a-driver` | Provider lead. Fields: `name`, `phone`, `email?`, `city_id?` / `city_other`, `service_category_id?`, `vehicle_note?`, `message?`, `consent`, honeypot `website_url` (empty), time trap `_ft`. Throttled `public-forms`; returns `data.reference` (`LD-000123`). No files. |
| POST | `/contact` | Support ticket. Fields: `name`, `email`, `phone?`, `category` (`TicketCategory::forWebsite()`), `subject`, `message`, `consent`, `website_url`, `_ft`. Throttled `public-forms`; returns `data.reference` (`TK-000123`). 3 per email per day. |
| GET | `/sitemap.xml`, `/robots.txt` | Dynamic; sitemap lists each language variant with hreflang alternates |
| GET | `/t/{token}`, `/receipts/{uuid}` | Unchanged (trip share now uses the website layout, minimal and noindex) |

Admin (session): `/admin/leads` (listing + filters `city_ids`, `category_ids`, `statuses`, `created`), `GET /admin/leads/{lead}`, `PUT /admin/leads/{lead}` (notes, owner, `lock_version`), `POST /admin/leads/{lead}/status?status=` (`reason` required for rejected / spam / new).

## 8.30 Growth: referrals, credits, loyalty, provider incentives (P5.7, ADR-197..202)

Every endpoint is behind a server feature flag (`referrals`, `loyalty`, `incentives`, all OFF by default; see 8.4 `features` / `flags`). When the flag is off for the caller the answer is `404` with `errors._code = "feature_disabled"` and the apps show no UI. Money is `{amount_minor, currency, amount}`. A flagged referral or a held quest is always shown as `pending` (fraud review is never revealed).

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/api/v1/me/referral` | `customer` or `provider` | Audience = the token's app. `data {code, link, share_text, campaign {name, referrer_reward, referee_reward, milestone_jobs, credit_valid_days (customers only), ends_at} \| null, can_apply_code, stats {referred, qualified, pending}, referrals [{id, name (first name), status pending\|rewarded\|closed, jobs_completed, created_at}]}`. The code is created on first read. |
| POST | `/api/v1/me/referral/apply` | `customer` or `provider`, `Idempotency-Key` | Body `{code}`. `200 {id, status: "pending"}`. Same code again returns the same referral. Errors: `422 referral_code_invalid`, `referral_self`, `referral_not_new` (a completed job exists), `referral_campaign_unavailable`, `referral_cap_reached`; `409 referral_already_applied`. |
| GET | `/api/v1/me/credits` | `customer` (flag `referrals`) | `data {available (Money \| null), credits [{source referral\|loyalty, amount, remaining, status granted\|expired\|reversed, granted_at, expires_at}], note}`. Credit sits in the ordinary wallet; money leaving the wallet uses promotional credit first, oldest first. |
| GET | `/api/v1/me/loyalty` | `customer` (flag `loyalty`) | `data {completed_jobs, level {id, name, min_completed_jobs} \| null, next_level \| null, jobs_to_next_level, levels [{id, name, min_completed_jobs, reward \| null, reached}]}`. |
| GET | `/api/v1/provider/incentives` | `provider` (flag `incentives`) | `data.campaigns [{id, name, starts_at, ends_at, target_jobs, bonus, max_awards, min_rating, progress {jobs_in_cycle, completed_jobs, awards, status in_progress\|completed\|pending\|closed}}]` for open campaigns in the provider's city. |

Rewards are posted by the server when the completed-booking hook runs (queued, once per booking): referral rewards when the new person reaches the milestone (jobs that satisfy the campaign's minimum order / service / city), loyalty credits when a level is reached, quest bonuses when the target is reached. All of them are balanced ledger entries (`promo_expense` against the wallet), keyed by an idempotency key, inside the campaign budget and caps. Customer credit expires (`growth:expire-credits`, hourly): the unspent part is taken back by a compensating entry.

Admin (session): `/admin/referral-campaigns` (CRUD, `POST .../{id}/status?status=` with `reason` for paused / archived, `POST .../{id}/budget` with mandatory `reason`), `/admin/referral-relationships` (listing + `GET {id}`, `POST {id}/approve`, `POST {id}/reject` with mandatory `reason`), `/admin/referral-rewards`, `/admin/loyalty-levels` (CRUD, `POST {id}/toggle?active=` reason to deactivate), `/admin/incentive-campaigns` (CRUD, status, budget, `GET {id}` with the progress table), `POST /admin/incentive-progress/{id}/release|reject` (reject needs `reason`). Report `/admin/reports/referral` (outcomes per campaign). Lookup `referral-campaigns`. Permissions `referrals.view|manage|review`, `loyalty.view|manage`, `incentives.view|manage|review`.
