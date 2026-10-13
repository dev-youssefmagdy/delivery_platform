# Implementation Plan

> Repository-specific plan required by SRS §0. The engineering standards, ADRs and milestone prompts live in
> `BUILD_PHASES_AND_PROMPTS.md` (the **Plan**); product requirements live in `indrive_inspired_delivery_platform_srs.md`
> (the **SRS**). This file records the real baseline and the milestone list with expected modules, test commands and risks.
> Live progress: `docs/IMPLEMENTATION_STATUS.md`. Decisions: `docs/DECISIONS.md`.

---

## 1. Baseline (audited 2026-10-13, milestone P0.1)

### 1.1 Repository

| Item | Real state at audit |
|---|---|
| Monorepo root `/var/www/delivery_platform` | **Already a git repository** (contrary to Plan §1): branch `main`, one commit `bf95901 first commit` (author "joe"), remote `origin = github.com/dev-youssefmagdy/delivery_platform`. 320 tracked files: the three skeletons + SRS. `BUILD_PHASES_AND_PROMPTS.md` was untracked. No root `.gitignore` (each sub-project had its own). |
| Secrets in history | Checked: `backend/.env` is **not** tracked (only `.env.example`); no keystores, `google-services.json`, `GoogleService-Info.plist`, `*.p12`/`*.jks` tracked. `vendor/`, `node_modules/`, `.dart_tool/`, `build/` not tracked. |
| `backend/` | Fresh Laravel skeleton. `composer.json` constraint `laravel/framework ^13.17`, **installed 13.36.0** (from `composer.lock`), PHP `^8.3`, `laravel/tinker 3.0.2`. Dev: PHPUnit 12.5.39, Pint 1.32.1, Collision 8.9.5, Pail 1.2.7, `laravel/pao` 1.1.5 (agent-format test/pint output), Mockery, Faker. |
| Backend code | `app/Models/User.php`, `app/Http/Controllers/Controller.php`, `app/Providers/AppServiceProvider.php`. Routes: `routes/web.php` (welcome view), `routes/console.php` (inspire). No `routes/api.php`. |
| Backend config | `config/{app,auth,cache,database,filesystems,logging,mail,queue,services,session}.php` only (no broadcasting/sanctum yet). |
| Migrations | Default three: `users` (+`password_reset_tokens`, `sessions`), `cache` (+locks), `jobs` (+`job_batches`, `failed_jobs`). |
| Tests | `tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php` (GET `/` → 200). `phpunit.xml` used SQLite `:memory:`. |
| `.env.example` | SQLite, `APP_LOCALE=en`, database cache/queue/session. Local `.env` mirrored it. |
| Frontend (backend) | `package.json`: Vite **8.3.4**, Tailwind CSS **4.3.3** (`@tailwindcss/vite`), `laravel-vite-plugin` **3.2.0**, `concurrently`. `resources/{css/app.css, js/app.js, views/welcome.blade.php}`. No lockfile, no `node_modules` before P0.1. |
| `backend/AGENTS.md`, `CLAUDE.md` | Bootstrap instructions asking to install Laravel Boost. |
| `customer_app/`, `driver_app/` | Default Flutter counter apps (`lib/main.dart`, `test/widget_test.dart`), identical. Dart SDK `^3.13.1`, deps: `cupertino_icons`, dev `flutter_lints ^6.0.0`. Platforms: android, ios, web, linux, macos, windows. App ids still `com.example.customer_app` / `com.example.driver_app` (iOS `com.example.customerApp` / `com.example.driverApp`). |
| `packages/` | Does not exist (created in P0.4). |
| `docs/` | Did not exist. |

### 1.2 Toolchain (verified on this machine)

| Tool | Version |
|---|---|
| PHP | 8.3.35 (NTS) with bcmath, intl, gd, imagick, redis (phpredis), igbinary, sodium, pdo_mysql, pcntl, sockets, zip, exif |
| Composer | 2.9.5 |
| Node / npm | 20.20.2 / 10.8.2 |
| MySQL | 8.0.46 (Ubuntu 24.04 package) |
| Redis | 7.0.15 (`redis-cli ping` → PONG) |
| Flutter / Dart | 3.47.1 stable / 3.13.1 |

