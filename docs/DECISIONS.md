# Architecture & Engineering Decisions

> Format: **ID — title** · date · status, then Context / Decision / Consequences.
> ADR-01..ADR-18 come from Plan §3 (`BUILD_PHASES_AND_PROMPTS.md`). ADR-19+ are decisions taken while executing milestones.
> Never edit an accepted decision's meaning silently: supersede it with a new ADR and mark the old one "Superseded by ADR-xx".

---

## ADR-01 — Modular monolith · 2026-10-13 · Accepted

**Context.** The SRS (§2.2) recommends a modular monolith; the repo has one fresh Laravel app. Team size and launch scope do not justify microservices.
**Decision.** One Laravel 13 app serves the REST API (`/api/v1`), the admin panel (`/admin`, configurable via `ADMIN_PATH`) and the public website (`/` with `/{locale}` prefix), each with its own route file, middleware group and layout.
**Consequences.** Shared domain layer and one deploy unit; module boundaries are enforced by namespaces and code review, not by the network. Modules can be extracted later because integrations go through adapters and events.

## ADR-02 — Layer-first structure with module sub-namespaces · 2026-10-13 · Accepted

**Context.** The user mandates Services / Repositories / DTOs / Enums layering; 40+ modules need a predictable layout a generator can scaffold.
**Decision.** Top-level layers (`Enums/`, `DTOs/`, `Repositories/{Contracts,Eloquent}/`, `Services/`, `DataTables/`, `Http/…`) each containing `<Module>/` subfolders (Plan §4.1).
**Consequences.** Layering is visible in paths and lintable; a feature touches several folders, which `make:admin-module` (P0.3) mitigates. Boost's "no new base folders without approval" is satisfied by this ADR (see ADR-19).

## ADR-03 — MySQL 8 + Redis 7 · 2026-10-13 · Accepted

**Context.** Both are installed locally (MySQL 8.0.46, Redis 7.0.15). Row locks, JSON and spatial behaviour must match production; SQLite differs.
**Decision.** MySQL 8.0 InnoDB, `utf8mb4_unicode_ci`, spatial `POINT SRID 4326`. Redis for cache, queues, sessions, locks, rate limits and the GEO index. Tests run on MySQL DB `delivery_platform_testing` and Redis logical DB 15.
**Consequences.** Test suite needs a MySQL server (no in-memory SQLite). ParaTest creates per-process databases `delivery_platform_testing_test_N` (see ADR-21).

## ADR-04 — Money as integer minor units · 2026-10-13 · Accepted

**Context.** Floats corrupt money; multiple currencies may be configured (EGP first).
**Decision.** `bigint *_minor` + `char(3) currency` columns, `App\Support\Money\Money` VO and `MoneyCast`. Currency exponent from a `currencies` table (EGP = 2). Half-up rounding at the final display step only; policy configurable per currency.
**Consequences.** No `float`/`decimal` money anywhere (code-review checklist). Flutter mirrors the VO in `platform_core`.

## ADR-05 — Mobile auth: phone OTP → Sanctum tokens · 2026-10-13 · Accepted

**Context.** Customers and providers authenticate by phone (SRS §6.2, §7.1); one person may be both.
**Decision.** Phone OTP issues Laravel Sanctum personal access tokens with abilities (`customer`, `provider`). One `users` table; customer and provider profiles are separate tables. Device registry for push tokens.
**Consequences.** Abilities are checked per route group; OTP abuse handled by rate limits (P1.3).

## ADR-06 — Admin auth: separate guard + Spatie permissions · 2026-10-13 · Accepted

**Context.** Admin staff are not app users; privileged operations need fine-grained permissions and MFA.
**Decision.** `admins` table + `admin` session guard, TOTP MFA (enforceable per role), login throttling, session list/revoke. spatie/laravel-permission on guard `admin`; permission names come only from the `AdminPermission` enum.
**Consequences.** No magic permission strings; roles are seeded from Appendix B and editable in admin.

## ADR-07 — Realtime via Laravel Reverb · 2026-10-13 · Accepted

**Context.** Offers, trip status and live location need push-style updates to apps and admin.
**Decision.** Laravel Reverb (Pusher protocol), private channels authorized in `routes/channels.php` via policies. Flutter uses `dart_pusher_channels`; admin uses Laravel Echo. Events are persisted (`booking_status_events`, `outbox_events`) and carry a per-booking `sequence` so clients detect gaps.
**Consequences.** Clients must resync from the REST snapshot when a sequence gap is detected. `BROADCAST_CONNECTION=log` until P2.2.

## ADR-08 — Admin UI stack · 2026-10-13 · Accepted

**Context.** Admin is a dense operations console; the repo already ships Tailwind 4 + Vite 8.
**Decision.** Blade components + Tailwind CSS 4 + Alpine.js + axios + Laravel Precognition + Yajra DataTables (DataTables 2.x; jQuery only as its dependency) + Tom Select + flatpickr (Arabic) + Leaflet/leaflet-draw + Chart.js. No SPA framework.
**Consequences.** Server-rendered pages with AJAX forms; one component library enforced by the Admin Module Contract (Plan §5.6).

## ADR-09 — Translatable content as JSON columns · 2026-10-13 · Accepted

**Context.** Arabic-first with English secondary for admin-managed content.
**Decision.** spatie/laravel-translatable JSON columns `{ "ar": "...", "en": "..." }`; Arabic required, English optional with fallback to Arabic.
**Consequences.** Validation requires `*.ar`; API resources return the resolved locale plus raw translations where admin needs them.

## ADR-10 — Typed settings, secrets only in env · 2026-10-13 · Accepted

**Context.** Business policy must be configurable without deploys (SRS §0.5, §32) but secrets must not reach the DB/UI.
**Decision.** `SettingKey` enum (type, default, rules, group, sensitive flag) + `platform_settings` table + cached `SettingsService`. Secrets stay in `.env`.
**Consequences.** Every new setting is an enum case; unknown keys are rejected.

## ADR-11 — Private and public file disks · 2026-10-13 · Accepted

**Context.** Driver documents, proofs and attachments are sensitive (SRS §13).
**Decision.** `private` disk (local in dev, S3-compatible in prod) accessed only through short-lived signed routes after a policy check; each sensitive access is audited. Public disk for CMS images.
**Consequences.** No direct file URLs in API payloads; backups of the private disk must be encrypted.

## ADR-12 — Integrations through contracts · 2026-10-13 · Accepted

**Context.** SMS, maps, payments, push and relay credentials are not available yet (SRS §22).
**Decision.** Contracts in `app/Integrations/<Area>/Contracts` (`SmsGateway`, `MapsProvider`, `PaymentGateway`, `PushNotifier`, `PhoneRelay`, `FileScanner`) with real + `log`/`fake` drivers. Fake drivers are allowed only when `app()->environment(['local','testing'])`, enforced at boot.
**Consequences.** No fake success in staging/production; the health page reports "not configured".

## ADR-13 — Flutter architecture · 2026-10-13 · Accepted

**Context.** Two apps share API client, models, enums and design tokens.
**Decision.** Shared package `packages/platform_core`. Riverpod (generator), go_router, dio, freezed + json_serializable, flutter_map, ARB + `flutter gen-l10n` with `ar` default.
**Consequences.** Apps contain features only; shared code changes are tested in the package.

## ADR-14 — Single response envelope · 2026-10-13 · Accepted

**Context.** Apps and admin AJAX need one predictable shape (SRS §20).
**Decision.** `{ success, message, data, meta, errors }` produced only by `App\Support\Http\ApiResponse`; stable machine code in `errors._code` for domain errors (e.g. `offer_expired`).
**Consequences.** Exception handler maps domain exceptions to 422/409 envelopes; see `docs/API.md`.

## ADR-15 — Static analysis and formatting · 2026-10-13 · Accepted

**Context.** Quality gate needs automated style and type checks.
**Decision.** Larastan starting at level 6 (raise to 8 by P6), Pint with the Laravel preset plus documented strict rules (ADR-22), `flutter analyze` with strict lints (`very_good_analysis` or strict `flutter_lints`, decided in P0.4).
**Consequences.** `composer check` and `scripts/check.sh` must be green before every milestone commit.

## ADR-16 — State machines in enums + one transition service · 2026-10-13 · Accepted

**Context.** Booking status integrity is a P0 invariant.
**Decision.** `BookingStatus::allowedTransitions()` declares the graph; `BookingTransitionService` enforces guards, writes `booking_status_events` and broadcasts. Nothing else may change `bookings.status`.
**Consequences.** Admin overrides also go through the service with a reason and audit.

## ADR-17 — Coupon usage lifecycle · 2026-10-13 · Accepted

**Context.** Coupon caps must hold under concurrency and abandoned searches.
**Decision.** Coupon usage is *reserved* at booking creation (expires with the search window), *released* on cancel/expiry, *committed* on completion/payment.
**Consequences.** Reservation rows with a coupon row lock; counters checked inside the lock (I7).

## ADR-18 — Commission base · 2026-10-13 · Accepted

**Context.** Discounts must not silently cut provider pay.
**Decision.** Commission is calculated on the agreed fare (before customer discount) plus accepted add-ons; tips are commission-free by default (configurable).
**Consequences.** Platform-funded discounts post to promo expense; provider-funded discounts are explicit in the breakdown.

---

## ADR-19 — Plan overrides conflicting Laravel Boost defaults · 2026-10-13 · Accepted

**Context.** `boost:install` generated guidelines (`backend/CLAUDE.md`, `AGENTS.md`) that say: don't create new base folders, don't change dependencies "without approval", and only create documentation when requested. The Plan (binding per Session Preamble) explicitly requires new layer folders, specific packages and a docs spine.
**Decision.** The Plan and its milestone prompts constitute the approval. Boost guidelines are followed for everything else (Pint after edits, `make:` generators, `search-docs`, PHPUnit conventions, curly braces, return types). `AGENTS.md` was made identical to the generated `CLAUDE.md` so other agents stop re-running the bootstrap.
**Consequences.** Package additions are still verified on Packagist and recorded here.

## ADR-20 — Keep existing git history; no re-init · 2026-10-13 · Accepted

