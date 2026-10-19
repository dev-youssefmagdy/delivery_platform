# Implementation Status

> Updated at the end of every milestone (Session Preamble close-out). Legend: ✅ done · 🟡 partial · ❌ not started.
> Never mark ✅ unless the gate (Plan §11.1) was run and seen passing.

## Milestones

| ID | Milestone | Priority | Status | Updated |
|---|---|---|---|---|
| P0.1 | Repository audit, git, documentation spine, tooling baseline | P0 | ✅ | 2026-10-13 |
| P0.2 | Backend core architecture | P0 | ✅ | 2026-10-13 |
| P0.3 | Admin UI kit, AJAX/Precognition forms, DataTables, filters card, generator | P0 | ✅ | 2026-10-19 |
| P0.4 | Flutter foundation + shared package | P0 | ✅ | 2026-10-19 |
| P1.1 | Admin authentication, roles/permissions, admin users, audit log UI | P0 | ❌ | — |
| P1.2 | Settings, markets, locales, policy versions, app config API | P0 | ❌ | — |
| P1.3 | Identity API: OTP, users, profiles, devices, consents, deletion | P0 | ❌ | — |
| P1.4 | Service catalog, vehicle categories, form schemas, versioned snapshots | P0 | ❌ | — |
| P1.5 | Geography: cities, service zones, availability, maps adapter | P0 | ❌ | — |
| P1.6 | Customers admin module | P0 | ❌ | — |
| P2.1 | Provider onboarding, vehicles, documents, verification | P0 | ❌ | — |
| P2.2 | Availability, live location, eligibility, dispatch engine | P0 | ❌ | — |
| P2.3 | Reference pricing & booking creation | P0 | ❌ | — |
| P2.4 | Offers, counter-offers & atomic selection | P0 | ❌ | — |
| P2.5 | Trip lifecycle: heading, arrived, PIN, start, complete, cancellation v1, ratings, share link | P0 | ❌ | — |
| P2.6 | Customer app: complete ride flow | P0 | ❌ | — |
| P2.7 | Driver app: complete ride flow | P0 | ❌ | — |
| P2.8 | Admin: bookings, offers monitor, live operations v1 | P0 | ❌ | — |
| P2.9 | E2E ride test, race tests, MVP hardening | P0 | ❌ | — |
| P3.1 | Double-entry ledger, wallets, commission rules, price breakdown | P1 | ❌ | — |
| P3.2 | Payments: gateway adapter, cash flow, webhooks, tips | P1 | ❌ | — |
| P3.3 | Coupon engine and coupon manager | P1 | ❌ | — |
| P3.4 | Provider earnings, payouts, adjustments, cash reconciliation | P1 | ❌ | — |
| P3.5 | Cancellation/no-show policies with fees, fare adjustments, receipts | P1 | ❌ | — |
| P3.6 | Notifications platform (push, SMS, email, in-app inbox) | P1 | ❌ | — |
| P3.7 | Booking chat and support tickets/disputes | P1 | ❌ | — |
| P3.8 | Flutter: wallet, payments, coupons, earnings, payouts | P1 | ❌ | — |
| P4.1 | Courier/parcel backend | P1 | ❌ | — |
| P4.2 | Furniture moving & freight backend (scoped offers) | P1 | ❌ | — |
| P4.3 | Flutter courier/moving/freight flows | P1 | ❌ | — |
| P4.4 | Admin & E2E for non-ride services | P1 | ❌ | — |
| P5.1 | Safety center, SOS, trusted contacts | P1 | ❌ | — |
| P5.2 | Reviews moderation & risk signals | P1 | ❌ | — |
| P5.3 | Operations dashboard, reports, exports | P1 | ❌ | — |
| P5.4 | CMS | P1 | ❌ | — |
| P5.5 | Public website | P1 | ❌ | — |
| P5.6 | Integration health, app versions, feature flags, system pages | P1 | ❌ | — |
| P5.7 | Referrals, loyalty, provider incentives | P2 | ❌ | — |
| P6.1 | Security, IDOR, race & webhook review | P0 | ❌ | — |
| P6.2 | Performance, indexes, retention | P0 | ❌ | — |
| P6.3 | Localization, RTL & accessibility review | P0 | ❌ | — |
| P6.4 | CI, deployment, backups, release report | P0 | ❌ | — |