### 1.3 Baseline changes made in P0.1

- Root `.gitignore`, branch `milestone/p0.1-foundation`, Boost installed, Larastan + ParaTest added, Pint config, composer
  scripts, `scripts/check.sh` + `Makefile`, MySQL/Redis/Arabic defaults, MySQL testing DB, docs spine. Details in
  `IMPLEMENTATION_STATUS.md` → P0.1.

---

## 2. Milestones

Order and dependencies follow Plan §8. "Tests" lists the commands that form the gate in addition to the always-on gate
(`make check` = Pint, PHPStan, `php artisan test --parallel`, `npm run build`, `dart format`, `flutter analyze`,
`flutter test`). SRS/Plan sections each milestone must read are in its prompt in Plan §10.

### Phase 0 — Foundation (P0)

| ID | Milestone | Depends | Expected modules / files | Key tests | Risks |
|---|---|---|---|---|---|
| P0.1 | Repo audit, git, docs spine, tooling | — | root `.gitignore`, `docs/*`, `scripts/check.sh`, `Makefile`, `backend/{phpstan.neon,pint.json,phpunit.xml,.env.example,composer.json}` | `InfrastructureBaselineTest` (MySQL testing DB, Redis DB 15, `ar` locale) | MySQL credentials/privileges on dev machines |
| P0.2 | Backend core architecture | P0.1 | `app/{Enums/Concerns,Enums/Contracts,DTOs,Repositories/{Contracts,Eloquent},Services,Support/{Money,Http/ApiResponse,Idempotency,Audit},Exceptions}`, `routes/{api_v1,admin,website,channels}.php`, middleware (SetLocale, CorrelationId, Idempotency), Sanctum, `RepositoryServiceProvider`, `IntegrationServiceProvider` | Money VO unit tests, envelope/exception-mapping feature tests, idempotency replay/conflict tests | Package support for Laravel 13 (Spatie, Yajra, Sanctum) — verify on Packagist, never `--ignore-platform-reqs` |
| P0.3 | Admin UI kit, AJAX/Precognition forms, DataTables, filters card, generator | P0.2 | `resources/views/components/admin/*`, `resources/js/admin/*`, `css/admin.css`, `AdminDataTable`, `make:admin-module` command, Vitest | Vitest (form protocol), feature tests for Precognition 204/422, generator snapshot test | jQuery/DataTables + Tailwind 4 integration; RTL logical utilities lint |
| P0.4 | Flutter foundation + `packages/platform_core` | P0.1 | `packages/platform_core` (ApiClient/dio, envelope, errors, Money, enums, tokens, widgets, realtime, location), Riverpod, go_router, freezed, ARB l10n in both apps | unit tests for envelope/Money/enums, RTL widget tests | Codegen versions vs Dart 3.13; app ids still `com.example.*` |

### Phase 1 — Core platform (P0)

| ID | Milestone | Depends | Expected modules / files | Key tests | Risks |
|---|---|---|---|---|---|
| P1.1 | Admin auth, roles/permissions, admin users, audit log UI | P0.3 | `admins` table + `admin` guard, TOTP MFA, `AdminPermission` enum, spatie/laravel-permission, `AuditLogger`, admin users/roles/audit modules | login throttle, MFA, permission matrix, audit write tests | Permission catalog drift vs Appendix A |
| P1.2 | Settings, markets, locales, policy versions, app config API | P1.1 | `SettingKey` enum, `platform_settings`, `SettingsService`, markets/locales/currencies, `policy_versions`, `GET /api/v1/config/app`, `/locales` | settings cache/lock_version 409, config API contract | Secrets accidentally stored in DB |
| P1.3 | Identity API: OTP, users, profiles, devices, consents, deletion | P1.2 | `SmsGateway` adapter, OTP service, Sanctum abilities, customer/provider profiles, devices, consents, deletion requests, `/auth/*`, `/me*` | OTP rate limits, enumeration safety, bypass only local/testing | OTP abuse; dev OTP leaking to prod |
| P1.4 | Service catalog, vehicle categories, form schemas, snapshots | P1.3 | service families/categories, vehicle categories, `service_form_schemas`, `FormFieldType`, `ServiceFormValidator`, catalog admin + API | schema→rules compilation, snapshot immutability (I4) | Schema versioning complexity |
| P1.5 | Geography: cities, zones, availability, maps adapter | P1.4 | cities, `service_zones` (spatial), `MapsProvider` adapter, zone admin (Leaflet), `/service-areas/resolve`, `/places/search`, `/routes/estimate` | point-in-polygon on MySQL spatial, adapter fakes guarded | MySQL SRID 4326 axis order |
| P1.6 | Customers admin module | P1.5 | customers DataTable + filters card, show/suspend/notes, audit | admin listing/filter/permission tests | PII exposure in listings |