**Context.** Plan §1 assumed no git repo, but the root already had `main` with `bf95901 first commit` and a GitHub `origin`. Rule: never rewrite history.
**Decision.** Did not run `git init` or recreate a "chore: baseline skeletons" commit; `bf95901` serves as the baseline. Each milestone runs on `milestone/<id>-<slug>` from `main`, then `main` is fast-forwarded (`git merge --ff-only`). No force-push.
**Consequences.** Root `.gitignore` added in the P0.1 commit, not the baseline; history audit found no secrets in `bf95901`.

## ADR-21 — Dedicated MySQL user and test database grants · 2026-10-13 · Accepted

**Context.** The app must not use MySQL `root`. ParaTest (`php artisan test --parallel`) creates per-process DBs `delivery_platform_testing_test_N`.
**Decision.** User `delivery_platform`@`localhost`/`127.0.0.1` with ALL on `delivery_platform`, `delivery_platform_testing` and the pattern `delivery_platform_testing\_%`. Password only in local `backend/.env` (untracked). SQL in `docs/SETUP.md`.
**Consequences.** Tests read credentials from `.env`; `phpunit.xml` overrides only the database name, Redis DB and drivers.

## ADR-22 — Pint strict rules; no `declare(strict_types=1)` · 2026-10-13 · Accepted

**Context.** ADR-15 asks for documented strict rules on top of the Laravel preset.
**Decision.** `backend/pint.json`: preset `laravel` + `strict_comparison`, `void_return`, `no_unused_imports`, `ordered_imports` (alpha; class, function, const), `fully_qualified_strict_types`, `global_namespace_import` (classes), `phpdoc_align` left. `declare_strict_types` is **not** enabled: Laravel's own stubs, generators and the Plan's skeletons omit it, and enabling it mid-project changes runtime coercion semantics; typed signatures + PHPStan cover the risk.
**Consequences.** Pint reformatted the three default migrations and `routes/console.php` (cosmetic only).

## ADR-23 — Testing environment drivers · 2026-10-13 · Accepted

**Context.** Tests must be fast and isolated but run on production-like data stores (ADR-03).
**Decision.** `phpunit.xml`: `DB_CONNECTION=mysql`, `DB_DATABASE=delivery_platform_testing`, `REDIS_DB=15`, `REDIS_CACHE_DB=15`, `REDIS_PREFIX=delivery-platform-testing-`, `APP_LOCALE=ar`; cache `array`, session `array`, queue `sync`, mail `array`, broadcast `null` by default. Tests that need real Redis behaviour (locks, GEO, rate limits) use the Redis connection on DB 15 explicitly. Added `brianium/paratest` (7.20, dev) because Collision's `--parallel` requires it.
**Consequences.** Never run `FLUSHALL` in tests; only flush DB 15 if needed.

## ADR-24 — Locale and DB defaults also in config files · 2026-10-13 · Accepted

**Context.** `.env.example` switched to MySQL/`ar`, but config fallbacks still said `sqlite`/`en`.
**Decision.** `config/app.php` fallbacks `ar`/`ar`/`ar_EG`; `config/database.php` and `config/queue.php` default connection `mysql`.
**Consequences.** A missing env var no longer silently selects SQLite or English.

---

## ADR-25 — P0.2 package versions · 2026-10-13 · Accepted

**Context.** Plan §2.4/P0.2: verify Laravel 13 / PHP 8.3 support on Packagist before requiring; never `--ignore-platform-reqs`.
**Decision.** All declare `illuminate/* ^13` (or framework-agnostic) and PHP ≤ 8.3: `laravel/sanctum` 4.3.3, `laravel/reverb` 1.12.0,
`spatie/laravel-permission` 8.3.0 (PHP ^8.3, illuminate ^12|^13), `spatie/laravel-translatable` 6.14.1, `yajra/laravel-datatables-oracle`
13.3.1, `yajra/laravel-datatables-html` 13.0.0, `pragmarx/google2fa` 9.1.0. `yajra/laravel-datatables-buttons` not added (CSV export is a
queued job, Plan §5.4). Reverb requires `guzzlehttp/psr7 ^2.6`, so Composer (`-W`) downgraded `guzzlehttp/guzzle` 8.2 → 7.15.5,
`psr7` 3.1 → 2.13.1, `promises` 3.0 → 2.5.3 (framework accepts both majors). Composer audit: no advisories.
**Consequences.** Guzzle stays on 7.x until Reverb allows psr7 3. Published: `config/{sanctum,reverb,permission,broadcasting,cors}.php`,
Sanctum + Spatie permission migrations (permission tables created now, used from P1.1 with guard `admin`).

## ADR-26 — Exception rendering and domain exceptions · 2026-10-13 · Accepted

**Context.** ADR-14 envelope for JSON/AJAX; normal pages for HTML; no leaks (SRS §20.7).
**Decision.** `ApiExceptionRenderer` (invokable, `withExceptions()->render()`) handles requests that are `api/*` or `expectsJson()`;
`HttpResponseException` is left to Laravel. Unexpected exceptions always return a generic `server_error`, even with `APP_DEBUG=true`
(the cause is in the log with the request id). `DomainException` implements `HttpExceptionInterface` so HTML requests render the
`errors/{status}` page, and it is in `dontReport` (expected outcomes, not bugs). Messages come from `api.errors.<code>`.
**Consequences.** New domain codes need `ar` + `en` entries in `lang/*/api.php`; otherwise the generic status message is shown.

## ADR-27 — Request context via Laravel Context · 2026-10-13 · Accepted

**Context.** Audit rows need IP / user agent / request id, but services must not read `request()` (Plan §4.2).
**Decision.** `CorrelationId` (first global middleware) stores `request_id` in `Context` (appears in every log line and queued job)
and `ip`, `user_agent` as hidden context (never logged). `AuditLogger` reads Context.
**Consequences.** Jobs dispatched from a request keep its request id; console-originated audit rows have null IP/request id.

## ADR-28 — Idempotency semantics · 2026-10-13 · Accepted

**Context.** Plan §4.4 / I9 leaves details open.
**Decision.** Key required (UUID) on unsafe methods of `idempotent` routes; scope `(actor morph alias, actor id, route name, key)` with
guests as `guest/0`; canonical request hash (method, path, recursively key-sorted input, uploaded file hashes); only **2xx** responses
are stored (24 h) so validation failures can be retried with the same key; in-flight duplicates get 409 through an atomic lock on the
`redis` cache store (`IDEMPOTENCY_LOCK_STORE`); the middleware runs after `Authenticate` (priority list). Rows pruned hourly.
**Consequences.** Clients generate one key per user intent and reuse it across retries (Plan §6.2).

## ADR-29 — Locale resolution order includes the session · 2026-10-13 · Accepted

**Context.** Plan §4.4: user preference → Accept-Language → `ar`. The admin topbar (P0.3) needs a locale switcher.
**Decision.** Order: user `HasLocalePreference` → session `locale` (only when a session exists, i.e. web/admin) → best `Accept-Language`
primary-language match among `ar`/`en` → `ar`. Also sets Carbon locale and `Content-Language`.
**Consequences.** Symfony test requests send `Accept-Language: en-us,en;q=0.5` by default, so feature tests that assert Arabic must
send `Accept-Language: ar` (or empty).

## ADR-30 — Money implementation details · 2026-10-13 · Accepted

**Context.** ADR-04 needs concrete arithmetic and formatting rules.
**Decision.** `Money` holds `int` minor units + `Currency` (code, exponent 0–4, symbols). Fractional steps (`multiplyBasisPoints`,
`fromDecimal` with extra precision) use bcmath strings and round once with an explicit `RoundingMode` (default `HalfUp` = half away from
zero; `HalfEven`, `Down`, `Up` available). `allocate(ratios)` gives floor shares and hands remaining units one by one to the earliest
non-zero ratios, so Σparts = total. Overflow throws. `MoneyCast` maps `<key>_minor` + `currency` columns (configurable) and resolves the
exponent through `CurrencyRegistry` (DB-backed, memoised). `MoneyFormatter` formats integers only: `ar` → Arabic-Indic digits and
trailing symbol (`١٬٢٣٤٫٥٠ ج.م`), `en` → leading symbol (`EGP 1,234.50`); `latinDigits` option for Arabic.
**Consequences.** Never pass floats to Money; parse user input with `Money::fromDecimal()`.

## ADR-31 — Enforced morph map and strict models · 2026-10-13 · Accepted

**Context.** Polymorphic columns (audit, tokens, permissions) must not store class names; silent attribute bugs should fail fast.
**Decision.** `Relation::enforceMorphMap()` in `AppServiceProvider`; `Model::shouldBeStrict()` outside production (lazy-loading,
missing-attribute and silently-discarded-attribute violations throw).
**Consequences.** Every new model used polymorphically (including future `Admin`) must be added to the morph map; the generator (P0.3)
should do it.

## ADR-32 — Health probes outside repositories · 2026-10-13 · Accepted

**Context.** "No queries outside repositories" vs. infrastructure probes.
**Decision.** `HealthCheckService` runs `select 1`, Redis `PING` and queue `size()` directly: they are connectivity probes, not data
access. Public `/api/v1/health` exposes statuses only; details (latency, heartbeat age, exception class) stay in `HealthReport::$details`.
**Consequences.** Admin system page (P5.6) can show details behind a permission.

## ADR-33 — Common tables shape · 2026-10-13 · Accepted

**Context.** P0.2 step 7.
**Decision.** `currencies(code char(3) unique, exponent, symbol_ar, symbol_en, active)`; `countries(iso2 unique, name json translatable,
phone_code, currency_code → currencies.code FK, timezone, default_locale, active)`. Seeders are idempotent (`updateOrCreate`) and seed
EGP (exponent 2, `ج.م`/`EGP`) and EG (`مصر`/`Egypt`, `+20`, `Africa/Cairo`, `ar`). The default demo-user seeding was removed (users
change in P1.3). No `lock_version` yet; P1.2 adds it when these become admin-editable.
**Consequences.** Country admin CRUD arrives with markets in P1.2.

## ADR-34 — P0.3 front-end packages (pinned) · 2026-10-19 · Accepted