---

## P0.4 — Flutter foundation + shared package · ✅ 2026-10-19

Branch `milestone/p0.4-flutter-foundation` (from `main` @ `9debc43`), fast-forwarded into `main`.

### What was built

- `packages/platform_core` (pure Flutter package, path dependency of both apps): `Env` (`--dart-define`), `ApiClient` (dio; request-id, Accept-Language, bearer, 401 -> `sessionExpired`, GET-only retry with backoff, `Idempotency-Key` helper), envelope parsing into `ApiResult`/`ApiException(kind, code, message, fieldErrors, requestId)`, `TokenStore` (secure storage + memory) and `SessionController`, 28 backend-mirrored enums with `unknown` fallback, `Money` + `MoneyFormatter` (Arabic-Indic digit preference), `RealtimeClient` (dart_pusher_channels, private-channel auth) + `SequenceTracker`, `LocationService` + rationale-first permission flow, `DynamicFormRenderer` (7 of 15 field types), design tokens, Material 3 theme with `UiDensity` (standard/driver), shared widgets (AppButton, AppTextField, PhoneField EG, OtpField, MoneyText, StatusChip, Timeline, OfferCard, EmptyState, ErrorState, LoadingView, Skeleton, OfflineBanner, ConfirmSheet, AttachmentPicker, RatingStars).
- `customer_app` and `driver_app`: `bootstrap.dart` (storage + session + locale before first frame, error zones), go_router (`splash -> language -> onboarding -> auth`, guarded `/home`), ARB `ar` (template) + `en` via gen-l10n, language picker (Arabic preselected, persisted, live RTL/LTR flip), offline banner, `AsyncStateView` (loading/data/empty/error+retry/offline/session-expired), `app_config` example feature (DTO freezed+json, entity, datasource, repository, riverpod_generator provider; tested with a mocked ApiClient, not wired into any screen until P1.2). Driver theme uses 64 dp targets and 1.15x type.
- Native: app ids `com.deliveryplatform.customer|driver` (Android `-PappId`, iOS `AppConfig.xcconfig`), Arabic default names with English, INTERNET/location permissions, iOS `InfoPlist.strings` ar/en (hand-added to the Xcode project), min Android 24 / iOS 15, no signing material.
- Docs: ADR-43..45, SETUP, INTEGRATIONS (dart-defines), platform_core README.

### Commands and results (2026-10-19)

| Command | Result |
|---|---|
| `scripts/check.sh flutter` (pub get, `dart format --set-exit-if-changed`, `flutter analyze`, `flutter test` in customer_app, driver_app, platform_core) | "All checks passed." |
| `flutter test` platform_core / customer_app / driver_app | 43 / 21 / 22 tests passed |
| `flutter analyze` (3 projects) | No issues found |
| `flutter build apk --debug` customer_app / driver_app | both built `build/app/outputs/flutter-apk/app-debug.apk` (Android SDK present; Gradle auto-installed platform 37, first build ~4 min) |

### Known gaps / notes

- iOS build not run (no Xcode on Linux); `project.pbxproj` was edited by hand for InfoPlist.strings and `$(APP_BUNDLE_ID)`, verify on first macOS build.
- `pinput` replaced by an in-house `OtpField` and `permission_handler` pinned to ^12 (ADR-43).
- RealtimeClient and LocationService are compiled and typed but not exercised against a live Reverb server/device (no backend channels until P2.x); `DynamicFormRenderer` supports 7 of 15 field types (rest in P1.4/P2.x/P4.x).
- Only the Android-emulator defaults exist for `API_BASE_URL`; there is no `/config/app` endpoint yet (P1.2), so the config repository is exercised only with a mocked ApiClient.
- Golden tests and `integration_test` flows are not added yet (no real flows until P1.3+).
- Shared ARB strings are duplicated per app.

### Next steps

P1.2 wires `appConfigProvider` (startup fetch, cache, maintenance and force-update screens) and the `/config/app` DTO must match the real contract; P1.3 replaces `AuthScreen` with phone OTP using `PhoneField`/`OtpField`, `SessionController.signIn`, and `newIdempotencyKey()` per OTP/verify intent.


