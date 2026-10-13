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