**Context.** Plan P0.3 step 1: pin exact versions; Node on this host is 20.20.
**Decision.** Exact pins: alpinejs 3.17.3 (+ @alpinejs/focus 3.17.3), axios 1.20.0, jquery 3.7.1 (DataTables 2 targets jQuery 3),
datatables.net 2.3.8 + datatables.net-responsive 3.0.8 (latest 2.x/3.x; DataTables 3 exists but the Plan specifies 2.x), tom-select
2.6.2, flatpickr 4.6.13 (+ bundled `ar` locale), leaflet 1.9.4, leaflet-draw 1.0.4, chart.js 4.5.1, @fontsource/ibm-plex-sans-arabic
5.3.0 (SIL OFL 1.1, self-hosted, Arabic + Latin). Dev: vitest 4.1.11 + jsdom 29.1.1 (vitest 5 / jsdom 30 need Node ≥ 22). The
`bunny` CDN font of the skeleton was removed (no third-party font requests). Two Vite entries: `admin` (css/admin.css,
js/admin/app.js) and `website` (css/website.css, js/website/app.js); Tom Select, flatpickr, Leaflet(+draw) and Chart.js are
lazy-loaded chunks. `npm install --save-exact` hit an npm 10 arborist bug for vitest peers; pins were written to package.json and
installed with `npm install`.
**Consequences.** Upgrading to Node 22 later allows vitest 5. No DataTables CSS package: the skin is Tailwind in admin.css.

## ADR-35 — `yajra/laravel-datatables-buttons` for the DataTable service class · 2026-10-19 · Accepted

**Context.** Plan §5.4: `AdminDataTable` extends Yajra `DataTable`; that service class lives in the buttons package (ADR-25 deferred it).
**Decision.** Require `yajra/laravel-datatables-buttons` ^13.2 (v13.2.1; Laravel 13 / PHP 8.3). Its synchronous print/csv/excel/pdf
actions are disabled (`$actions = []`); exports use the queued `AdminExportService`. No maatwebsite/excel.
`config/datatables.php` published: `error => throw` (errors go through the envelope renderer), `max_length => 100`. Yajra's
debug payload (`queries`, `input`) is stripped in `AdminDataTable::ajax()` even with `APP_DEBUG=true`.

## ADR-36 — DataTable options as JSON, not inline scripts; allowlist by rebuilding columns · 2026-10-19 · Accepted

**Context.** Yajra's HTML builder emits inline `<script>`; client-sent `columns[i][orderable/searchable/name]` are trusted by Yajra.
**Decision.** `<x-admin.datatable>` renders `<table data-admin-table data-options data-language>`; `core/datatables.js` initialises
it (CSP-friendly, works in AJAX-loaded modals/tabs). `AdminDataTable` rebuilds the request's `columns`/`order`/`search` from the
server column definitions (ordering/searching default **off** in `column()`), so forged column names or orderable flags never reach
the query. Filters use `filters[...]` from the filters card, not per-column search. `FilterData` contract (`fromRequest`,
`activeCount`, `toArray`) + `ParsesFilterInput` (tolerant parsers: invalid filter values are ignored, never 500/422).
**Consequences.** Every listing declares orderable columns explicitly.

## ADR-37 — Queued exports with polling (no notifications table yet) · 2026-10-19 · Accepted

**Context.** Plan §5.4: CSV export is queued, permissioned, audited, delivered via a signed link "in the admin notifications" — but
there is no Admin notifiable until P1.1 and the notifications platform is P3.6.
**Decision.** `admin_exports` table + `AdminExportService` (queue → audit `exported` → `GenerateAdminExport` on queue `low` → CSV
with BOM on the private `ADMIN_EXPORTS_DISK`, formula cells prefixed with `'`). The listing polls `GET /admin/exports/{uuid}`
and shows a toast with the signed (24 h) owner-only download link. Rows/files are pruned after 7 days (`model:prune`, hourly).
**Consequences.** P3.6 additionally posts the signed link to the admin notification inbox; the polling flow stays.

## ADR-38 — DataTables protocol responses are the only admin JSON outside ApiResponse · 2026-10-19 · Accepted

**Context.** ADR-14 says all AJAX JSON goes through `ApiResponse`; DataTables requires its own shape (`draw`, `recordsTotal`, …).
**Decision.** Listing data responses use the Yajra shape; every error (403/419/500 …) on those endpoints still uses the envelope
(exception renderer). Everything else (forms, confirm actions, lookups, exports) uses `ApiResponse` / `AdminFormResponse`.

## ADR-39 — Admin authorization before P1.1 · 2026-10-19 · Superseded in P1.1 (see ADR-46, ADR-52)

**Context.** P0.3 needs policies, `can:` routes and contract tests, but the Admin model/guard is P1.1.
**Decision.** Policies type-hint `Illuminate\Contracts\Auth\Access\Authorizable` and call `$admin->can(<AdminPermission value>)`
(Spatie answers from P1.1). The full Appendix A catalog is the `AdminPermission` enum (labels composed from group + action
translations). Raw permission checks outside policies use `AdminAccess::allows()`. Tests use `Tests\Support\TestAdmin` through
`Tests\Concerns\InteractsWithAdmin` (`actingAsAdmin`, `assertGuestBlocked` = 403 for now, `datatable()`, `precognitive()`); P1.1
changes only that trait. Temporary `AllowLocalGuestAccess` middleware (admin group, before `Authorize` in the priority list) grants
every ability to guests **only** when `APP_ENV=local` and `ADMIN_LOCAL_GUEST_ACCESS=true`; it is inert in testing/production and is
deleted in P1.1. The locale switcher stores the session key; persisting it on the admin is P1.1.

## ADR-40 — Admin form protocol details · 2026-10-19 · Accepted

**Context.** Plan §5.3 leaves transport details open.
**Decision.** Transport is always `POST` + `FormData` (`_method` spoofing) for both the Precognition call and the real submit, so
files are validated before submit. A Precognition response that is not `204`/`Precognition-Success` is treated as a failure (a
missing middleware must never execute a write during "validation"). Client checks use constraint validation with messages from
`admin.js.validation.*` (panel language, not browser language). `ajax-form:validated` exposes `detail.proceed()/cancel()` for async
hooks (built-in `confirm="…"`); `ajax-form:submit` is cancelable. Live validation is debounced 400 ms, only for touched fields,
with stale responses discarded. Success instructions come from `AdminFormResponse` (`redirect`, `reload_table[]`, `close_modal`,
`reset`, `reload`, `replace_html`); toasts survive redirects via sessionStorage. Confirm actions reuse the same protocol: the global
confirm dialog is itself an `<x-admin.form>` with a mandatory-reason textarea.

## ADR-41 — `make:admin-module` conventions · 2026-10-19 · Accepted

**Context.** Plan §5.5.
**Decision.** Stubs in `backend/stubs/admin-module/*.stub` with `{{ key }}` placeholders and `{{#flag}}…{{/flag}}` sections
(`status`, `translatable`). Module routes go to `routes/admin/<kebab-plural>.php` (auto-loaded by `routes/admin.php`); shared files are
edited at `// @admin-module:<marker>` comments (bindings, morph-map, permissions, menu, lang, permission-groups, enums, lookups) with
idempotent de-duplication and Pint-compatible import sorting. Every module gets `lock_version` optimistic locking (409
`stale_version`), audit on create/update/delete, `Destroy<Model>Request` with optional reason, an Activity tab (audit timeline via
`AuditTrailService`) and a Feature test implementing the Admin Module Contract. Verified: two generated modules (translatable +
status, and plain) pass Pint, PHPStan and their generated tests unmodified (18 tests) on a full copy of the app.
**Consequences.** Arabic strings default to the English ones unless `--title-ar/--singular-ar` are given (the command warns).

## ADR-42 — Demo Item proof module is env-gated · 2026-10-19 · Accepted

**Context.** P0.3 step 8.
**Decision.** `DemoItem` (generated, then extended) is registered only when `ADMIN_DEMO_ENABLED=true` and `APP_ENV` is local/testing
(route file returns early, `EnsureDemoModuleEnabled` 404s at runtime, menu entry has `environments`). It uses every component:
7 filter types, all form fields (money, translatable, remote select, file, map point, polygon, key-value, repeater …), quick-create
modal, drawer quick view, archive with mandatory reason, delete confirm, edit confirm, export, stat tiles, tabs with a lazy activity tab.
**Consequences.** Remove or keep disabled before production; it carries a `demo_items` table migration (harmless when unused).

## ADR-43 — Flutter packages (pinned by `pubspec.lock`) and deviations · 2026-10-19 · Accepted

**Context.** P0.4 step 1; Flutter 3.47.1 / Dart 3.13.1. Versions are what `flutter pub add` resolved (caret constraints in `packages/platform_core/pubspec.yaml`).
**Decision.** platform_core: flutter_riverpod ^3.4.3, riverpod_annotation ^4.0.7, freezed_annotation ^3.1.0, json_annotation ^4.12.0, go_router ^18.1.0, dio ^5.11.1, flutter_secure_storage ^11.2.0, shared_preferences ^2.5.6, intl ^0.20.3, flutter_map ^8.3.2, latlong2 ^0.10.1, geolocator ^14.0.1, permission_handler ^12.0.1 (13.x needs compileSdk 37 while AGP 9.1 supports max 36), dart_pusher_channels ^1.3.1, connectivity_plus ^7.3.2, package_info_plus ^10.2.2, url_launcher ^6.3.3, share_plus ^13.3.1, image_picker ^1.2.4, cached_network_image ^4.0.4, intl_phone_field ^3.2.0 (country picker, `EG` preselected), uuid ^4.6.0; dev: build_runner ^2.16.2, freezed ^4.0.3, json_serializable ^6.14.1, riverpod_generator ^4.0.9, mocktail ^1.0.5. The apps depend on platform_core (path) plus riverpod, riverpod_annotation, go_router, intl, freezed/json annotations, shared_preferences, flutter_localizations.
**Deviation: `pinput` dropped.** pinput 7.0 depends on the `material_ui` package whose `Material` is a different class from the SDK's `package:flutter/material.dart` one used by `MaterialApp`, so it throws "No Material widget found" inside a normal app. `OtpField` is implemented in platform_core (boxes over one hidden numeric `TextField`, `oneTimeCode` autofill, paste, always LTR). go_router and cached_network_image also depend on `material_ui` but only use non-Material widgets; re-check on Flutter upgrades.
**Consequences.** Generated code (`*.g.dart`, `*.freezed.dart`, l10n under `lib/app/l10n/generated/`) is committed so `flutter analyze`/`test` work without a build step; regenerate with `dart run build_runner build --delete-conflicting-outputs` and `flutter gen-l10n`.