---

## P0.3 — Admin UI kit, AJAX/Precognition forms, DataTables, filters card, generator · ✅ 2026-10-19

Branch `milestone/p0.3-admin-ui-kit` (from `main` @ `4f36c3c`), fast-forwarded into `main`.

### What was built

- **Build (ADR-34):** Vite entries `admin` + `website` (no shared CSS); pinned Alpine/axios/jQuery 3/DataTables 2.3 + Responsive 3/
  Tom Select/flatpickr (ar)/Leaflet + draw/Chart.js; self-hosted IBM Plex Sans Arabic (OFL). Tailwind 4 `@theme` tokens (brand,
  semantic success/warning/danger/info/neutral, radius, shadows, font) + skins for DataTables, Tom Select, flatpickr, Leaflet.
- **Blade components (`resources/views/components/admin`, 69 files):** layouts.app/auth, sidebar (config/admin_menu.php, permission +
  environment aware, collapsible, mobile off-canvas), topbar (ar/en switcher, notifications when the admin is notifiable, profile),
  page-header, card, tabs(+panel, lazy URL panels), section, grid, alert, badge (any HasLabel&HasColor enum), toast-stack, empty-state,
  skeleton, spinner, stat-tile, button, icon-button, icon, dropdown(+item), confirm-action (mandatory reason), modal, drawer,
  form (+ section, field, label, field-error, input, textarea, select, enum-select, remote-select, checkbox, toggle, radio-group, date,
  datetime, date-range, money, translatable-input/-textarea, file, map-point, polygon, key-value, repeater, actions), datatable,
  filters-card (collapsed by default, active-count badge, URL sync), filters.text/select/enum/remote-select/date-range/number-range/
  boolean, table.actions (policy-filtered), detail-list(+item), timeline, money, user-chip, map-preview, json-viewer.
- **JS (`resources/js/admin`):** core/config, http (axios: CSRF, JSON, X-Request-Id, locale; error normaliser), ajax-form (Plan §5.3
  protocol, live Precognition-Validate-Only, double-submit lock), form-errors (dot→bracket, nested arrays, tabs error dots,
  summary), client-validation, response-instructions, toast, modal, confirm-action, datatables (AdminTables registry), filters-card,
  remote-select, date-pickers, maps, exports, charts; components/tabs, components/ui (dropdown, sidebar, repeater, money, file).
- **Backend:** `AdminController`, `AdminFormResponse`, `AdminDataTable` (defaults, column helpers, allowlist, export hooks),
  `FilterData` contract + `ParsesFilterInput`, `TableAction`, `AdminMenu`, `AdminAccess`, `FieldName`, lookups (`LookupRegistry`,
  `Lookup`, `CountryLookup`, `LookupService`, `LookupController`), queued exports (`admin_exports`, `AdminExportService`,
  `GenerateAdminExport`, `ExportController`), `AuditTrailService` + activity partial, `LocaleController`, `HomeController`,
  `AdminPermission` enum (Appendix A catalog), `AllowLocalGuestAccess` (temporary), `EnsureDemoModuleEnabled`.
- **Generator:** `php artisan make:admin-module Coupon --module=Marketing --translatable=title --enum-status=CouponStatus
  --group=marketing [--title-ar= --singular-ar= --title-en= --singular-en= --force]` → 22 files + shared-file edits (ADR-41).
- **Demo Item module** (local/testing only, ADR-42), generated then extended to use every component.

### Commands and results (run 2026-10-19)

| Command | Result |
|---|---|
| `composer require yajra/laravel-datatables-buttons:^13.2` | v13.2.1, no advisories |
| `npm install` (pinned deps) | 0 vulnerabilities |
| `vendor/bin/pint --test` | passed |
| `vendor/bin/phpstan analyse` | passed, 0 errors |
| `php artisan test --parallel` | passed — 126 tests, 665 assertions |
| `npm run build` | built OK (admin app.js 367 kB / 126 kB gzip; lazy chunks for Tom Select, flatpickr, Leaflet, Chart.js) |
| `npx vitest run` | passed — 2 files, 11 tests (ajax-form protocol + helpers) |
| Generated `Coupon` (translatable+status) and `Widget` (plain) on a full app copy | Pint passed, PHPStan 0 errors, 18 generated tests passed, unmodified |
| `php artisan serve` smoke (local, guest scaffolding) | `/admin` 302 → demo items; index/create 200; DataTables JSON 200 without debug payload; lookups 200 |

