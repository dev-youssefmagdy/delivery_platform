# Implementation Status

> Updated at the end of every milestone (Session Preamble close-out). Legend: ✅ done · 🟡 partial · ❌ not started.
> Never mark ✅ unless the gate (Plan §11.1) was run and seen passing.

## Milestones

| ID | Milestone | Priority | Status | Updated |
|---|---|---|---|---|
| P0.1 | Repository audit, git, documentation spine, tooling baseline | P0 | ✅ | 2026-10-13 |
| P0.2 | Backend core architecture | P0 | ✅ | 2026-10-13 |
| P0.3 | Admin UI kit, AJAX/Precognition forms, DataTables, filters card, generator | P0 | ❌ | — |
| P0.4 | Flutter foundation + shared package | P0 | ❌ | — |
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