## ADR-44 — platform_core API/runtime design · 2026-10-19 · Accepted

**Context.** Plan §6.1/§6.2, docs/API.md §2-3.
**Decision.** `ApiClient` (dio) unwraps the envelope into `ApiResult<Object?>` (`data`, `message`, `meta`) and throws `ApiException(kind, code, message, statusCode, fieldErrors, requestId, retryAfterSeconds)`; `code` is `errors._code` (fallback `http_<status>`), `fieldErrors` excludes `_code`, `requestId` from `meta.request_id` (or the `X-Request-Id` response header). `kind` classes: network, timeout, unauthenticated (401/419), forbidden, notFound, conflict, validation, rateLimited, server, cancelled. Interceptors, in order: X-Request-Id (UUID), Accept-Language (`ar`/`en` from the locale provider), Bearer auth (401 on a request that carried a token emits `sessionExpired`; failed logins do not), GET-only retry (2 retries, exponential backoff + jitter, on transport errors and 502/503/504). `Idempotency-Key` is passed per call via `idempotencyKey:`; `newIdempotencyKey()` makes a UUID v4 that the caller keeps for the whole user intent. `validateStatus` < 400, so every 4xx/5xx reaches the envelope parser.
Enums mirror Plan §7.1 with a wire `value` and `unknown` fallback (`parseApiEnum`); catalog enums whose values the plan does not list (ticket category, rating tag, ...) are added by the milestone that needs them. `Money` = minor units + currency + exponent (ADR-04/30); `MoneyFormatter`: Arabic -> Arabic-Indic digits, `٬ ٫` separators and trailing symbol, English -> leading symbol; `digits:` overrides. Realtime: `RealtimeClient` over dart_pusher_channels, private channels authorized by `POST {origin}/broadcasting/auth` with the bearer token; `SequenceTracker.needsRefetch(key, seq)` flags gaps (events are hints). Not yet exercised against a live Reverb server (needs P2.x backend). `DynamicFormRenderer` renders text/textarea/integer/decimal/boolean/select/multiselect; other `FormFieldType`s are skipped (never dead controls) until their milestone.
**Consequences.** Shared widgets take every label as a parameter (no literals). Driver app uses `UiDensity.driver` (64 dp targets, 1.15x type).

## ADR-45 — App shell conventions · 2026-10-19 · Accepted

**Context.** Plan §6.1/§6.2, P0.4 step 3.
**Decision.** `bootstrap()` loads SharedPreferences and restores the session before `runApp` (locale is correct on the first frame), installs `FlutterError`/`PlatformDispatcher`/zone handlers, and runs under `ProviderScope`. Routes: `/splash` -> `/language` -> `/onboarding` -> `/auth`; `/home` is the only guarded route (`computeRedirect`, unit tested). `/auth` honestly says sign-in is unavailable until P1.3 (no fake form); `/home` only hosts the language switch until P2.6/P2.7. Language: Arabic preselected, a tap applies and persists immediately (UI flips RTL/LTR), "Continue" marks the choice as made. Android app ids default to `com.deliveryplatform.customer|driver` and are overridable with `-PappId=...`; iOS bundle ids come from `ios/Flutter/AppConfig.xcconfig` (`APP_BUNDLE_ID`). Min SDKs: Android 24 (7.0), iOS 15.0. Arabic names are the default Android `values/strings.xml` and `ar.lproj/InfoPlist.strings`; English alongside. No signing material committed.
**Consequences.** Shared strings (error/empty/offline) are duplicated in each app's ARB files; a shared-ARB mechanism is deferred until duplication hurts.

## ADR-46 — Admin identity model · 2026-10-19 · Accepted

**Context.** P1.1 step 1-2, ADR-06.
**Decision.** `admins` (bigint `id` + `uuid`, soft deletes, `status` AdminStatus, `lock_version`, encrypted `mfa_secret`/`mfa_recovery_codes`, `mfa_last_used_step`, `suspended_at`, `last_login_at`) is a separate Authenticatable on the `admin` session guard and the `admins` password broker (table `admin_password_reset_tokens`). `locale` is **nullable**: null means "no stored preference", so `SetLocale` falls through to session, then `Accept-Language`, then Arabic; choosing a language in the topbar (or the profile) persists it on the admin and from then on wins (user preference is first in ADR-29). Spatie uses guard `admin`; roles are an `App\Models\Role` subclass (`is_system`, translatable `label`, `lock_version`; `name` is the stable slug). Morph aliases `admin` and `role` (audit `actor_type` is `admin`). `UseAdminGuard` (first in the `web` group) makes `admin` the default guard for the whole `ADMIN_PATH` prefix, so `auth()`, Gate, `$request->user()` and `SetLocale` all see the admin. Super Admin passes every check through `Gate::before` and has **no stored permissions**; the role editor refuses to edit it.
**Consequences.** `Admin` uses `RoutesNotifications`, not `Notifiable`: the database notification inbox (and its table) arrive with P3.6, until then the topbar bell is hidden. Default attributes are declared on the model so strict mode never trips on a freshly created, unrefreshed instance. Audit rows reference the actor through `actor_type`+`actor_id`; `actorAdmin()` is only meaningful when `actor_type = admin`.

## ADR-47 — Admin sign-in, throttling and MFA · 2026-10-19 · Accepted

**Context.** P1.1 step 3; SRS §16.16, §20.
**Decision.** One generic message ("These credentials do not match our records") for unknown email, wrong password and suspended account; a dummy hash check keeps timing similar. Failed credential checks are throttled **per email+IP** in `AdminAuthService` (`RATE_LIMIT_ADMIN_LOGIN_PER_MINUTE` failures, then locked for `ADMIN_LOGIN_LOCKOUT_SECONDS`, default 900; the 429 carries `Retry-After`); the attempt is recorded as `throttled` in `admin_login_events`. Precognition (validation-only) calls are never counted: the named `admin-login` limiter returns `Limit::none()` for them, otherwise live field validation would lock users out. Every login step is recorded in `admin_login_events` (append-only) and failures also in the audit log.
MFA is TOTP (pragmarx/google2fa, window 1, 30 s): the password step stores a 5-minute pending record in the session and the admin is **not signed in until the code is accepted** (5 wrong codes end the pending login and count as a failed login). A TOTP time step is accepted once (`mfa_last_used_step`, replay protection). Eight one-time recovery codes (`xxxxx-xxxxx`) are stored only as HMAC-SHA256 hashes (keyed with `APP_KEY`) inside an encrypted column; they are shown once (flashed to the recovery page). Enrolment shows an SVG QR (new dependency `bacon/bacon-qr-code` 3.1, pure PHP, SVG back end; required by the milestone, no other package added) plus the manual key; the secret stays in the session until the first valid code. Disabling MFA and regenerating codes need the current password; MFA cannot be turned off while a role requires it.
MFA enforcement: `config admin.auth.mfa.required_roles` (env `ADMIN_MFA_REQUIRED_ROLES`, default super_admin, finance_manager, finance_payout_officer, system_integration_admin; the phpunit environment empties it) — admins holding such a role and not yet enrolled can only reach `admin.security.*`, logout and the locale switch (`EnforceAdminMfa`; JSON gets 403 `mfa_enrolment_required`). The setting moves to `admin_mfa_required_roles` in P1.2.
**Consequences.** Another admin resets lost MFA with a mandatory reason (audited `mfa_reset`, target sessions ended).

## ADR-48 — Tracked admin sessions and middleware priority · 2026-10-19 · Accepted

**Context.** "Active sessions list with revoke" must work with any session driver (production uses Redis, where sessions cannot be listed per user).
**Decision.** `admin_sessions` (admin, session id, IP, user agent, last activity) is written at login. `EnsureAdminSession` (after `auth:admin`) ends the session when the admin is no longer active, or when the session carries the "started by a real login" marker but its row is gone (revoked); a session **without** the marker (created outside the login flow, e.g. `actingAs` in tests) gets its row lazily. Revoking deletes the row and the stored session payload (`SessionManager->driver()->getHandler()->destroy()`); suspension, deletion, MFA reset, password reset and password change revoke sessions (password change keeps the current one). Last-activity writes are throttled to once a minute.
Discovered while wiring this: the framework's default middleware priority list names the `AuthenticatesRequests` contract, so `appendToPriorityList(Authenticate::class, …)` silently did nothing and `SetLocale`/`Idempotency` ran **after** route `can:` checks. `bootstrap/app.php` now appends after `AuthenticatesRequests`, giving the order authenticate → session → MFA gate → locale → idempotency → bindings → `can:`.

## ADR-49 — Privilege-escalation and lock-out rules · 2026-10-19 · Accepted

**Context.** P1.1 step 4 ("cannot remove own super-admin role") and SRS §4.2 least privilege.
**Decision.** Enforced in the services under row locks (policies alone are bypassed by `Gate::before` for Super Admin): only a Super Admin may grant or remove the Super Admin role (`super_admin_only`); any other admin may only add roles/permissions they hold themselves (`role_exceeds_own_permissions`, comparing the *added* set so existing grants an editor lacks are preserved); nobody suspends, deletes or MFA-resets themselves via the admin screens (`cannot_modify_self`); a Super Admin cannot remove their own Super Admin role (`cannot_remove_own_super_admin`); the last active Super Admin can never be suspended, deleted or demoted (`last_super_admin`, counted with `lockForUpdate`); the Super Admin role cannot be edited and system roles cannot be deleted or renamed; a role in use cannot be deleted. Deleting an admin is a soft delete and the email stays reserved.

## ADR-50 — Appendix B interpretations and seeding · 2026-10-19 · Accepted

**Context.** Appendix B uses shorthand ("customers.* (no pii export)", "*.view (no pii)").
**Decision.** `AdminRole::defaultPermissions()` is the single definition. Operations Admin: every `customers.*` except `customers.export`; every `bookings.*` and `offers.*`; "services/areas/policies manage" = `services`, `vehicle_categories`, `service_areas`, `fare_policies`, `cancellation_policies`, `policies.manage` (not `policies.publish`). Safety officer: all `incidents.*` plus the three `*.view_pii` and `bookings.view`. Finance Manager: `finance.*`, `payouts.*`, `wallets.*` (including both halves of the dual-control pair; the rule needs a *second* admin at run time). Analyst/Auditor: `dashboard.view` plus every permission whose action is `view` (never `view_pii`, never the demo module). `PermissionSeeder` creates every enum case on guard `admin` and deletes stale ones; `RoleSeeder` creates the 12 system roles with labels (ar/en) and default permissions **only when the role is new**, so re-seeding never overwrites what an admin edited. Both run from `DatabaseSeeder` and are idempotent.