Tests added: `Feature/Admin/Demo/{DemoItemAdminTest (generated contract), DemoItemComponentsTest}`,
`Feature/Admin/Kit/{AdminKitTest, LogicalDirectionUtilitiesTest, MakeAdminModuleTest}`, `resources/js/admin/core/{ajax-form,helpers}.test.js`.

### Deviations from Plan §5 (see ADRs)

- DataTables options rendered as JSON data attributes instead of Yajra's inline script (ADR-36).
- Export download is delivered by polling + toast with a signed link; notification inbox delivery deferred to P3.6 (ADR-37).
- Guests get 403 (no admin auth until P1.1); locale is session-only until the Admin model exists (ADR-39).
- Module routes live in `routes/admin/*.php` instead of a pasted snippet (ADR-41). Vitest 4 / jsdom 29 because of Node 20 (ADR-34).

### Known gaps / next steps

- No real browser (Playwright) run in this environment; JS behaviour is covered by Vitest + server-rendered markup tests.
- P1.1: Admin model/guard + Spatie seeding of `AdminPermission`; switch `InteractsWithAdmin` to real admins and guests →
  redirect; delete `AllowLocalGuestAccess` + `ADMIN_LOCAL_GUEST_ACCESS`; persist the locale on the admin; add `admin` to the morph map.

## P0.2 — Backend core architecture · ✅ 2026-10-13

Branch `milestone/p0.2-backend-core` (from `main` @ `202dd48`), fast-forwarded into `main`.

### What was built

- **Packages** (ADR-25): sanctum 4.3.3, reverb 1.12.0, spatie/laravel-permission 8.3.0, spatie/laravel-translatable 6.14.1,
  yajra datatables-oracle 13.3.1 + html 13.0.0, pragmarx/google2fa 9.1.0. Guzzle downgraded 8→7 (Reverb needs psr7 ^2).
- **Structure:** Plan §4.1 folders (empty ones hold `.gitkeep`); `RepositoryServiceProvider` (`$bindings`), `IntegrationServiceProvider`
  + `IntegrationGuard` (fake/log drivers refused outside local/testing), `config/integrations.php`.
- **Base abstractions:** `BaseRepositoryInterface`/`BaseRepository`; `HasLabel`/`HasColor`/`EnumHelpers`/`BadgeColor`; common enums
  (`Locale`, `ActorType`, `Platform`, `AuditAction`, `QueueName`, `RateLimiterName`, `HealthStatus`); Money VO set (ADR-30);
  `ApiResponse` (success/created/error/paginated).
- **Exceptions:** `DomainException` → `BusinessRuleViolation` (422) / `ConflictException` (409) / `ForbiddenActionException` (403);
  `ApiExceptionRenderer` for validation, auth, authorization, not found, throttle (retry metadata), 405, 419, other HTTP, 500 (ADR-26).
- **Middleware:** `CorrelationId` (global; Laravel Context), `SetLocale` (ADR-29), `Idempotency` (`idempotent`, table `idempotency_keys`,
  ADR-28), `ForceJsonResponse` (api).
- **Routes:** `api_v1.php`, `admin.php` (ADMIN_PATH, web + Precognition), `website.php` (replaces `web.php`), `channels.php`, `console.php`.
- **Audit:** `admin_audit_logs` + `AdminAuditLog` (append-only) + `AuditLogRepository` (append only) + `AuditLogger` + `Auditable`.
- **Common tables:** `currencies`, `countries` (+ factories, repositories, idempotent seeders EG/EGP), `DatabaseCurrencyRegistry`.
- **i18n:** `lang/{ar,en}/{validation,auth,pagination,passwords,enums,api,admin}.php`; full Arabic validation file.
- **Rate limiters:** api, otp-request, otp-verify, location, offers, public-forms, admin-login (`config/rate_limits.php`).
- **Realtime/queues/scheduler:** Reverb configured (`BROADCAST_CONNECTION=reverb`, env documented; `reverb:start` verified),
  named queues enum, `system:heartbeat` every minute, hourly `model:prune` for idempotency keys.