### Phase 2 — Fare-negotiated ride MVP (P0)

| ID | Milestone | Depends | Expected modules / files | Key tests | Risks |
|---|---|---|---|---|---|
| P2.1 | Provider onboarding, vehicles, documents, verification | P1.6 | provider applications, vehicles, documents (private disk, signed URLs), verification queue admin | document access audit, status transitions (Plan §7.3) | File privacy, expiry handling |
| P2.2 | Availability, live location, eligibility, dispatch | P2.1 | `ProviderEligibilityService` (I11), `ProviderGeoIndex` (Redis GEO + DB impl), `provider_locations`, `DispatchBookingWave`, Reverb channels | eligibility reasons, GEO radius, wave recipients | Battery/OS limits, Redis GEO consistency |
| P2.3 | Reference pricing & booking creation | P2.2 | `PriceBreakdownCalculator` (reference), bookings + snapshots, `POST/GET /bookings`, idempotency | I4, I9, I12 tests | Client-supplied totals |
| P2.4 | Offers, counter-offers, atomic selection | P2.3 | `booking_offers` + append-only versions (I3), `OfferSelectionService` (I1, I2), expiry jobs + sweeps | concurrency tests on MySQL, 409 `booking_already_assigned` | Race conditions (core risk) |
| P2.5 | Trip lifecycle, PIN, cancellation v1, ratings, share link | P2.4 | `BookingTransitionService` (ADR-16), PIN, ratings, share tokens | transition guard matrix, PIN, share expiry | State machine bypass |
| P2.6 | Customer app ride flow | P2.5 | customer_app features (auth, booking, offers, tracking, rating) | widget/integration tests, RTL goldens | Realtime reconnection gaps |
| P2.7 | Driver app ride flow | P2.5 | driver_app features (onboarding, online, requests, offers, trip) | widget tests, location throttling tests | Foreground service/store review |
| P2.8 | Admin bookings, offers monitor, live ops v1 | P2.5 | bookings/offers DataTables, live map (Echo + Leaflet), overrides with reason | permission + override audit tests | Live map load |
| P2.9 | E2E ride test, race tests, MVP hardening | P2.6–P2.8 | `tests/Concurrency`, E2E scripts | full ride E2E, Process-pool race script | Flaky concurrency tests |

### Phase 3 — Money, coupons, notifications, support (P1)

| ID | Milestone | Depends | Expected modules | Key tests | Risks |
|---|---|---|---|---|---|
| P3.1 | Double-entry ledger, wallets, commission, price breakdown | P2.9 | `LedgerService` (I5), wallets (I6), commission rules, `ledger:reconcile` | balance assertions, reconcile | Money rounding |
| P3.2 | Payments: gateway adapter, cash, webhooks, tips | P3.1 | `PaymentGateway` adapter, `gateway_webhook_events` (I8) | webhook signature/replay, idempotent capture | No sandbox credentials |
| P3.3 | Coupon engine + manager | P3.2 | coupons, reservations (ADR-17, I7), coupon admin | cap concurrency tests | Budget overrun |
| P3.4 | Earnings, payouts, adjustments, cash reconciliation | P3.3 | payouts with dual control, adjustments | dual approval, reservation tests | Double payout |
| P3.5 | Cancellation/no-show fees, fare adjustments, receipts | P3.4 | policy engine, receipts | fee matrix tests | Policy snapshot drift |
| P3.6 | Notifications (push, SMS, email, inbox) | P3.5 | `PushNotifier`, templates ar/en, inbox | channel fan-out tests | FCM credentials |
| P3.7 | Booking chat, support tickets/disputes | P3.6 | chat, tickets, disputes admin | ownership/IDOR tests | Abuse/PII in chat |
| P3.8 | Flutter wallet, payments, coupons, earnings, payouts | P3.7 | app features | widget tests | — |