## ADR-51 — Admin creation, invitation and `admin:create` · 2026-10-19 · Accepted

**Context.** "A local-only command `admin:create` creates the first admin interactively (no default credentials)".
**Decision.** Reading "local" as "from the server shell, never from the web", `admin:create` works in every environment but only interactively (the password is prompted, never an argument, so it stays out of shell history; non-interactive use is refused), applies the admin password policy (12+ chars, mixed case, digits, symbols; uncompromised-password check in production) and writes an audit row with `actor_type = system`. Admins created in the panel get a random unusable password and an emailed invitation (reset-password link, 60 minutes) so no one else ever knows their password; with `MAIL_MAILER=log` the link is in `storage/logs`.

## ADR-52 — Authorization guard test and access markers · 2026-10-19 · Accepted

**Context.** "A guard test that fails when a new admin route lacks authorization."
**Decision.** `routes/admin.php` wraps everything but `routes/admin/auth.php` in `auth:admin` + `admin.session` + `admin.mfa`. Every route named `admin.*` must carry `can:` (policy/permission) **or** an explicit marker: `admin.access:self` (any signed-in admin acting on their own account, plus lookups/exports that authorize inside) or `admin.access:public` (login, MFA challenge, password reset, logout, locale switch; must not be behind `auth:admin`). `AdminRouteAuthorizationGuardTest` walks the whole route table and also proves it detects an unprotected probe route. The module route files (`admins`, `roles`, `audit-logs`, `security`) sit in `routes/admin/` as the generator convention requires.

## ADR-53 — Identity modules are hand-written to the generator's shape · 2026-10-19 · Accepted

**Context.** "Admin modules via the generator + Admin Module Contract".
**Decision.** `make:admin-module` derives the model, table, permission group and routes from one name, which does not fit here: the model is the auth model `Admin` (the generated `AdminController` would clash with the base controller), roles are a Spatie model, and audit logs/login activity are read-only over existing append-only tables. The four modules (Admin users, Roles, Audit logs, Login activity) were therefore written file-for-file in the generated layout (DTO input + filter, repository interface + Eloquent, service with transactions/audit/optimistic lock, policy, FormRequests, controller, `AdminDataTable`, views, routes file, menu entry, ar/en keys, Feature tests covering the Plan §5.6 contract). The generator and its stubs are unchanged; the demo module still passes the same contract tests through the new real-admin test helper.

## ADR-54 — Real-browser smoke check without new dependencies · 2026-10-19 · Accepted

**Context.** The admin JS had only been unit-tested.
**Decision.** `scripts/browser-smoke.sh` (`make browser-smoke`) starts PHP's built-in server against the *testing* database (environment variables override `.env`: file sessions/cache, no Redis state), creates a throwaway admin and runs `backend/tests/Browser/admin-login-smoke.mjs`, which drives the installed Chrome over the DevTools protocol (Node >= 20.10 with `--experimental-websocket`; no Playwright/Puppeteer dependency). It asserts: empty submit sends no request at all; a weak password on the reset form gets an inline Precognition 422 and no real submit; a wrong password sends exactly validation (204) then submit (422) with one generic error; correct credentials validate, submit and land on the dashboard; logout. It is not part of `scripts/check.sh` because it needs Chrome and a local MySQL; run it when touching `resources/js/admin`.

## ADR-55 — Settings storage, cache and config-backed defaults · 2026-10-19 · Accepted