- **Health:** `GET /api/v1/health` (db, redis, queue, scheduler; statuses only; 503 when failing).

### Files touched

Backend: `composer.json/lock`, `bootstrap/{app,providers}.php`, `config/{admin,idempotency,rate_limits,integrations,health,sanctum,reverb,
permission,broadcasting,cors}.php`, `app/{Console,DTOs,Enums,Exceptions,Http,Integrations,Models,Providers,Repositories,Services,Support}/**`,
`database/{migrations,factories,seeders}/**`, `lang/**`, `routes/**` (`web.php` removed), `.env.example`, `tests/**`
(`tests/Unit/ExampleTest.php` removed). Docs: `ARCHITECTURE.md`, `API.md`, `INTEGRATIONS.md`, `DECISIONS.md` (ADR-25..33), this file.

### Commands and results (run 2026-10-13)

| Command | Result |
|---|---|
| `composer require -W laravel/sanctum laravel/reverb spatie/laravel-permission spatie/laravel-translatable yajra/laravel-datatables-oracle yajra/laravel-datatables-html pragmarx/google2fa` | OK; "No security vulnerability advisories found" |
| `php artisan migrate --seed` (local MySQL) | OK — 6 new migrations, Currency/Country seeders |
| `vendor/bin/pint --test` | passed |
| `vendor/bin/phpstan analyse` (level 6) | passed, 0 errors |
| `php artisan test --parallel` | passed — 97 tests, 376 assertions |
| `npm run build` | built OK |
| `curl /api/v1/health` (artisan serve) | `{"success":true,…"status":"ok","checks":{"database":"ok","redis":"ok","queue":"ok","scheduler":"ok"}}` |
| `php artisan reverb:start --port=8089` | "Starting server on 0.0.0.0:8089" |

Tests added: `Unit/Support/Money/{MoneyTest,MoneyFormatterTest}`, `Unit/Enums/EnumHelpersTest`, `Unit/Integrations/IntegrationGuardTest`,
`Feature/Api/{ApiEnvelopeTest,IdempotencyTest,HealthEndpointTest}`, `Feature/Support/{LocaleNegotiationTest,AuditLoggerTest,RoutingTest,
CommonReferenceDataTest,RateLimitersTest}`.

### Known gaps / notes

- No real endpoints besides health; `routes/admin.php` and `routes/channels.php` are empty until P0.3/P1.1/P2.2.
- `tests/Concurrency/` exists but is not a PHPUnit suite yet (P2.9 adds the process-pool race tests).
- Vite entries `admin`/`website` are not split yet (P0.3). Rate limits are config-driven; settings-driven in P1.2.
- `Model::shouldBeStrict()` is on outside production (ADR-31).

### Next steps

P0.3 — Admin UI kit (and P0.4 Flutter foundation, which may run in parallel).

## P0.1 — Repository audit, git, documentation spine, tooling baseline · ✅ 2026-10-13

Branch `milestone/p0.1-foundation` (from `main` @ `bf95901`), fast-forwarded into `main`.

### What was built

- **Audit:** real baseline recorded in `docs/IMPLEMENTATION_PLAN.md` §1 (repo was already a git repo with a GitHub remote; Laravel
  installed is 13.36.0, not 13.17; both Flutter apps are untouched counter templates; no secrets in history).
- **Git:** kept existing history (ADR-20); added root `.gitignore` (Laravel, Flutter, IDE, `.env*` except `.env.example`, keystores,
  `*.p12`/`*.jks`/`*.p8`, Firebase files, service accounts, SQL dumps, backups). `BUILD_PHASES_AND_PROMPTS.md` now tracked.
- **Laravel Boost** 2.10.3 installed (`--guidelines --skills --mcp`, Claude Code): `backend/CLAUDE.md` regenerated, `AGENTS.md` synced to
  it, `backend/.mcp.json`, `backend/boost.json`, `backend/.claude/skills/*` (5 skills). Conflicts resolved in ADR-19.