### Phase 4 — Courier, moving, freight (P1)

| ID | Milestone | Depends | Expected modules | Key tests | Risks |
|---|---|---|---|---|---|
| P4.1 | Courier/parcel backend | P3.8 | delivery contacts, recipient OTP, proofs, attempts | parcel workflow integration test | Proof storage |
| P4.2 | Moving & freight backend (scoped offers) | P4.1 | structured scope offers, items, capacity | furniture/freight workflow tests | Scope immutability |
| P4.3 | Flutter courier/moving/freight flows | P4.2 | dynamic forms in apps | widget tests | Form schema rendering parity |
| P4.4 | Admin + E2E for non-ride services | P4.3 | admin views, E2E | E2E | — |

### Phase 5 — Operations & public website (P1/P2)

| ID | Milestone | Depends | Expected modules | Key tests | Risks |
|---|---|---|---|---|---|
| P5.1 | Safety center, SOS, trusted contacts | P4.4 | incidents, SOS, trusted contacts | SOS escalation tests | — |
| P5.2 | Reviews moderation & risk signals | P5.1 | moderation queue, risk signals | — | Internal fraud signals leaking via API |
| P5.3 | Ops dashboard, reports, exports | P5.2 | Chart.js dashboards, export permissions | export permission tests | Heavy queries |
| P5.4 | CMS | P5.3 | pages, FAQs, help articles | — | — |
| P5.5 | Public website | P5.4 | `/{locale}` website, SEO | website feature tests | SEO/RTL |
| P5.6 | Integration health, app versions, feature flags | P5.5 | health page, scheduler heartbeat | health tests | — |
| P5.7 | Referrals, loyalty, incentives (P2) | P5.6 | referrals, loyalty, incentives | — | Scope creep |

### Phase 6 — Production hardening (P0)

| ID | Milestone | Depends | Expected work | Key tests | Risks |
|---|---|---|---|---|---|
| P6.1 | Security, IDOR, race & webhook review | P5.7/P5.6 | review + fixes | IDOR suite | — |
| P6.2 | Performance, indexes, retention | P6.1 | indexes, retention jobs, PHPStan → 8 | load baseline | — |
| P6.3 | L10n, RTL, accessibility review | P6.2 | translation/RTL audit | golden tests | — |
| P6.4 | CI, deployment, backups, release report | P6.3 | CI pipeline, deploy/backup docs | CI green | Store signing outside repo |

---

## 3. Standing test commands

```bash
make check                      # full gate (= scripts/check.sh all)
cd backend && composer check    # pint --test + phpstan + php artisan test --parallel
cd backend && npm run build
cd customer_app && dart format --output=none --set-exit-if-changed . && flutter analyze && flutter test
cd driver_app   && dart format --output=none --set-exit-if-changed . && flutter analyze && flutter test
```

## 4. Cross-cutting risks (from Plan §2.4, adapted)

| Risk | Mitigation in this repo |
|---|---|
| Laravel 13 package support | Verify on Packagist per milestone; record pins in DECISIONS |
| Concurrency bugs | Tests on MySQL (`delivery_platform_testing`, per-process DBs under ParaTest), row locks, idempotency |
| Missing external credentials | Adapters with fake/log drivers guarded to local/testing; INTEGRATIONS.md lists "not configured" |
| Scope explosion | Strict milestone order; status doc after each milestone; "no dead buttons" |
| RTL regressions | Logical utilities only in admin; RTL widget/golden tests in Flutter |
| Boost guideline conflicts with Plan | Plan is the approval for new folders/dependencies/docs (DECISIONS ADR-19) |