**Context.** P1.2 step 1-3 and the P1.1 carry-over (MFA roles, lock-out, rate limits).
**Decision.** `SettingKey` (42 cases) holds type, default, rules, group, admin input, bounds and unit; `platform_settings(key unique, value json, updated_by_admin_id)` stores only overrides, so an unset key always reads its enum default. All values are validated typed (`fromInput` normalizes form strings: "2, 4 ,7" -> `[2.0, 4.0, 7.0]`, empty optional text -> null) and every read re-casts (`fromStored`), falling back to the default on corrupt data. The whole override map is one cache entry (`platform_settings.v1`, forever); every write forgets it inside the transaction and again after commit (so a concurrent reader cannot re-cache pre-commit data) and also forgets the public app-config payload. Settings that existed in config before P1.2 keep reading it as their default (`admin.auth.mfa.required_roles`, `admin.auth.login.lockout_seconds`, `rate_limits.*`), so env-driven deployments behave identically until an admin saves the tab. A tab save audits one row per changed key (`platform_setting` target, `{key, value}` before/after) and a form fingerprint (hash of the tab's effective values) turns a concurrent edit into `409 stale_version` instead of per-row `lock_version`. Cross-field rules live in the service (default locale is supported, OTP cooldown < validity, offer TTL <= search TTL, ascending radius steps), the per-field rules in the enum. The named rate limiters read their limits from settings on each request (cached), which is why API tests need the database.
**Consequences.** New setting = one enum case + two lang keys (a test fails if a label or description is missing); the secret-name guard test keeps secrets out of settings.

## ADR-56 — Markets modules are hand-written to the generator shape · 2026-10-19 · Accepted

**Context.** `Country` and `Currency` already exist (ADR-33) with shared repositories under `Common`; `make:admin-module` only creates new models.
**Decision.** Same layout as generated modules (DTO input + filter, repository `datatableQuery`, service with transaction/audit/`lock_version`, policy, Store/Update/Destroy requests, DataTable with collapsed filters card, views, routes file, tests) in `Markets` sub-namespaces, extending the existing repository interfaces. Integrity rules: ISO code, currency code and exponent are immutable after creation (stored minor-unit amounts depend on the exponent), the update forms do not submit them and the service rejects a change; the default country/currency (setting) and currencies used by a country cannot be deactivated or deleted; countries need an active currency. Appendix A defines only `markets.manage`, so it gates viewing and editing.
**Consequences.** Cities (P1.5) will add their own module; `CurrencyRegistry` gained `flush()` so edits are visible in the same process.

## ADR-57 — Policy versions: immutable once published · 2026-10-19 · Accepted

**Context.** SRS §13.3 (consents must reference the exact accepted text) and P1.2 step 5.
**Decision.** `policy_versions` rows are drafts (`version` null, editable, deletable) until published; publishing, under a lock on all rows of the type, assigns `max(version)+1`, archives the previous published row (audit `status_changed` with the superseding version) and freezes the new one (`published_at`, `published_by_admin_id`); policies deny edit/delete/publish for non-drafts. `version` is unique per type; numbering is per type and gap-free. `policies.publish` (Appendix A) is separate from `policies.manage`; publishing needs a reason. The current version per type (highest published) is what `config/app` exposes and what P1.3 consents will reference by `policy_versions.id`. No default texts are seeded (legal text is the business's to supply).
**Consequences.** Re-publishing the same text means creating a new draft; there is no unpublish.

## ADR-58 — Public config API shape and caching · 2026-10-19 · Accepted

**Context.** P1.2 step 6; Flutter `appConfigProvider` existed with a guessed DTO.
**Decision.** `GET /api/v1/config/app` returns one language-independent payload (the envelope `message` is localized, so `Vary: Accept-Language`): `default_locale`, `locales`, `country{iso2,phone_code,timezone}`, `currency{code,exponent,symbol{ar,en}}`, `digits_preference` (`auto|western|arabic_indic`), `support{phone,email,whatsapp}`, `maintenance`, `apps{customer,provider}{min_version,force_update}`, `policies{type:{id,version,published_at}}`, `features{}` (placeholder; empty maps are JSON objects). Both apps' rules are always returned and each client reads its own key (the driver app is `provider`), so no `X-App` header is needed before P1.3. A force-update only applies when `force_update` is true and the installed version is below `min_version`; maintenance wins. Responses carry `Cache-Control: public, max-age=30` and an ETag (`cache.headers` middleware, `304` on match); the assembled payload is cached for 10 min and forgotten by every writer (settings, countries, currencies, policy versions). `GET /api/v1/locales` lists supported locales with native names, direction and default flag.
**Consequences.** A maintenance toggle reaches clients within about 30 s (HTTP cache) and on the next app resume.

## ADR-59 — Flutter app config wiring · 2026-10-19 · Accepted

**Context.** P1.2 step 7 and the P0.4 notes (generated code is committed).
**Decision.** Per app: remote data source returns the raw `data` map; the repository parses the DTO (a malformed body is `invalid_response`), stores the raw map in `SharedPreferences` and, on any failure except cancellation, returns the cached copy flagged `fromCache` (otherwise rethrows). `appConfigProvider` disables Riverpod's automatic retry (the splash would otherwise wait on it); it is re-read on app resume and by "Check again". `appGateProvider` derives `open|maintenance|updateRequired` from the config and the installed version (`installedAppVersion()` in platform_core) and fails open on loading, errors or unparsable versions; `AppGate` replaces the whole app in the `MaterialApp.router` builder, so it covers every route. Shared pure logic and the `BlockingNotice` widget live in platform_core; strings stay in each app's ARB. The server's default language applies until the user picks one, and the language picker offers only the supported locales. Version rules are looked up by app key (`customer` / `provider`).
**Consequences.** Forced-update has no store link until a setting for it exists; the shared `ApiClient` still treats 304 as an error, so the client uses the cache instead of conditional requests.

## ADR-60 — `users` becomes the phone-identity table · 2026-10-20 · Accepted

**Context.** The skeleton `users` table (email, password) was unused. SRS §6.2: phone OTP is the primary login.
**Decision.** A migration drops password/remember_token/email_verified_at and the email unique index and adds `uuid`, unique `phone_e164`, `phone_verified_at`, `photo_path`, `preferred_locale`, `status` (`UserStatus`), `last_seen_at`, soft deletes; `name` and `email` are nullable (the name is collected by profile completion). `User` has a narrow `$fillable` (name, email, preferred_locale, photo_path): status and phone are only set by services. One user can use both apps (customer and provider tokens); `customer_profiles` is created at the first customer sign-in, `provider_profiles` is a stub until P2.1. Email is not unique or verified (it is contact data, not an identity).
**Consequences.** `UserFactory` creates verified users; account deletion processing (anonymising the phone) is P1.6.

## ADR-61 — OTP design · 2026-10-20 · Accepted

**Context.** SRS §6.2, §20.7, §28; P1.2 settings.
**Decision.** `OtpService`: crypto-random digits (`otp_length`), stored only as HMAC(app key, phone hash | purpose | code); phone matched by keyed hash, masked phone kept for the admin log. Cooldown (`otp_resend_cooldown_seconds`) is answered with `429 otp_resend_too_soon` (reveals nothing about accounts); a request supersedes older open challenges; requests of one phone are serialised with a cache lock. Verification runs under a row lock, counts attempts (committed before the error is thrown) up to `otp_max_attempts`, then locks the challenge. Request answers are identical for known and unknown numbers; an existing-but-suspended account is only revealed after a valid code. Rate limits: the `otp-request` limiter now also keys on `device_id` and on the normalised phone (formatting tricks share a bucket). Delivery failure (or no SMS driver) is `503 otp_delivery_failed`, never a faked success. The `log` SMS driver writes only to log channel `otp_dev`. **Test OTP:** `identity.test_otp` (enabled flag, fixed code, phone list); `TestOtpGuard` aborts boot outside local/testing if any of it is set, `OtpService::testCodeFor()` re-checks the environment on every use, and no request parameter or header can activate it (tested). Challenges are pruned after 30 days.
**Consequences.** SMS Misr is the real driver (untested without credentials).

## ADR-62 — Tokens, abilities and account gates · 2026-10-20 · Accepted

**Decision.** Sanctum token name `<app>:<device_id>` with the single ability `customer` or `provider` (from `X-App` at verification); signing in again on a device replaces that device's token; lifetime `AUTH_TOKEN_TTL_DAYS` (90). `POST /auth/refresh` re-issues, logout revokes the presented token and clears the device push token. Routes use `ability:customer,provider` (aliases `ability`/`abilities` registered). `account.active[:strict]` maps `UserStatus` to typed 403 codes; status-safe routes omit it. `user.seen` refreshes `last_seen_at` at most every 5 minutes. Deletion requests set `deletion_pending` and remember the previous status for cancellation; admin processing comes in P1.6.

## ADR-63 — Consents and policy texts · 2026-10-20 · Accepted

**Decision.** `user_consents` references `policy_versions.id` (unique per user and version, append-only model); the API exposes policy ids as uuids. Required types per app: `ClientApp::requiredPolicyTypes()` (terms + privacy; driver terms belong to P2.1). A type with no published version is not required, so an unconfigured platform does not block sign-in (an admin must publish terms and privacy before launch). Only the current published version can be accepted; publishing a new version makes the consent pending again (the apps re-ask on the next `/me`). Marketing opt-in is stored apart on `customer_profiles`. `GET /policies/{type}` serves the current text for the consent screen.

## ADR-64 — Flutter identity flow · 2026-10-20 · Accepted

**Decision.** Shared in platform_core: identity models, `IdentityRepository` (only code that knows the endpoints), `DeviceIdStore` (random per-install uuid), `AuthStage` + `computeAuthStage`. Each app has Riverpod providers (`meProvider`, `authStageProvider`, `AuthActions`) and screens; the router redirect is a pure function of the stage. Customer: phone -> OTP -> profile -> consent -> home; driver: phone -> OTP -> `/provider-onboarding`, a labelled "coming soon" entry point (P2.1 replaces it), no profile/consent steps. Blocking statuses (suspended, deleted, deletion pending, verification required, restricted until acknowledged) and a failed `/me` all land on one account-status screen. Error text comes from ARB by stable error code, never from server strings. The token stays in `flutter_secure_storage` through `SessionController`. Profile photo upload is not in the apps yet (the endpoint exists); push token registration waits for the push milestone.

## ADR-65 — Catalog drafts and immutable published versions · 2026-10-20 · Accepted

**Context.** Plan P1.4 asks for editable drafts, a Publish action that snapshots category + schema + policies, and bookings that keep the configuration they were created with.
**Decision.** The `service_categories` row (and its vehicle links and the draft `service_form_schemas` row, `status = draft`, `version = null`) is the **draft**. Publishing (permission `services.manage`, mandatory reason, audited) copies the draft form into a new immutable `service_form_schemas` row (`published`, next version number; the previous published row becomes `archived`, its fields are never touched) and writes a `service_category_versions` row holding a JSON **snapshot** (category data, family, resolved booking/pricing/dispatch/limits/safety/payment policies, vehicle categories, form fields). `service_categories.published_version_id` points at the current version. Versions and published/archived schema rows are immutable at the model level (updating/deleting throws). Publishing with no difference to the current version is refused (`category_no_changes`; fingerprint comparison ignores JSON key order). The public API serves the snapshot of the published version, so a draft edit changes nothing for clients until published; `active` is a live switch. Bookings (P2.3) will store `service_category_versions.id`.
**Consequences.** One extra step after editing; the show page says whether the draft differs from what is live. Archived schema rows stay for history.

## ADR-66 — Activation, deactivation guards and the active-bookings seam · 2026-10-20 · Accepted

**Decision.** New families, vehicle categories and categories start **inactive**; the code is immutable after creation. A category can be activated only with a published version and an active family. Deactivation always needs a reason and is audited (`StatusChanged`, before/after). A category cannot be deactivated if it is the last active category of the platform (`catalog_last_active_category`), nor can a family that holds the last active categories. If the category has active bookings the request is rejected with `category_has_active_bookings` unless the admin ticks an explicit confirmation (the deactivate modal shows the count). There is no bookings table before P2.3, so the count comes from `CatalogUsageRepositoryInterface`, whose Eloquent implementation returns 0 now and **throws once a `bookings` table exists** until P2.3 replaces it with the real query (it must not silently under-count). A category that is active or has any published version cannot be deleted; a vehicle category linked to a category cannot be deleted; a family with categories cannot be deleted.

## ADR-67 — Form schema contract and builder · 2026-10-20 · Accepted

**Decision.** Field shape and value types are in docs/API.md 8.6. `FormFieldType` and `VisibilityOperator` are mirrored in platform_core with an `unknown` fallback (an unknown operator never matches, so the field stays hidden). `FormSchemaValidator` checks the schema itself (keys `^[a-z][a-z0-9_]{0,39}$` and unique, known type, Arabic label required, ranges valid for the type, options for select / multiselect / item_checklist only, `visible_if` pointing at an earlier field with a valid operator/value, max 40 fields / 100 options) and runs both in the admin FormRequest (errors land on the exact repeater cell) and in the services (save and publish), so no caller can store or publish an invalid schema. `ServiceFormValidator` compiles a schema to Laravel rules and validates `service_details`: unknown keys rejected, hidden fields (visibility evaluated in schema order, chaining through hidden fields) not required and dropped, results returned in schema order. The builder uses flat repeater rows (`key, type, label_ar/en, help_ar/en, required, min, max, options_text, visible_field/op/value`); options are one per line `value|Arabic|English`; `FormSchemaBuilderMapper` converts rows to and from the canonical JSON. Photo answers are attachment references (strings); upload endpoints arrive with the courier / moving milestones, so the Flutter renderer takes a `PhotoFieldHandler` (camera/gallery now, optional uploader later) and renders no add button without one.

## ADR-68 — Flutter catalog and DynamicFormRenderer · 2026-10-20 · Accepted

**Decision.** platform_core owns `CatalogRepository` + models (`ServiceFamilyInfo`, `ServiceCategoryInfo` with the published form schema, `VehicleCategoryInfo`) and the forms package: `FormFieldSchema` / `LocalizedText`, pure `FormLogic` (visibility, validation mirroring `ServiceFormValidator`, `service_details` payload), `DynamicFormController` (answers, issues shown after touch or `validate()`), `DynamicFormStrings` (all texts supplied by the app's ARB, so platform_core has no literals) and `DynamicFormRenderer` with a widget per `FormFieldType` (Directional layout, ar/en labels with Arabic fallback, Arabic-Indic digits accepted). The customer app lists families (chips) and categories (cards: capacity, from-fare via the server digit style, negotiable) on the home screen from the API, keeps the selected category and its saved `service_details` in Riverpod (consumed by the booking flow in P2.6), and opens the dynamic form from the selected-service panel (`/service-details`, ready accounts only). Zone availability (`lat`/`lng`) is sent by P2.6/P1.5, not yet.

## ADR-69 — Spatial storage and zone resolution · 2026-10-21 · Accepted

**Context.** Plan P1.5 steps 1-2, ADR-03.
**Decision.** `cities.center` is `POINT`, `service_zones.polygon` is `POLYGON` (outer ring only), `saved_places.point` is `POINT`, all SRID 4326 with a spatial index on the polygon. Every value is written with `ST_GeomFromText(wkt, 4326, 'axis-order=long-lat')` (x = longitude, y = latitude) through the `GeoPointCast` / `GeoPolygonCast` casts, which write a `GeometryExpression` (it remembers the PHP value, so a model keeps working after `save()`) and read MySQL's internal WKB with `WkbReader`. `GeoPoint` rejects non-finite, out-of-range and exact (0,0) values; `GeoPolygon` needs >= 3 distinct vertices (max 500) and is validated again by MySQL `ST_IsValid` (self-intersections are `zone_invalid_polygon`). Membership is `ST_Contains` on **active zones of active cities**; overlapping zones resolve to the smallest (`ST_Area`), overlaps are only a warning (`ST_Intersects and not ST_Touches`), the admin editor shows it live and the save message repeats it. Haversine (`Distance`) is for ranking/sanity only. Zone listings never select the polygon column. The audit snapshot of a zone stores vertex count + fingerprint, not the geometry.

## ADR-70 — Availability matrix, overrides and hours · 2026-10-21 · Accepted

**Decision.** `zone_service_category(active, overrides json)`: no row = not offered. Overrides: `min_fare_minor`, `max_fare_minor`, `dispatch_radius_steps_km`, `hours {from,to}` (one daily window). A category is bookable at a position now when the zone matrix enables it, the category is active + published, and the local time (city timezone) is inside all three layers: zone `operating_hours`, category `service_hours`, override window (same `{day 1-7, from, to}` shape, no overnight windows, empty = always open). `GeoService::resolve` returns every enabled category with `AvailabilityReason` + `opens_at`; `GET /service-categories?lat&lng` applies the matrix in `ServiceCategoryRepository::publicList(..., $zoneId)` and the hours in `CatalogReadService`. A zone goes active only with >= 1 enabled category and cannot lose its last one while active; suspending needs a reason; active zones cannot be deleted; the matrix save is guarded by the zone `lock_version`. Zone status changes while bookings exist are not checked yet (no bookings table): P2.3 must add the active-bookings guard like ADR-66.

## ADR-71 — Maps adapter · 2026-10-21 · Accepted

**Decision.** `MapsProvider` (geocode, reverseGeocode, placesSearch, route, name, status) with drivers `osrm_nominatim`, `google`, `fake` (local/testing only, boot-guarded) registered in `IntegrationServiceProvider`; real drivers are wrapped in `CachingMapsProvider` (successes only; keys include provider, locale, ~1 m rounded points). Adapters throw `MapsUnavailableException` (transport, quota, credentials, garbage) or `NoRouteException`; `MapsService` maps them to `503 maps_unavailable` (also when no driver is configured) and `422 route_not_found`; nothing is invented on failure. Google exceptions are not chained so the key in a URL cannot leak. Nominatim calls are spaced by a cache lock (`MAPS_NOMINATIM_MIN_INTERVAL_MS`) and send a User-Agent. Results carry `precision` (`exact|street|approximate`) so clients never present approximate positions as exact addresses. Endpoints that spend quota need a signed-in app; `routes/estimate` requires the origin to be inside a zone (and the category available) before calling the provider.

## ADR-72 — Saved places and Flutter map flow · 2026-10-21 · Accepted

**Decision.** Saved places are customer-token only and always read through the owner id (foreign uuid = 404); creation runs under a lock on the user row (one home, one work, max 20). The address is a client snapshot, a hint only. Flutter: platform_core `GeoRepository` + models (`PlaceResult`, `ServiceAreaInfo`, `SavedPlaceInfo`, `RouteEstimateInfo`, enums with unknown fallbacks); customer_app `map` feature: `TripLocationPanel` on home (flutter_map, `MAP_TILE_URL` + attribution from Env, injectable tile provider), rationale-first location permission (`UserLocationNotifier` never prompts by itself) with manual fallback, debounced (350 ms, stale answers dropped) `PlaceSearchScreen`, `PinAdjustScreen` (reverse geocode after the map settles; a failed lookup never blocks confirming), swap, `SavedPlacesScreen`, outside-area / area-check-failed / restricted-pickup banners. The pickup (rounded to ~10 m) drives `serviceAreaProvider` and the catalog request (`lat`/`lng`). The map default center is downtown Cairo until app config provides one.

## ADR-73 — Customers admin: scope, account standing and actions · 2026-10-21 · Accepted

**Context.** Plan P1.6, SRS §16.2. A customer is a `users` row with a `customer_profiles` row (a provider-only account is not listed).
**Decision.** Customers are addressed by uuid in the panel and stay reachable after anonymisation (`withTrashed`). There is no create, edit or delete route and `UserPolicy` has no delete ability: customers register through the apps and can only leave through a processed deletion request. Standing lives on `users` (`status`, `status_reason`, `status_changed_at`, `status_expires_at`, `anonymised_at`). Allowed transitions (policy hides the button, `CustomerAccountService` re-checks under a row lock, `customer_status_transition_invalid`): suspend from active / restricted / verification_required (optional expiry, `suspension_expiry_in_past` if not in the future); restrict from active; reactivate from suspended / restricted / verification_required (always to `active`). Suspend, restrict, reactivate, send help message and both deletion decisions need a reason of 5-1000 characters and write before/after + reason to the audit log (`AuditAction::Suspended|Restricted|Reactivated|MessageSent|Anonymised|Rejected`). Actions that need extra fields (suspension end, message text) use `<x-admin.action-modal>` (a modal around `<x-admin.form>` with the mandatory reason field); reason-only actions use `<x-admin.confirm-action>`.
**Suspension expiry.** `ReactivateExpiredSuspensions` is scheduled every minute (`withoutOverlapping`); it re-checks each account under its own lock, so a manual reactivation, restriction or new suspension is never overwritten, and audits as the system actor. **Help messages** are stored in `customer_messages` and read by the customer through `GET /me/messages` (status-safe, owner-scoped); push delivery arrives with P3.6.
**Consequences.** `restricted` still only blocks endpoints that ask for strict checking (booking creation, P2.3). The Flutter inbox screen is not built yet (API only).

## ADR-74 — Customer PII: masking, reveal, search and exports · 2026-10-21 · Accepted

**Decision.** Phone, email and emergency-contact phone are masked everywhere (`PhoneNumber::mask`: `+20••••4567`), including the listing JSON and CSV exports; DataTables output is an explicit `only([...])` allowlist because Yajra otherwise serialises every model attribute. A reveal is a separate `POST customers/{uuid}/reveal` that needs `customers.view_pii`, returns the values once, and writes `AuditAction::PhoneRevealed` naming the fields (never the values). Saved places (home/work addresses) are PII too: their tab and route need `customers.view_pii`. The listing search box matches an exact uuid, an exact full international number, or the END of a number given as 4+ digits (the mask already shows the last four digits, so searching reveals nothing more), otherwise the name. Listing CSV export (`customers.export`) reuses the queued export + signed-link machinery with masked phones. The per-customer **personal data export** (`customers.export`) is a queued JSON file through the same machinery (`admin_exports.format = json`, `subject_id`): it contains the full phone, profile, devices (no push tokens), consents, saved places, deletion requests and help messages, lists the modules whose data is not stored yet in `sections_pending`, and is audited against the customer. Owner-only signed download, 7-day retention (unchanged).
**Consequences.** A role with `customers.view` but not `customers.view_pii` can find a customer by the last digits yet never sees more than the mask. The city lookup became available to every admin (public reference data) so the Customers filter works without `service_areas.manage`.

## ADR-75 — Deletion processing: anonymisation and retention rules · 2026-10-21 · Accepted

**Context.** SRS §13.3 (retention schedules, legal holds, explain what must be retained), §6.2.
**Decision.** Approving a request **anonymises, never deletes**: the `users` row keeps its id and uuid and is soft-deleted with `status = deleted` and `anonymised_at`, so bookings, payments, ledger entries, refunds and invoices (which reference `user_id`) stay valid and auditable. **Removed:** name, email, phone (frees the unique number for a new registration), phone verification, photo (file deleted after commit), all API tokens, all devices and push tokens, saved places, help messages, and the customer profile contents (accessibility needs, emergency contact, marketing opt-in). **Retained:** the account row and its financial references; consent records (legal proof of what was accepted, with the IP at the time; append-only by design); the deletion request itself with the decision, admin and reason; the admin audit trail (which holds no contact data); and any booking, payment, ledger, rating, ticket and incident record owned by later modules, each following its own retention schedule (P6.2) and legal/safety holds. Approval is **refused** while the customer has active bookings (`customer_has_active_bookings`) or unsettled money (`customer_has_open_financial_items`), and for accounts that are also providers (`customer_is_provider`; handled in the provider module). Rejecting restores the status the account had before the request (including any suspension and its expiry). The block checks go through `CustomerActivityRepositoryInterface`, a seam like `CatalogUsageRepository`: its implementation throws as soon as a `bookings`, `payments`, `wallets` or `journal_entries` table exists, so P2.3/P3.1 must replace it with real queries instead of silently approving deletions.
**Consequences.** Anonymisation is irreversible; the confirm dialog says so and requires a reason. A later module that stores customer personal data (tickets, chat, ratings text) must add its scrub step to `CustomerDeletionService` and its section to `PersonalDataExporter`.

## ADR-76 — Customers listing filters and not-yet-available modules · 2026-10-21 · Accepted

**Decision.** Appendix C filters: search (uuid / phone / name), status (multi), registered range, deletion requested, and **city**. Customers have no home city yet, so "city" matches customers with a saved place whose point lies inside a zone of that city (`ST_Contains`); this is documented in the filter label. **Has active booking** needs bookings, so the filters card shows the filter as "available after P2.3" instead of a dead control. The Bookings, Payments, Coupons, Tickets and Ratings tabs render `<x-admin.available-after>` naming the milestone; no fake rows. Lazy tabs (consents, devices, saved places, data requests, messages, restrictions/audit) are their own Yajra DataTables served from their own URLs (HTML fragment on a normal request, rows on the DataTables AJAX call).
**Fix found while building.** `adminTabs` used Alpine's `$el` (the clicked element inside event handlers) instead of `$root`, so lazy tabs only loaded when they were the initial tab and tab keyboard navigation found no tabs. Both now use `$root`; the browser smoke covers the click path.

## ADR-77 — Deleting an account that is also a provider account · 2026-10-22 · Accepted

**Context.** P1.6 refused every deletion of an account with a provider profile (`customer_is_provider`) and left the provider side to P2.1. Provider accounts hold identity documents, vehicle papers and a birth date, which are the most sensitive data of the platform.
**Decision.** A deletion request (from either app, `POST /me/account-deletion-requests`) puts the provider profile in `deletion_pending` and remembers the previous provider status on the request (`previous_provider_status`); cancelling or rejecting the request restores it (a temporary suspension loses its automatic end and has to be lifted by an admin). Approving is a **Providers-module** action (`POST /admin/providers/{uuid}/deletion-requests/{request}/approve`, mandatory reason) that needs both `customers.process_deletion` (the legal action) and `drivers.view`; the Customers module keeps refusing these accounts with `customer_is_provider`, now pointing to the provider module. Approval **anonymises, never deletes**: removed are the identity and vehicle **document files** (deleted from the private disk after commit), their **numbers**, the **vehicle photos**, the **birth date** and the city, and everything the customer flow already removes (name, email, phone, photo, tokens, devices, saved places, messages, customer-profile contents). Retained: the review history (document types, statuses, reviewer, dates, rejection reasons), the status events, the vehicles' **make, model and plate** (completed jobs, insurance and safety records reference them; the vehicles become `inactive`), the provider aggregates and the audit trail (which holds no contact data), all without a link back to a person. The profile stays `deletion_pending` (the account status `deleted` and `anonymised_at` say it is finished). Approval is refused while the provider has active jobs or unsettled money (`ProviderActivityRepositoryInterface`, a seam like `CustomerActivityRepository`: it **throws once `bookings`, `payments`, `wallets`, `journal_entries` or `payout_requests` exist**, so P2.3/P3.1/P3.4 must replace it with real queries) and for the same customer-side blocks as ADR-75. The shared account steps live in `AccountScrubber`, used by both flows.
**Consequences.** One legal permission covers both account kinds; an officer without it cannot erase anything. Anonymisation is irreversible and the confirm dialog says so.

## ADR-78 — Provider status machine and onboarding flow · 2026-10-22 · Accepted

**Context.** Plan §7.3, SRS §7.1, invariants I10/I11.
**Decision.** `ProviderStatus::allowedTransitions()` is the only graph and `ProviderStatusService::transition()` the only writer of `provider_profiles.status` (history row in `provider_status_events`, `ProviderStatusChanged` event after commit). `online_eligible` and `expired_documents` are **not** statuses. `POST /provider/applications` creates the profile and walks `draft -> phone_verified -> profile_submitted -> documents_pending` in one transaction (the phone is already verified by the OTP flow); the applicant edits profile, services, vehicles and documents while the status is `phone_verified`, `profile_submitted`, `documents_pending`, `more_information_required` or `rejected` (documents and vehicles also while `under_review`, `approved` and `temporarily_suspended`, so renewals work). `POST /provider/application/submit` re-computes the progress under the row lock and refuses with `422 application_incomplete` listing typed tasks until: profile complete (type, city, name, date of birth unless company/fleet, at least 18 years), >= 1 requested service, >= 1 vehicle submitted for review, every mandatory provider and vehicle document uploaded (pending or approved), and every required policy accepted (terms, privacy and **driver terms**: `ClientApp::requiredPolicyTypes()` now differs per app; the driver app collects them in the declarations step, so sign-in still does not block). Admin decisions: **approve** (`drivers.verify`; every mandatory provider document approved; the officer ticks which requested services are approved, the rest are rejected), **request more information** (`drivers.verify`; mandatory message, optional per-document reasons which reject those pending documents), **reject** (`drivers.reject`; mandatory reason, resubmittable). Suspend/reactivate (`drivers.suspend`, mandatory reason, temporary with optional end or permanent; a permanent suspension cannot be lifted from the panel) mirrors customers; `ReactivateExpiredProviderSuspensions` runs every minute. A new service request of an approved provider starts as `requested` and is decided on the Services tab.
**Consequences.** Every transition is audited with before/after and reason; applicants see the reviewer's reasons (rejection, more information) and nothing else from admin notes.

## ADR-79 — Document types, requirements and the checklist · 2026-10-22 · Accepted

**Decision.** `document_types` (translatable name, `applies_to` provider|vehicle, expiry and number flags, allowed MIME types, max size, `lock_version`) and `document_requirements` (document type + optional country, city, service category, vehicle category, provider type + `mandatory`) are admin data (`documents.manage_types`, reason + audit on every requirement change). A requirement applies when **every** non-null scope matches the owner: the provider's city and its country, a **requested or approved** service category (a rejected one no longer scopes), the vehicle category of the vehicle (provider-level requirements use the categories of the provider's non-inactive vehicles), the provider type. A type reached by several rows is mandatory when any is. `DocumentChecklistService` is the resolver and status summariser: per type the owner is `approved` (a currently valid approved document, expiry date inclusive), else `pending`, else the latest `rejected`/`expired` (an approved document past its date reads `expired` even before the daily job), else missing. Uploads of types nobody requires are kept but never counted. A new upload **replaces the owner's pending upload of the same type** (soft-deleted, file removed); reviewed documents stay as history. Seeds (`DocumentTypeSeeder`) are Egypt defaults flagged as starting points to be reviewed for local compliance, not legal advice.
**Consequences.** Nothing in code says which document a market needs; changing a requirement changes eligibility at once because eligibility is computed.

## ADR-80 — Private files, upload pipeline and the file scanner · 2026-10-22 · Accepted

**Decision.** Documents and vehicle photos live on the `private` disk (`PROVIDER_DOCUMENTS_DISK`, local `storage/app/private-files`, an S3-compatible disk in production) under random paths; the original name is kept only as a sanitised display value. `UploadInspector` checks, before anything is stored: size against the type's limit and a hard cap, the **client extension against the sniffed MIME type** (finfo, never the browser's), that MIME against the type's allow-list, a real **decode** of images with a pixel cap (decompression-bomb guard) and a minimum edge, **re-encoding to JPEG** (drops EXIF/GPS and appended payloads; scaled to 2400 px for documents, 1600 px for vehicle photos) and, for PDFs, the `%PDF-` header and a refusal of `/JavaScript`, `/JS`, `/Launch`, `/EmbeddedFile`, `/RichMedia`, `/SubmitForm`, `/ImportData` (a heuristic on raw bytes, documented as such). Then the `FileScanner` adapter (ADR-12): `clamav` (clamd INSTREAM over TCP, parsed from the clamd documentation, **untested against a live clamd**), `noop` (local/testing only, boot-guarded; stores `scan_status = skipped`, never `clean`), or **no driver configured**, which stores `not_scanned` and the review screen shows that in a warning badge (no silent success). An infected file is refused (`422 document_infected`), an unreachable engine fails closed (`503 file_scanner_unavailable`). Document **numbers are encrypted** (`encrypted` cast), never returned except masked (last three characters) and never written to the audit log. Access: the driver gets `file_url` as a signed route valid 5 minutes that still needs the owner's token and an ownership check (`403` otherwise); admins call a permissioned endpoint (`documents.view`) that issues a signed URL **bound to that admin's id**, valid 5 minutes; the serve route re-checks signature, admin binding and permission, then writes the audit entry (`viewed` or `downloaded`, document uuid, type, scan status; against the provider or the vehicle) **when the file is actually served**, so issuing a link audits nothing and every load of a file is one audited access. Responses carry `nosniff`, `private, no-store` and, for images, a `default-src 'none'; sandbox` CSP (not applied to PDFs, which browsers refuse to render in a sandbox). Upload endpoints are rate limited (`rate_limit_provider_uploads_per_minute`, default 20) and take an `Idempotency-Key`.
**Consequences.** A leaked URL is useless after 5 minutes and for any other admin; documents deleted by anonymisation (ADR-77) disappear from the disk.

## ADR-81 — Eligibility is computed, never stored · 2026-10-22 · Accepted

**Decision.** `ProviderEligibilityService::check(profile, ?serviceCategory): EligibilityResult` returns typed `EligibilityReason`s with a reference (document type code, `vehicleUuid:code`, category id): `account_blocked` (user status blocks strictly, including `restricted`), `provider_suspended`, `not_approved`, `document_missing|document_not_approved|document_expired` for every unsatisfied mandatory provider document, `no_approved_vehicle`, the same document reasons for an **approved vehicle whose own mandatory documents lapse** (reported only when no other approved vehicle is fully valid), and with a category `service_not_approved` and `vehicle_category_mismatch` (a usable vehicle must be of a category linked to the service). `usableVehicleIds` lists approved vehicles with every required document valid. The driver app reads `GET /provider/eligibility`; the online toggle, dispatch and offer submission (P2.2+) will call the same service and extend it with location freshness, active job and debt limit. `documents:check-expiry` (daily 02:00) marks expired documents (audited as the system, notification hook), sends reminders at the configured thresholds (30 and 7 days, once each, tracked per document) and takes an online provider offline when it stopped being eligible. The notification hook is `ProviderNotifier`, a log-only stub until P3.6 (its log lines say nothing was sent).

## ADR-82 — Verification queue, review screen and permissions · 2026-10-22 · Accepted

**Decision.** The queue lists `under_review` and `more_information_required`, oldest submission first (default order applied when the table sends none), with a waiting-time badge against the setting `provider_verification_sla_hours` (24 by default; warning at the target, danger at twice) and a "claim" action that assigns the officer (audited). The review screen shows the documents and vehicle photos in a client-side viewer: **no file is in the page**; selecting an item asks the server for a fresh signed URL (ADR-80) and refreshes it before it expires. Per document: approve (`drivers.verify` / `vehicles.approve`), reject with reason (`drivers.reject` / `vehicles.reject`); decisions on the application as in ADR-78; vehicles are approved or rejected on the same screen or in the Vehicles module (`vehicles.approve|reject`; approval needs every mandatory vehicle document approved and sets the provider's current vehicle when it has none). Buttons are hidden by policy abilities and every route and service re-checks them: a read-only role gets 403 on every decision and sees "you can review but not decide". Document numbers are shown masked; revealing them (`drivers.view_pii`) is not built (known gap). The reveal of contact details and birth date (`drivers.view_pii`) is audited without values, like customers (ADR-74).
**Also.** The `service-categories` and `vehicle-categories` lookups (names of public catalog data) are open to any signed-in admin, like `cities` (ADR-74), because the provider filters need them. New typed settings: `provider_verification_sla_hours`, `provider_document_expiry_warning_days` (30), `rate_limit_provider_uploads_per_minute`.

## ADR-83 — Flutter driver application wizard · 2026-10-22 · Accepted

**Decision.** platform_core owns `ProviderRepository` (the only code that knows these endpoints), the models (`ProviderApplication` with `steps`, `resume_step`, typed `tasks`, the checklist, vehicles, policies, support contact) and the enums with `unknown` fallbacks. driver_app `provider_onboarding`: the hub screen shows the **wizard** (profile, services, vehicles, documents, declarations, review) while the server status is a filling-in one, the **verification status** screen otherwise; the wizard opens at the server's `resume_step`, so a driver resumes on any device. Every step saves to the server before moving on and refreshes the application from the answer (a failed re-read keeps what is on screen). Vehicles and document uploads are their own screens (photo from camera or gallery through `PhotoFieldHandler`, number, issue and expiry date pickers). **One idempotency key per user intent**: kept across retries of the same upload, renewed when the driver changes what is sent (a different payload with the same key would be refused). Error text comes from ARB by the server's stable `code`, never from server strings; unknown codes fall back to a generic text. Declarations read the pending policies from `/me` and accept them through the existing consent endpoint. iOS Info.plist gained the camera and photo-library usage strings. The vehicle minimum photo count (2) is mirrored in a hint text only; the server decides.