- **Tooling:** Larastan 3.13 (`phpstan.neon`, level 6, `app/`), ParaTest 7.20 (for `--parallel`), `pint.json` (Laravel preset + strict
  rules, ADR-22), composer scripts `lint`, `lint:fix`, `analyse`, `test` (parallel), `check`; root `scripts/check.sh` + `Makefile`
  (`make check`, `check-backend`, `check-flutter`, `backend-fix`).
- **Config:** `.env.example` rewritten for MySQL + Redis (phpredis; redis cache/queue/session), `ar`/`ar`/`ar_EG`, every variable commented.
  Config fallbacks switched to `mysql` and `ar` (ADR-24). Local `.env` updated to match (untracked).
- **Databases:** created `delivery_platform` and `delivery_platform_testing` (utf8mb4_unicode_ci) and dedicated user `delivery_platform`
  with grants incl. `delivery_platform_testing\_%` for ParaTest (ADR-21; SQL in `docs/SETUP.md`). Access was via local MySQL root
  (password auth); the app never uses root.
- **Tests:** `phpunit.xml` → MySQL `delivery_platform_testing`, Redis DB 15, `ar` locale. New `tests/Feature/InfrastructureBaselineTest.php`
  (asserts MySQL testing DB + migrations, Redis DB 15 round-trip, `ar` default/fallback locale).
- **Docs spine:** `IMPLEMENTATION_PLAN.md`, `IMPLEMENTATION_STATUS.md`, `DECISIONS.md` (ADR-01..24), `ARCHITECTURE.md`, `API.md`,
  `INTEGRATIONS.md`, `SETUP.md`, `CONTRIBUTING.md`.

### Files touched

Root: `.gitignore`, `Makefile`, `scripts/check.sh`, `docs/*`, `BUILD_PHASES_AND_PROMPTS.md` (now tracked).
Backend: `.env.example`, `AGENTS.md`, `CLAUDE.md`, `composer.json`, `composer.lock`, `package-lock.json` (new), `phpstan.neon` (new),
`pint.json` (new), `phpunit.xml`, `boost.json`, `.mcp.json`, `.claude/skills/**`, `config/{app,database,queue}.php`,
`database/migrations/0001_01_01_00000{0,1,2}_*.php` + `routes/console.php` (Pint formatting only),
`tests/Feature/InfrastructureBaselineTest.php`.

### Commands and results (run 2026-10-13)

| Command | Result |
|---|---|
| `composer require laravel/boost larastan/larastan --dev` | OK — boost v2.10.3, larastan v3.13.0; only additions to `composer.lock`; no security advisories |
| `composer require brianium/paratest --dev` | OK — v7.20.0 |
| `php artisan boost:install --guidelines --skills --mcp --no-interaction` | OK — 7 guidelines, 5 skills, MCP for Claude Code |
| `php artisan migrate` (MySQL `delivery_platform`) | OK — 3 default migrations ran |
| `vendor/bin/pint --test` | passed (after one `vendor/bin/pint` run that reformatted 3 migrations + `routes/console.php`) |
| `vendor/bin/phpstan analyse` (level 6) | passed, 0 errors |
| `php artisan test --parallel` | passed — 5 tests, 10 assertions (ParaTest, MySQL testing DBs) |
| `composer check` | passed (lint + analyse + test) |
| `npm install` / `npm run build` | 0 vulnerabilities / built OK (Vite 8.3.4, Tailwind 4.3.3) |
| `customer_app`: `dart format --set-exit-if-changed .`, `flutter analyze`, `flutter test` | 0 changed / No issues found / All tests passed (1) |
| `driver_app`: same | 0 changed / No issues found / All tests passed (1) |
| `make check` | exit 0 — "All checks passed." |

### Known gaps / notes

- Plan step "git init + initial commit 'chore: baseline skeletons'" not executed literally: the repo already existed with
  `bf95901 first commit`, which is the baseline (ADR-20).
- Default `ExampleTest`s from the skeleton remain; they will be replaced as real features land.
- Flutter app ids are still `com.example.*` — to be set in P0.4.
- No Vitest yet (arrives with P0.3). No CI pipeline yet (P6.4).
- `backend/database/database.sqlite` leftover file exists locally (ignored, unused).

### Next steps

P0.2 — Backend core architecture (and P0.4 may run in parallel after P0.1).
