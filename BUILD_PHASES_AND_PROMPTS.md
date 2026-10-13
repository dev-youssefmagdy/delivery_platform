# Multi-Service Ride, Delivery & Freight Platform — Master Build Plan & Execution Prompts

> **Companion to:** `indrive_inspired_delivery_platform_srs.md` (the **SRS**, product source of truth)
> **This file:** the **Plan**: engineering standards, architecture decisions, phase roadmap, and the prompts to run, in order, until the platform is complete.
> **Author role:** Senior software architect / tech lead
> **Created:** 2026-10-13

---

## Table of Contents

0. [How to Use This Document](#0-how-to-use-this-document)
1. [Repository Reality Check](#1-repository-reality-check)
2. [Deep Analysis of the SRS](#2-deep-analysis-of-the-srs)
3. [Architecture Decisions (ADR Summary)](#3-architecture-decisions-adr-summary)
4. [Backend Engineering Standard](#4-backend-engineering-standard)
5. [Admin Panel Engineering Standard](#5-admin-panel-engineering-standard)
6. [Flutter Engineering Standard](#6-flutter-engineering-standard)
7. [Domain Map: Enums, State Machines, Money, Events](#7-domain-map-enums-state-machines-money-events)
8. [Phase Roadmap](#8-phase-roadmap)
9. [Session Preamble (paste on top of every prompt)](#9-session-preamble-paste-on-top-of-every-prompt)
10. [Phase Prompts](#10-phase-prompts)
11. [Quality Gates & Definition of Done](#11-quality-gates--definition-of-done)
12. [Appendices](#12-appendices)

---

## 0. How to Use This Document

1. Run the prompts in **§10 in order**. Each prompt is one **milestone**, sized for one focused Claude Code session (a few need two).
2. Start **every** session by pasting the **Session Preamble (§9)**, then the milestone prompt.
3. Do not start a milestone until its **"Depends on"** milestones are marked ✅ in `docs/IMPLEMENTATION_STATUS.md`.
4. After each milestone, check the **Quality Gate (§11)** yourself: tests green, Pint/PHPStan clean, `flutter analyze` clean, status doc updated.
5. If a session runs out of room, start a new one with the preamble plus:
   `Continue milestone Px.y. Read docs/IMPLEMENTATION_STATUS.md for what is done and finish the remaining items.`
6. The standards in **§4–§7 are binding**. Prompts refer to them (for example "follow the Admin Module Contract §5.6") so they stay short.

---

## 1. Repository Reality Check

Inspected on 2026-10-13:

| Item | Finding | Consequence |
|---|---|---|
| Monorepo root `/var/www/delivery_platform` | Not a git repository | P0.1 runs `git init`, adds root `.gitignore`, and makes an initial baseline commit |
| `backend/` | Fresh Laravel **13.17** skeleton, PHP ^8.3, PHPUnit 12, Pint, Tailwind **4** + Vite **8** already in `package.json`. Only `User` model and default migrations exist | Greenfield. Keep Laravel conventions and add structure |
| `backend/AGENTS.md` / `CLAUDE.md` | Asks for **Laravel Boost** to be installed | P0.1 installs `laravel/boost --dev` and re-reads the generated guidelines |
| `customer_app/`, `driver_app/` | Default Flutter counter apps; Dart SDK ^3.13.1; no packages | Greenfield. Shared package `packages/platform_core` is created |
| Toolchain | PHP 8.3.35 (bcmath, intl, gd, imagick, redis, sodium, pdo_mysql), Composer 2.9, Node 20.20, MySQL **8.0.46**, Redis **7.0.15**, Flutter **3.47.1** / Dart **3.13.1** | MySQL 8 + Redis 7 are the chosen data stores (see ADR) |
| Default DB | SQLite in `.env.example` | Switch to MySQL. Tests use a dedicated MySQL test DB, because row locks, JSON, and spatial behaviour must match production |

---

## 2. Deep Analysis of the SRS

### 2.1 What the product really is

A **two-sided, real-time negotiation marketplace**, not a taxi app with a price field. The hard engineering does not live in the screens. It lives in five server-side cores:

1. **Negotiation core.** Booking request, provider offers and versions, customer selection. The selection must be atomic, with expiry and invalidation.
2. **Dispatch core.** Eligibility (approval + documents + vehicle + capability + area + freshness + no conflicting job + debt limit), geo search, radius waves, staged privacy disclosure.
3. **Lifecycle core.** An explicit booking state machine with guards (PIN, proofs, actor ownership), plus separate payment, dispute, and refund status axes.
4. **Money core.** Immutable double-entry ledger, commission/coupon/tip/cash flows, wallets, payouts with reservations and dual control.
5. **Configuration core.** Data-driven services, versioned form schemas and policies, **snapshotted onto every booking** so history never changes.

Admin panel, website, and Flutter apps are clients of these cores. **If a core is wrong, every client is wrong**, so cores are built first, tested hardest, and never duplicated in clients.

### 2.2 Invariants that must never break (test each one)

| # | Invariant | Mechanism |
|---|---|---|
| I1 | A booking has at most one selected provider | `SELECT … FOR UPDATE` on booking + conditional update `WHERE selected_offer_id IS NULL AND status IN (searching_offers, offers_received)`; unique index on active assignment |
| I2 | An expired, withdrawn, or invalidated offer can never be accepted | Server checks `expires_at > now()` and status inside the lock; scheduled expiry is a backstop, not the guard |
| I3 | Offer amount, currency, and scope are immutable after submission | `booking_offer_versions` append-only; an offer row only points to its current version |
| I4 | Booking history and receipts never change when admin edits config | `service_snapshot`, `policy_snapshot`, `commission_snapshot`, localized name snapshots stored on booking |
| I5 | Every journal entry balances (Σdebit = Σcredit) and is never edited | `LedgerService::post()` asserts balance; no update/delete paths; reversals are new entries |
| I6 | Wallet balance = sum of ledger lines (reconcilable) | Cached balance updated in the same transaction under row lock; nightly `ledger:reconcile` |
| I7 | Coupon global, per-user, and budget caps hold under concurrency | Reservation rows inside a transaction with a coupon row lock; counters checked in the lock |
| I8 | A payment is `paid` only from a verified webhook/query or a cash-confirmation rule | Gateway adapter `verifyWebhook()` + `gateway_webhook_events` unique `(gateway, event_id)` |
| I9 | Replayed critical requests do not double-apply | `Idempotency-Key` middleware + idempotency store on create booking, offers, accept, transitions, payments, payouts |
| I10 | Providers see only what their stage allows | Dedicated API Resources per disclosure stage (`ProviderRequestCardResource` vs `ProviderAssignedBookingResource`) |
| I11 | Only eligible providers go online or receive requests | Single `ProviderEligibilityService` returning typed reasons, used by online toggle, dispatch, and offer submission |
| I12 | No client-supplied total is trusted | Request DTOs never carry computed totals; `PriceBreakdownCalculator` is the only producer |

### 2.3 Hardest problems and the chosen solutions

| Problem | Solution |
|---|---|
| **Race: two customers' taps / two devices accept different offers** | Pessimistic lock on booking row + idempotency key + conditional update. The loser receives `409 booking_already_assigned` with a fresh booking payload. A Process-pool race script proves it (P2.9). |
| **Offer and search expiry while apps are closed** | `expires_at` columns + delayed queue job per booking/offer (`ExpireOffer`, `ExpireBookingSearch`) **and** a scheduler sweep every 5s (`offers:expire`, `bookings:expire-searches`), both idempotent. |
| **Fast geo search for online drivers** | Redis GEO set per city (`GEOADD providers:online:{city}`) for live positions + `GEOSEARCH … BYRADIUS`. MySQL `provider_locations` (POINT SRID 4326, spatial index) for persistence/history. Abstracted behind `ProviderGeoIndex` (Redis impl + Database impl for tests/local). |
| **Dispatch waves without spamming** | `DispatchBookingWave(booking, wave)` job: radius progression from category policy (e.g. 2 → 4 → 7 km), batch size, records each recipient in `booking_dispatch_recipients`. Providers only see requests they were dispatched. |
| **Data-driven service forms across Laravel and two Flutter apps** | `FormFieldType` enum + versioned `service_form_schemas`. Backend `ServiceFormValidator` compiles schema → Laravel rules. Flutter `DynamicFormRenderer` renders the same schema. No `if (service == 'freight')` scattered in clients. |
| **Staged disclosure (privacy)** | Pickup area shown as rounded coordinates/district before selection. Full address, name, and chat after selection. Phone relay/masking optional via adapter. |
| **Cash jobs bypassing commission** | Cash completion posts "commission due" against the provider wallet (may go negative up to a configurable credit limit). Exceeding the limit is an eligibility reason that blocks going online. |
| **Coupon funding vs provider pay** | Commission and provider gross are calculated on the **agreed fare**. Platform-funded discounts are posted to promo expense. Provider-funded discounts reduce provider earnings explicitly. Both appear in the breakdown. |
| **Admin edits colliding** | Optimistic locking (`lock_version`) on configuration entities (catalog, policies, coupons, settings): stale submit returns `409`. |
| **Arabic-first everywhere** | `ar` default in Laravel and Flutter. Tailwind logical utilities (`ms-*`, `pe-*`, `start-*`). Flutter `Directionality` from locale. Translatable JSON columns `{ar,en}`. |

### 2.4 Risk register

| Risk | Impact | Mitigation |
|---|---|---|
| Package not yet supporting Laravel 13 (Yajra, Spatie, etc.) | Blocks admin | P0.2 verifies on Packagist. Never `--ignore-platform-reqs`. If unsupported, record in DECISIONS and pin to the newest compatible tag or a maintained fork, with a note |
| Scope explosion (40+ modules) | Half-built product | Strict vertical slices, P0 before P1 before P2, "no dead buttons" rule, status doc after every milestone |
| External credentials missing (SMS, maps, payments, FCM) | Fake success temptation | Adapter + `log`/`fake` drivers allowed only in `local`/`testing` (guarded at boot). Health page shows "not configured" |
| Location battery and OS background limits | Driver app rejection / drain | Foreground service on Android with disclosed notification; throttle by state (idle / approaching / active) |
| Concurrency bugs only appear in production | Double assignment / double payout | Locks + idempotency + race tests on MySQL (not SQLite) |
| RTL regressions | Unusable Arabic UI | RTL golden/widget tests; admin uses logical utilities only (lint rule: no `ml-`/`mr-`/`pl-`/`pr-`/`left-`/`right-` in admin Blade) |

---

## 3. Architecture Decisions (ADR Summary)

Record each of these in `docs/DECISIONS.md` (P0.1) with date, context, decision, and consequences.

| ID | Decision |
|---|---|
| ADR-01 | **Modular monolith** in a single Laravel 13 app: REST API (`/api/v1`), Admin panel (`/admin`, configurable via `ADMIN_PATH`), Public website (`/` with `/{locale}` prefix). Separate route files, middleware groups, and layouts. |
| ADR-02 | **Layer-first structure with module sub-namespaces:** `Enums/`, `DTOs/`, `Repositories/{Contracts,Eloquent}/`, `Services/`, `DataTables/`, `Http/…` each with `<Module>/` subfolders (e.g. `Services/Booking/OfferSelectionService.php`). This makes the user-mandated Services / Repositories / DTOs / Enums layering explicit and lets the generator command scaffold it. |
| ADR-03 | **Database: MySQL 8.0** (InnoDB, utf8mb4_unicode_ci, spatial POINT SRID 4326). **Redis 7** for cache, queues, locks, rate limits, GEO index. Tests run on MySQL DB `delivery_platform_testing`. |
| ADR-04 | **Money:** integer minor units (`bigint *_minor`) + `char(3) currency`. `App\Support\Money\Money` value object + `MoneyCast`. Currency exponent from `currencies` table (EGP = 2). Rounding: half-up at the final display step only; policy configurable per currency. |
| ADR-05 | **Mobile auth:** phone OTP → **Laravel Sanctum** personal access tokens with abilities (`customer`, `provider`). One `users` table; customer/provider profiles are separate tables. Device registry for push tokens. |
| ADR-06 | **Admin auth:** separate `admins` table + `admin` session guard, TOTP MFA (enforceable per role), login throttling, session list/revoke. Permissions via **spatie/laravel-permission** (guard `admin`), permission names come from the `AdminPermission` enum only. |
| ADR-07 | **Realtime:** Laravel **Reverb** (Pusher protocol), private channels authorized in `routes/channels.php` via policies. Clients: `dart_pusher_channels` (Flutter), Laravel Echo (admin live ops). Events are persisted (`booking_status_events`, `outbox_events`) and carry a per-booking `sequence` number so clients detect gaps. |
| ADR-08 | **Admin UI:** Blade components + **Tailwind CSS 4** + **Alpine.js** (component state) + **axios** (AJAX) + **Laravel Precognition** (server validation before submit) + **Yajra DataTables** (server-side listings, DataTables 2.x with jQuery as its required dependency only) + Tom Select (searchable/remote selects) + flatpickr (dates, Arabic locale) + Leaflet/leaflet-draw (zones, live map) + Chart.js (dashboards). No SPA framework. |
| ADR-09 | **Translatable content:** `spatie/laravel-translatable` JSON columns `{ "ar": "...", "en": "..." }`. Arabic required, English optional with fallback to Arabic. |
| ADR-10 | **Settings:** typed `SettingKey` enum (type, default, rules, group, sensitive flag) + `platform_settings` table + cached `SettingsService`. Secrets stay in `.env`, never in DB or admin UI. |
| ADR-11 | **Files:** private disk (`private`, local in dev, S3-compatible in prod) for documents/proofs/attachments; access only via short-lived signed routes after policy check (each access to sensitive files is audited). Public disk for CMS images. |
| ADR-12 | **Integrations via contracts** in `app/Integrations/<Area>/Contracts`: `SmsGateway`, `MapsProvider` (geocode, reverse, route/ETA), `PaymentGateway`, `PushNotifier`, `PhoneRelay`, `FileScanner`. Drivers: real + `log`/`fake` (fake only allowed when `app()->environment(['local','testing'])`, enforced in the service provider). |
| ADR-13 | **Flutter:** monorepo package `packages/platform_core` (API client, envelope, errors, Money, enums, design tokens, shared widgets, realtime, location abstraction). State: **Riverpod** (with generator). Routing: **go_router**. HTTP: **dio**. Models: **freezed + json_serializable**. Maps: **flutter_map** (tile URL configurable; Google/Mapbox tiles later). l10n: ARB + `flutter gen-l10n`, `ar` default. |
| ADR-14 | **API envelope:** `{ success, message, data, meta, errors }` for API **and** admin AJAX responses, produced only by `App\Support\Http\ApiResponse`. Stable machine `code` in `errors._code` for domain errors (e.g. `offer_expired`). |
| ADR-15 | **Static analysis:** Larastan (level 6 to start, raise to 8 by P6), Pint (Laravel preset), `flutter analyze` with `very_good_analysis` or strict `flutter_lints`. |
| ADR-16 | **State machines in enums + one transition service:** `BookingStatus::allowedTransitions()` declares the graph; `BookingTransitionService` enforces guards, writes `booking_status_events`, broadcasts. Nothing else may change `bookings.status`. |
| ADR-17 | **Coupon usage:** *reserved* at booking creation (expires with search window), *released* on cancel/expiry, *committed* on completion/payment. |
| ADR-18 | **Commission base:** agreed fare (before customer discount) + accepted add-ons; tips commission-free by default (configurable). |

---

## 4. Backend Engineering Standard

### 4.1 Directory layout (backend/)

```text
app/
  Console/Commands/                 # bookings:expire-searches, offers:expire, ledger:reconcile, make:admin-module …
  DataTables/Admin/<Module>/        # Yajra DataTable classes (extend AdminDataTable)
  DTOs/<Module>/                    # readonly data objects (input + filter + output)
  Enums/<Module>/                   # backed enums (statuses, types, permissions, setting keys)
  Events/<Module>/  Listeners/<Module>/  Jobs/<Module>/  Notifications/<Module>/
  Exceptions/                       # DomainException base, BusinessRuleViolation, ConflictException …
  Http/
    Controllers/Admin/<Module>/     # thin; session + AJAX JSON
    Controllers/Api/V1/{Auth,Customer,Provider,Common,Webhooks}/
    Controllers/Website/
    Middleware/                     # SetLocale, CorrelationId, Idempotency, EnsureAccountActive, EnsureProviderEligible …
    Requests/Admin/<Module>/  Requests/Api/V1/<Module>/
    Resources/Api/V1/<Module>/
  Integrations/<Area>/{Contracts,Drivers}/
  Models/                           # Eloquent models (casts, relations, scopes only)
  Policies/
  Providers/                        # RepositoryServiceProvider, IntegrationServiceProvider …
  Repositories/
    Contracts/<Module>/             # interfaces
    Eloquent/<Module>/              # implementations
  Services/<Module>/                # business logic, transactions, events, audit
  Support/                          # Money, Http/ApiResponse, Idempotency, Geo, Enums traits, Audit
  View/Components/Admin/            # class-based Blade components (where logic is needed)
resources/
  views/admin/{layouts,components,<module>}/   views/website/   views/components/admin/…
  js/admin/{app.js, core/*, components/*}      js/website/
  css/admin.css  css/website.css
  lang/{ar,en}/{admin,enums,validation,api,website,…}.php
routes/ api_v1.php  admin.php  website.php  channels.php  console.php
tests/ Unit/  Feature/{Api,Admin,Website}/  Concurrency/
```

### 4.2 Layer responsibilities (enforced in code review)

| Layer | Does | Never does |
|---|---|---|
| **Controller** | Authorize (policy/FormRequest), build DTO, call one service method, return Resource / `ApiResponse` / view | Queries, business rules, transactions, `Model::create` |
| **FormRequest** | Authorization + input validation + `toDto()` helper | Business decisions that need locks/state (services do those) |
| **DTO** | Typed, immutable transport between layers (`final readonly class`) | Logic beyond trivial normalisation |
| **Service** | Business rules, orchestration, `DB::transaction`, locks, events, audit, calling integrations | Building HTTP responses, reading `request()` |
| **Repository** | All Eloquent queries (find, filters, datatable query, locking reads, aggregates) | Business rules, transactions orchestration, events |
| **Model** | Casts (enums, Money, translatable), relations, local scopes | Business logic, HTTP |
| **Enum** | Allowed values, `label()`, `color()`, `options()`, transitions/meta | DB access |
| **Policy** | Object-level authorization (ownership + permission) | Business validation |
| **Resource** | Output shape per audience/disclosure stage | Queries in loops (use eager loading from the repository) |

### 4.3 Reference skeletons (copy these patterns)

**Enum**

```php
namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;      // values(), options(), labels(), fromLabel()
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum BookingStatus: string implements HasLabel, HasColor
{
    use EnumHelpers;

    case Draft = 'draft';
    case SearchingOffers = 'searching_offers';
    case OffersReceived = 'offers_received';
    case ProviderSelected = 'provider_selected';
    // … see §7.2

    public function label(): string
    {
        return __("enums.booking_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::SearchingOffers, self::OffersReceived => BadgeColor::Info,
            self::Completed => BadgeColor::Success,
            self::CancelledByCustomer, self::CancelledByProvider, self::CancelledByOperations => BadgeColor::Danger,
            default => BadgeColor::Neutral,
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array { /* see §7.2 */ }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool { /* … */ }
}
```

**DTO (input + filter)**

```php
namespace App\DTOs\Coupon;

final readonly class CouponData
{
    public function __construct(
        public string $code,
        public array $title,                 // ['ar' => '…', 'en' => '…']
        public DiscountType $discountType,
        public int $value,                   // minor units or basis points (percentage) — documented per type
        public ?Money $maxDiscount,
        public ?Money $minFare,
        public FundingSource $fundingSource,
        public CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt,
        /** @var list<int> */ public array $serviceCategoryIds,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreCouponRequest|UpdateCouponRequest $r): self { /* map validated() */ }
}

final readonly class CouponFilterData
{
    public function __construct(
        public ?string $code = null,
        /** @var list<CouponStatus> */ public array $statuses = [],
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $r): self { /* read $r->input('filters.*'), cast to enums, ignore unknown keys */ }

    public function activeCount(): int { /* used by the filters badge */ }
}
```

**Repository**

```php
interface CouponRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCodeForUpdate(string $code): ?Coupon;
    public function datatableQuery(CouponFilterData $filters): Builder;
}

final class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    public function __construct(Coupon $model) { parent::__construct($model); }

    public function findByCodeForUpdate(string $code): ?Coupon
    {
        return $this->query()->where('code', $code)->lockForUpdate()->first();
    }

    public function datatableQuery(CouponFilterData $f): Builder
    {
        return $this->query()
            ->select(['id','code','title','discount_type','status','starts_at','ends_at','used_count'])
            ->when($f->code, fn ($q, $v) => $q->where('code', 'like', "%{$v}%"))
            ->when($f->statuses, fn ($q, $v) => $q->whereIn('status', array_map(fn ($s) => $s->value, $v)))
            ->when($f->from, fn ($q, $v) => $q->where('starts_at', '>=', $v))
            ->when($f->to, fn ($q, $v) => $q->where('starts_at', '<=', $v));
    }
}
```

`BaseRepository` provides: `query()`, `find()`, `findOrFail()`, `findByUuid()`, `create()`, `update(Model, array)`, `delete()`, `lockById()`, `paginate()`. Bindings live in `RepositoryServiceProvider::$bindings`.

**Service**

```php
final class CouponService
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(CouponData $data, Admin $actor): Coupon
    {
        return $this->db->transaction(function () use ($data, $actor) {
            $coupon = $this->coupons->create($this->attributes($data) + ['status' => CouponStatus::Draft]);
            $this->audit->record(AuditAction::Created, $coupon, actor: $actor, after: $coupon->auditSnapshot());
            CouponCreated::dispatch($coupon);
            return $coupon;
        });
    }
}
```

**Controller (admin, AJAX + Precognition-ready)**

```php
final class CouponController extends AdminController
{
    public function __construct(private readonly CouponService $service) {}

    public function index(CouponsDataTable $table) { return $table->render('admin.coupons.index'); }

    public function store(StoreCouponRequest $request)   // Precognition stops here after validation (204)
    {
        $coupon = $this->service->create(CouponData::fromRequest($request), $request->user('admin'));

        return ApiResponse::success(__('admin.coupons.created'), [
            'redirect' => route('admin.coupons.show', $coupon),
        ]);
    }
}
```

**Domain errors.** Services throw `BusinessRuleViolation::make('offer_expired', field: 'offer_id')` or `ConflictException`. The exception handler maps them to `422`/`409` in the envelope, with field errors where a field applies, so the admin form and the Flutter apps display them like validation errors.

### 4.4 Cross-cutting backend rules

- **Idempotency:** middleware `idempotent` reads `Idempotency-Key` (UUID). It stores `(actor, route, key) → status + body` for 24h and replays the stored response. A key reused with a different payload hash returns `422 idempotency_key_reused`.
- **Correlation ID:** `X-Request-Id` accepted or generated, added to log context and error envelopes.
- **Locale:** `SetLocale` middleware reads, in order: user preference → `Accept-Language` → `ar`. Validation messages exist in `lang/ar/validation.php`.
- **Audit:** `AuditLogger::record(AuditAction $action, Model|string $target, ?Authenticatable $actor, ?array $before, ?array $after, ?string $reason, array $meta = [])`. Models expose `auditSnapshot()` with secrets/PII excluded.
- **Pagination:** API uses cursor or length-aware pagination with `meta.pagination`. Sorting and filters use allowlists.
- **Rate limits:** named limiters in `AppServiceProvider` (`otp-request`, `otp-verify`, `location`, `offers`, `public-forms`).
- **Queues:** Redis, named queues `critical` (expiry, dispatch), `default`, `notifications`, `low`. Horizon is optional; if added, it is admin-gated.
- **Scheduler:** `routes/console.php`, every job idempotent, plus a heartbeat writer for the health page.
- **Timestamps:** stored UTC; displayed in city/market timezone.

---

## 5. Admin Panel Engineering Standard

These are the user's hard requirements: **Tailwind CSS · AJAX · server validation call before submit (show errors, block submit; fire the submit event only when clean) · everything as components · Yajra DataTables · every listing has a filters card collapsed by default · Services/Repositories/DTOs/Enums.**

### 5.1 Front-end stack and build

- `resources/css/admin.css`: Tailwind 4 (`@import "tailwindcss";`) with `@theme` design tokens (brand colours, radius, font), Arabic font self-hosted (OFL licence, e.g. *IBM Plex Sans Arabic* or *Cairo*), DataTables Tailwind skin, Tom Select/flatpickr skin.
- `resources/js/admin/app.js`: imports Alpine, axios instance (CSRF, `Accept: application/json`, `X-Requested-With`, `X-Request-Id`, locale header), jQuery + DataTables 2 (+ Responsive), and registers the `core/*` modules.
- Separate Vite entries: `admin` and `website`. They share no CSS.
- **RTL:** `<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">`. **Only logical utilities** (`ms-*`, `me-*`, `ps-*`, `pe-*`, `start-*`, `end-*`, `text-start`, `rtl:`/`ltr:` variants where needed). A test greps admin views for forbidden physical utilities (`\b(ml|mr|pl|pr|left|right)-`).

### 5.2 Blade component library (`resources/views/components/admin/…`)

| Group | Components |
|---|---|
| Layout | `layouts.app`, `layouts.auth`, `sidebar` (permission-aware nav from `config/admin_menu.php`), `topbar` (locale switch, profile, notifications), `page-header` (title, breadcrumbs, actions slot), `card`, `tabs`, `section`, `grid` |
| Feedback | `alert`, `badge` (takes any `HasLabel&HasColor` enum), `toast-stack`, `empty-state`, `skeleton`, `spinner`, `stat-tile` |
| Actions | `button` (variants, icon, loading state, `href`/`submit`), `icon-button`, `dropdown`, `confirm-action` (opens confirm modal, optional **mandatory reason** textarea, POSTs via AJAX, reloads table) |
| Overlays | `modal` (Alpine, focus trap, ESC; content can be loaded via AJAX), `drawer` (side panel for quick view) |
| Forms | `form` (the AJAX/Precognition form, §5.3), `form.input`, `form.textarea`, `form.select`, `form.enum-select`, `form.remote-select` (Tom Select + `/admin/lookups/{lookup}`), `form.checkbox`, `form.toggle`, `form.radio-group`, `form.date`, `form.datetime`, `form.date-range`, `form.money` (minor-unit aware), `form.translatable-input` / `form.translatable-textarea` (ar/en tabs, ar required), `form.file` (preview, size/MIME hints), `form.map-point`, `form.polygon` (Leaflet draw), `form.key-value`, `form.repeater` (Alpine; used by form-schema builder), `form.field-error`, `form.actions` |
| Tables | `datatable` (wraps Yajra HTML builder), `filters-card`, `filters.text`, `filters.select`, `filters.enum`, `filters.remote-select`, `filters.date-range`, `filters.number-range`, `filters.boolean`, `table.actions` (permission-aware row buttons: view/edit/delete/custom) |
| Detail | `detail-list` (label/value), `timeline` (status events), `money`, `user-chip`, `map-preview`, `json-viewer` (snapshots, sanitized) |

Every component: no hard-coded text (labels come in as props or from `lang/*/admin.php`), accessible (labels, `aria-*`, focus states), works in RTL and LTR.

### 5.3 AJAX form protocol: "validate before submit"

**Server.** All admin write routes use the `HandlePrecognitiveRequests` middleware (in the `admin` route group). Controllers type-hint FormRequests. A precognitive request runs route-model binding, `authorize()`, and `rules()`, then returns **204** with no controller execution, or **422** with errors.

**Client** (`resources/js/admin/core/ajax-form.js`, auto-bound to every `<form data-ajax-form>` that `<x-admin.form>` renders):

```text
submit (user) ──► preventDefault ──► if busy: ignore
   │
   ├─1─ clear previous errors
   ├─2─ client checks: form.checkValidity() (required, maxlength, type, min/max)
   │        └─ invalid → render inline errors, focus first, STOP
   ├─3─ server validation call: same URL + method, FormData, header `Precognition: true`
   │        ├─ 422 → render field errors under each field (dot → bracket name mapping,
   │        │         array fields like items.0.name supported), error summary toast,
   │        │         focus + scroll to first error, STOP (submit is prevented)
   │        ├─ 403/419/5xx → toast (419: "session expired" + reload), STOP
   │        └─ 204 → valid
   ├─4─ dispatch CustomEvent('ajax-form:validated', {cancelable:true})
   │        (hooks: confirm dialog / reason prompt; calling preventDefault cancels)
   └─5─ fire CustomEvent('ajax-form:submit') → handler performs the real AJAX submit
            button → loading + disabled (double-submit blocked)
            ├─ 200 {success:true, message, data:{redirect|reload_table|close_modal|reset|replace_html}}
            │        → toast success → apply instructions
            ├─ 422 (service-level rule, e.g. code taken under lock) → same error rendering
            ├─ 409 (stale lock_version / conflict) → toast + offer reload
            └─ other → toast with correlation id
```

Extras:
- **Live field validation:** on `change`/`blur` (debounced 400 ms), send `Precognition-Validate-Only: <field>` to validate just that field. Opt-in per form via `live-validation` prop, default **on**.
- **Files:** sent in both the precognitive and the real request, so MIME/size errors appear before submit.
- **Preserve input:** nothing is reset on error. `reset` happens only when the server asks for it.
- **Modals:** `<x-admin.form>` inside `<x-admin.modal>` closes on `close_modal` and reloads the referenced table.

Blade usage:

```blade
<x-admin.form :action="route('admin.coupons.store')" method="POST">
    <x-admin.form.section :title="__('admin.coupons.sections.basic')">
        <x-admin.form.input name="code" :label="__('admin.coupons.fields.code')" required maxlength="32" />
        <x-admin.form.translatable-input name="title" :label="__('admin.coupons.fields.title')" required />
        <x-admin.form.enum-select name="discount_type" :enum="\App\Enums\Coupon\DiscountType::class" required />
        <x-admin.form.money name="max_discount" currency="EGP" />
        <x-admin.form.remote-select name="service_category_ids[]" lookup="service-categories" multiple />
    </x-admin.form.section>
    <x-admin.form.actions :cancel="route('admin.coupons.index')" />
</x-admin.form>
```

### 5.4 Listings: Yajra DataTables + collapsed filters card

**`App\DataTables\Admin\AdminDataTable`** (abstract, extends Yajra `DataTable`) supplies:
- server-side processing, responsive, `pageLength` 25, localized language file (ar/en), Tailwind DOM layout;
- `ajax.data` hook that merges the linked filters form into `d.filters`;
- helpers `enumBadgeColumn()`, `moneyColumn()`, `dateColumn()` (market timezone), `userColumn()`, `actionsColumn()` (renders `<x-admin.table.actions>` with policy checks), `boolColumn()`;
- an **orderable/searchable allowlist** per column. Global search is disabled where columns are encrypted/PII;
- an **export hook**: CSV export is a queued job reusing the repository query. It requires the module's `*.export`/`reports.export` permission and writes an audit entry. The download is delivered through a signed link in the admin notifications.

Each listing's `query()` is always:

```php
public function query(): Builder
{
    return $this->repository->datatableQuery(CouponFilterData::fromRequest($this->request()));
}
```

**`<x-admin.filters-card for="coupons-table">`**
- **Collapsed by default** (Alpine `open: false`). The header shows the "Filters" title, an **active-filter count badge**, and a chevron. The collapsed state stays collapsed even when filters are active; the badge shows that filters apply.
- The body is a responsive grid of `filters.*` components. **Apply** (Enter key also applies) and **Reset**.
- Apply → `AdminTables.reload('coupons-table')` and syncs `?filters[...]` to the URL (shareable, survives refresh). Reset clears the inputs, URL, and table.
- On page load, the filter form pre-fills from the query string (the server also reads it, so the first draw is filtered).

Index view (every listing looks like this):

```blade
<x-admin.layouts.app :title="__('admin.coupons.title')">
    <x-admin.page-header :title="__('admin.coupons.title')" :breadcrumbs="[…]">
        @can(\App\Enums\Admin\AdminPermission::CouponsManage->value)
            <x-admin.button :href="route('admin.coupons.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="coupons-table">
        <x-admin.filters.text name="code" :label="__('admin.coupons.fields.code')" />
        <x-admin.filters.enum name="statuses" :enum="\App\Enums\Coupon\CouponStatus::class" multiple />
        <x-admin.filters.date-range name="starts_at" :label="__('admin.coupons.fields.starts_at')" />
    </x-admin.filters-card>

    <x-admin.card>
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
```

### 5.5 Generator: `php artisan make:admin-module`

Built in P0.3 so all ~40 admin modules come out identical:

```text
php artisan make:admin-module Coupon --module=Marketing --translatable=title --enum-status=CouponStatus
```

It generates, each from a stub in `stubs/admin-module/`: Model + migration + factory, Enum(s), `CouponData`, `CouponFilterData`, Repository interface + Eloquent impl (+ binding), Service, Policy, `Store/UpdateCouponRequest`, Controller, `CouponsDataTable`, views (`index`, `create`, `edit`, `show`, `_form`), routes snippet, menu entry, `lang/{ar,en}/admin.php` keys, and a Feature test skeleton. Generated code must pass Pint/PHPStan untouched.

### 5.6 Admin Module Contract (checklist used by every admin prompt)

For each admin module, deliver all of these:

1. **Permissions** in the `AdminPermission` enum + role seeder mapping; route middleware `can:` + Policy methods.
2. **Repository** (interface + Eloquent) with `datatableQuery(FilterDTO)`; **Service** with all writes in transactions + audit; **DTOs** for input and filters; **Enums** for every status/type.
3. **Listing:** `XxxDataTable` + `<x-admin.filters-card>` (collapsed by default, see Appendix C for each listing's filters) + permission-aware row actions + optional export (permissioned + audited).
4. **Forms:** `<x-admin.form>` (AJAX + Precognition) with FormRequests; translatable fields use the ar/en component; `lock_version` on configuration entities.
5. **Dangerous actions** (suspend, reject, cancel, refund, adjust, override, delete, publish): `<x-admin.confirm-action>` with a **mandatory reason** where the SRS requires one, dual approval where configured, audit entry with before/after.
6. **Show page:** detail list + related tabs (each tab lazy-loads its own DataTable via AJAX) + timeline where relevant.
7. **i18n:** all strings in `lang/ar` and `lang/en`; Arabic default; RTL verified.
8. **Tests** (Feature): guest redirected; admin without permission gets 403 (page + JSON + datatable endpoint); precognitive request returns 422 for invalid input and 204 for valid input without persisting; store/update success writes an audit row; datatable JSON respects each filter; dangerous action without reason is rejected.

---

## 6. Flutter Engineering Standard

### 6.1 Repo layout

```text
packages/platform_core/lib/
  api/        # ApiClient (dio), envelope parsing, ApiException(code, fieldErrors), interceptors: auth, locale, idempotency, request-id, retry(GET only)
  auth/       # TokenStore (flutter_secure_storage), session state
  config/     # Env (--dart-define: API_BASE_URL, REVERB_*, MAP_TILE_URL, FLAVOR)
  enums/      # mirrors backend enums, each with `unknown` fallback for forward compatibility
  money/      # Money (minor units + currency exponent), MoneyFormatter (locale digits pref)
  realtime/   # RealtimeClient (dart_pusher_channels) + private channel auth + sequence-gap detection → refetch
  location/   # LocationService abstraction (geolocator), permission rationale flow
  forms/      # DynamicFormRenderer for service form schemas (FormFieldType)
  ui/         # design tokens, theme (light), components: AppButton, AppTextField, PhoneField, OtpField, MoneyText,
              # StatusChip, Timeline, OfferCard base, EmptyState, ErrorState(retry), Skeleton, ConfirmSheet, AttachmentPicker, RatingStars
  l10n/       # shared ARB fragments + helpers
customer_app/lib/  driver_app/lib/
  app/        # bootstrap.dart, router.dart (go_router + auth redirect), theme.dart, l10n/
  core/       # app-specific providers, deep links, push handler
  features/<feature>/
    data/       datasources/ (remote API calls)  dtos/ (freezed+json)  repositories/ (impl)
    domain/     entities/  repositories/ (abstract contracts)
    presentation/ providers/ (Riverpod notifiers)  screens/  widgets/
```

### 6.2 Rules

- Widgets never call dio. Screen → Notifier (Riverpod) → Repository (contract) → DataSource → ApiClient.
- Every async UI handles **loading / data / empty / error(retry) / offline / session-expired**.
- No user-visible string literal in widgets. All text comes from ARB `ar` (template) + `en`.
- Root `MaterialApp.router` with `locale` from persisted preference (default `ar`); `Directionality` follows automatically. Use `EdgeInsetsDirectional`, `AlignmentDirectional`, and `PositionedDirectional` only.
- Critical POSTs send an `Idempotency-Key` generated once per user intent (kept across retries).
- Enums are parsed with an `unknown` fallback; the UI degrades gracefully on unknown values.
- Realtime events are hints. On each event or reconnect, refetch the booking snapshot if `sequence` has a gap.
- Tests: unit (repositories with mocked ApiClient), widget (RTL + LTR, states), golden for key cards, `integration_test` for the core flows against a local backend with test OTP.

---

## 7. Domain Map: Enums, State Machines, Money, Events

### 7.1 Enum catalog (backend `App\Enums\…`, mirrored in `platform_core/enums`)

| Module | Enums |
|---|---|
| Common | `Locale`, `BadgeColor`, `AuditAction`, `ActorType` (customer/provider/admin/system), `Platform` (android/ios/web) |
| Admin | `AdminPermission` (**all permission strings**, Appendix A), `AdminRole`, `AdminStatus` |
| Settings | `SettingKey` (with `type()`, `default()`, `rules()`, `group()`, `isSensitive()`), `SettingGroup` |
| Identity | `UserStatus` (active, restricted, suspended, verification_required, deletion_pending, deleted), `OtpPurpose`, `ConsentType`, `DeletionRequestStatus` |
| Provider | `ProviderType` (individual_driver, courier, moving_crew, commercial_transport, company_fleet), `ProviderStatus` (§7.3), `EligibilityReason`, `DocumentStatus` (pending, approved, rejected, expired), `VehicleStatus` (draft, under_review, approved, rejected, suspended, inactive) |
| Catalog | `ServiceFamilyKind` (passenger_city, intercity, courier, moving, freight, hourly, other), `BookingType` (on_demand, scheduled, intercity, parcel, freight, hourly), `FormFieldType` (text, textarea, integer, decimal, boolean, select, multiselect, date, time, datetime, contact, photo, item_checklist, dimensions, weight), `ProofType` (photo, signature, recipient_otp, pickup_pin, checklist, weight) |
| Geo | `ZoneStatus`, `LocationSource` (gps, network, manual) |
| Booking | `BookingStatus` (§7.2), `OfferStatus` (§7.2), `OfferKind` (accept_customer_fare, counter_offer), `CancellationActor`, `CancellationReason`, `AdjustmentStatus`, `DeliveryAttemptOutcome` |
| Payments | `PaymentMethodType` (cash, card, mobile_wallet, platform_wallet), `PaymentStatus` (not_required, pending, requires_action, authorized, paid, failed, partially_refunded, refunded, cash_due, cash_collected, reconciliation_required), `RefundStatus` |
| Ledger | `LedgerAccountType` (customer_wallet, provider_wallet, platform_revenue, promo_expense, gateway_clearing, tax_payable, cash_commission_receivable, payout_clearing, refunds), `JournalEntryType` (booking_settlement, tip, refund, payout, adjustment, reversal, incentive, cancellation_fee), `EntrySide` (debit, credit) |
| Payouts | `PayoutStatus` (requested, under_review, approved, processing, paid, failed, rejected, cancelled, reversed), `PayoutMethodType`, `ApprovalStatus` |
| Coupons | `CouponStatus` (draft, scheduled, active, paused, exhausted, expired, archived), `DiscountType` (fixed, percentage), `FundingSource` (platform, provider, partner, split), `CouponReservationStatus` (reserved, released, committed, reversed), `CouponAttemptResult` |
| Notifications | `NotificationChannel` (push, sms, email, in_app), `NotificationCategory`, `DeliveryStatus` |
| Support | `TicketCategory` (SRS §14.3 list), `TicketStatus` (open, pending_customer, pending_internal, escalated, resolved, closed), `TicketPriority`, `TicketMessageVisibility` (public, internal) |
| Safety | `IncidentCategory`, `IncidentSeverity` (low, medium, high, critical), `IncidentStatus` (new, triaged, investigating, escalated, resolved, closed) |
| Reviews | `ModerationStatus` (visible, hidden, under_review), `RatingTag` |
| CMS | `ContentStatus` (draft, in_review, scheduled, published, archived), `PageType` |

### 7.2 Booking & offer state machines

```text
BookingStatus transitions (enforced only by BookingTransitionService):

draft ───────────────► searching_offers ───► expired_no_provider
                          │      ▲
                          ▼      │ (all offers expired/withdrawn)
                       offers_received
                          │
           (atomic accept)▼
                    provider_selected ──► provider_heading_to_pickup ──► provider_arrived ──► in_progress
                          │                        │                          │                 │  │
                          ├── provider cancels ──► searching_offers (if policy auto_redispatch) │  ├─► delivery_attempt_failed ─► in_progress (retry)
                          │                        else cancelled_by_provider                   │  │                           └► cancelled_by_operations / disputed
                          ▼                                                                     ▼  ▼
   cancelled_by_customer / cancelled_by_provider / cancelled_by_operations        completed_pending_payment ─► completed ─► disputed ─► completed
   (allowed from draft…provider_arrived; guarded by actor, policy, fee rules)     (operations may cancel in_progress with reason)
```

Guards (SRS §8.3) live in `BookingTransitionService::guard{To}()`:
`provider_selected` (offer active and unexpired, provider still eligible, no assignment); `provider_arrived` (owner, GPS evidence stored); `in_progress` (owner, arrival, **PIN verified** if required, pickup proof if required); `completed` (required proofs present, server-computed totals); `cancelled_*` (actor/state/time window → fee decision snapshot).

```text
OfferStatus:
submitted / counter_offered ─► accepted_by_customer
                            ├► rejected_by_customer
                            ├► withdrawn_by_provider
                            ├► expired
                            ├► invalidated_request_changed
                            ├► invalidated_provider_unavailable
                            └► lost_booking_assigned
```

### 7.3 Provider onboarding status

`draft → phone_verified → profile_submitted → documents_pending → under_review → approved`, with branches `more_information_required ↔ under_review`, `rejected` (resubmittable), `temporarily_suspended ↔ approved`, `permanently_suspended`, `deletion_pending`.
**`online_eligible` and `expired_documents` are computed** by `ProviderEligibilityService::check(Provider, ?ServiceCategory): EligibilityResult` (list of `EligibilityReason`). They are not stored states, so they cannot drift from reality.

### 7.4 Money flow worked examples (ledger postings)

Agreed fare 100.00 EGP, commission 10%:

| Scenario | Debits | Credits | Check |
|---|---|---|---|
| Card, no coupon | gateway_clearing 100 | provider_wallet 90, platform_revenue 10 | 100 = 100 |
| Card, platform coupon 20 (customer pays 80) | gateway_clearing 80, promo_expense 20 | provider_wallet 90, platform_revenue 10 | 100 = 100 |
| Cash, no coupon (driver holds 100 cash) | provider_wallet 10 | platform_revenue 10 | provider wallet −10 (commission owed) |
| Cash, platform coupon 20 (driver holds 80) | promo_expense 20 | platform_revenue 10, provider_wallet 10 | driver nets 80 + 10 = 90 |
| Cash, provider-funded coupon 20 (driver holds 80) | provider_wallet 10 | platform_revenue 10 | driver nets 80 − 10 = 70 (explicit in breakdown) |
| Tip 15 by card | gateway_clearing 15 | provider_wallet 15 | commission-free by default |
| Payout 200 requested → paid | provider_wallet 200 (on reserve) | payout_clearing 200 → then payout_clearing 200 / gateway_clearing 200 | reservation prevents double payout |

Provider wallet below `provider_cash_debt_limit` (setting) adds `EligibilityReason::CashDebtLimitExceeded`.

### 7.5 Realtime channels & events

Channels: `private-customer.{userUuid}`, `private-provider.{providerUuid}`, `private-booking.{bookingUuid}`, `private-admin.operations` (permission `bookings.view`). Events (SRS §10.4) all implement `ShouldBroadcast`, carry `{booking_uuid, sequence, occurred_at, payload}`, and are written to `booking_status_events` / `outbox_events` before broadcast (`ShouldDispatchAfterCommit`).

---

## 8. Phase Roadmap

| Phase | Milestones | Priority | Outcome |
|---|---|---|---|
| **0 Foundation** | P0.1 Audit, git, docs, tooling · P0.2 Backend core architecture · P0.3 Admin UI kit + AJAX forms + DataTables + generator · P0.4 Flutter foundation + shared package | P0 | Skeleton that enforces all standards |
| **1 Core platform** | P1.1 Admin auth, roles/permissions, audit · P1.2 Settings, markets, locales, policy versions · P1.3 Identity API (OTP, users, profiles, consents) · P1.4 Service catalog + form schemas · P1.5 Geography, zones, maps adapter · P1.6 Customers admin module | P0 | Configurable platform + identities |
| **2 Ride MVP** | P2.1 Provider onboarding + verification · P2.2 Availability, location, eligibility, dispatch · P2.3 Pricing reference + booking creation · P2.4 Offers & atomic selection · P2.5 Trip lifecycle, PIN, cancellation v1, ratings, share · P2.6 Customer app ride flow · P2.7 Driver app ride flow · P2.8 Admin bookings & live ops v1 · P2.9 E2E + race tests + MVP hardening | P0 | **Negotiated ride works end-to-end** |
| **3 Money & support** | P3.1 Ledger, wallets, commission · P3.2 Payments + webhooks + tips · P3.3 Coupons engine + manager · P3.4 Earnings, payouts, adjustments, cash reconciliation · P3.5 Cancellation/no-show policies, fare adjustments, receipts · P3.6 Notifications platform · P3.7 Booking chat + support tickets · P3.8 Flutter money/support features | P1 | Launch-grade finance + support |
| **4 Multi-service** | P4.1 Courier backend (contacts, recipient OTP, proofs, attempts) · P4.2 Moving & freight backend (scope offers, items, capacity) · P4.3 Flutter courier/moving/freight flows · P4.4 Admin + E2E for non-ride flows | P1 | Not just a taxi clone |
| **5 Operations & web** | P5.1 Safety & incidents + trusted contacts · P5.2 Reviews moderation & risk signals · P5.3 Dashboard, reports, exports · P5.4 CMS · P5.5 Public website · P5.6 Integration health, app versions, feature flags · P5.7 Referrals, loyalty, incentives (P2) | P1/P2 | Full operations center + public site |
| **6 Hardening** | P6.1 Security/IDOR/race review · P6.2 Performance, indexes, retention · P6.3 L10n/RTL/accessibility review · P6.4 CI, deployment, backups, release report | P0 | Production-ready |

Dependency order (an arrow means "must be done before"):

```text
P0.1 → P0.2 → P0.3 ─┐
        └──→ P0.4   ├─► P1.1 → P1.2 → P1.3 → P1.4 → P1.5 → P1.6
                    │                                     │
                    └─────────────────────────────────────┴─► P2.1 → P2.2 → P2.3 → P2.4 → P2.5 ─┬─► P2.6
                                                                                               ├─► P2.7
                                                                                               └─► P2.8 → P2.9
P2.9 → P3.1 → P3.2 → P3.3 → P3.4 → P3.5 → P3.6 → P3.7 → P3.8
P3.8 → P4.1 → P4.2 → P4.3 → P4.4 → P5.1 … P5.7 → P6.1 … P6.4
```

---

## 9. Session Preamble (paste on top of every prompt)

```text
ROLE: You are a senior software engineer (Laravel 13 / PHP 8.3, Flutter 3.47 / Dart 3.13, MySQL 8, Redis 7) building a production
platform. Work autonomously; do not ask routine questions. Record assumptions in docs/DECISIONS.md and keep going.

WORKSPACE: /var/www/delivery_platform (monorepo)
  backend/            Laravel 13 (API /api/v1, Admin /admin, Website /)
  customer_app/       Flutter customer app
  driver_app/         Flutter driver/provider app
  packages/platform_core/  shared Dart package
  docs/               plan/status/decisions/architecture/api/integrations

SOURCES OF TRUTH (read the sections a milestone cites before coding):
  - indrive_inspired_delivery_platform_srs.md   (SRS: product requirements)
  - BUILD_PHASES_AND_PROMPTS.md                  (Plan: standards §4–§7 are BINDING)
  - docs/IMPLEMENTATION_STATUS.md, docs/DECISIONS.md (current state; never redo finished work)

NON-NEGOTIABLE STANDARDS:
  1. Backend layering: Controller → FormRequest → DTO (readonly) → Service → Repository (interface + Eloquent) → Model.
     No queries outside repositories; no business logic in controllers, repositories, models or views.
  2. Enums for every status/type/permission/setting key. No magic strings. Money = integer minor units via Money VO.
  3. Admin panel: Tailwind CSS 4 + Blade components only. Every form uses <x-admin.form> (AJAX; Precognition validation call
     BEFORE submit; on errors show them inline and prevent submit; when clean, fire the submit event and post via AJAX).
     Every listing = Yajra AdminDataTable + <x-admin.filters-card> COLLAPSED BY DEFAULT. Follow the Admin Module Contract (Plan §5.6).
  4. Every privileged action: AdminPermission enum + Policy + audit log; dangerous actions need a reason (and dual approval where the SRS says so).
  5. Server owns all business rules. Use transactions, row locks and Idempotency-Key on critical writes. Never trust client totals/statuses.
  6. Arabic is the default locale, English is secondary. No hard-coded user-visible strings. RTL via logical CSS utilities /
     Directional Flutter widgets.
  7. External services only through adapters. Fake/log drivers are allowed only in local/testing (boot-time guard). Never fake success.
  8. Flutter: feature-first, Riverpod notifiers → repository contracts → datasources → platform_core ApiClient; all states handled.
  9. No dead buttons, no placeholder screens presented as working, no secrets in code.

VERIFY BEFORE FINISHING (run what applies, paste real results into the status doc):
  backend:  cd backend && vendor/bin/pint --test && vendor/bin/phpstan analyse && php artisan test --parallel && npm run build
  flutter:  dart format --output=none --set-exit-if-changed . && flutter analyze && flutter test   (in each touched app/package)
  Never claim a command passed unless you saw it pass. If something cannot run here, say exactly why.

CLOSE-OUT:
  - Update docs/IMPLEMENTATION_STATUS.md: milestone status (✅/🟡/❌), what was built, files/modules touched, commands + results,
    known gaps, next steps.
  - Update docs/DECISIONS.md (new decisions), docs/API.md (new endpoints), docs/INTEGRATIONS.md (new adapters/env vars).
  - Work on branch `milestone/<id>-<slug>`. Commit with a conventional message when the gate passes.
```

---

## 10. Phase Prompts

> Format per milestone: **Depends on** · **Priority** · then the prompt to paste after the preamble.

---

### PHASE 0 — Foundation

#### P0.1 — Repository audit, git, documentation spine, tooling baseline
**Depends on:** — · **Priority:** P0

```text
MILESTONE P0.1 — Repository audit, git, documentation spine, tooling baseline.
Read: SRS §0, §2, §29, §30; Plan §1–§3, §11.

1. Inspect the whole monorepo (backend composer/package files, config, migrations, routes; both Flutter apps' pubspec, lib, platform
   folders; installed toolchain versions). Write the real state to docs/IMPLEMENTATION_PLAN.md (section "Baseline"), then add the
   milestone list from Plan §8 with expected modules, test commands and risks.
2. git: `git init` at the monorepo root, root .gitignore (Laravel, Flutter, IDE, .env*, keystores, *.p12, *.jks, google-services.json,
   GoogleService-Info.plist, service-account*.json, storage dumps, backups). Initial commit "chore: baseline skeletons" on main,
   then branch milestone/p0.1-foundation.
3. backend/AGENTS.md asks for Laravel Boost: install `laravel/boost --dev`, run `php artisan boost:install` (non-interactive options
   suitable for Claude Code), re-read the generated AGENTS.md/CLAUDE.md and respect it from now on.
4. Create docs/: IMPLEMENTATION_PLAN.md, IMPLEMENTATION_STATUS.md (table of all milestones, all ❌ except P0.1),
   DECISIONS.md (ADR-01..ADR-18 from Plan §3, dated 2026-10-13, each with context/decision/consequences),
   ARCHITECTURE.md (module map, layering diagram, request flow, realtime flow, data stores), API.md (conventions: versioning,
   envelope, error codes, pagination, idempotency, auth; endpoints are added later), INTEGRATIONS.md (adapter list with env vars
   "not configured yet"), SETUP.md (local setup for backend + apps), CONTRIBUTING.md (layering rules, naming, branch/commit conventions).
5. Tooling: add Larastan (`larastan/larastan`) with phpstan.neon (level 6, paths app/), confirm Pint config (laravel preset + strict rules
   you document), composer scripts: `lint`, `analyse`, `test`, `check` (= all). Add a root Makefile or scripts/check.sh running backend
   and Flutter checks.
6. Switch backend .env.example to MySQL + Redis (DB_CONNECTION=mysql, DB_DATABASE=delivery_platform, REDIS_CLIENT=phpredis,
   CACHE_STORE=redis, QUEUE_CONNECTION=redis, SESSION_DRIVER=redis, APP_LOCALE=ar, APP_FALLBACK_LOCALE=ar, APP_FAKER_LOCALE=ar_EG),
   document every variable with a comment. Create local DBs delivery_platform and delivery_platform_testing (document the SQL);
   configure phpunit.xml to use the testing MySQL DB and Redis DB 15.
7. Run the baseline: composer install, migrate, php artisan test, pint --test, phpstan, npm install && npm run build,
   flutter analyze/test in both apps. Record results.

Done when: git initialized with baseline commit; docs spine exists with real content; checks run and results recorded.
```

#### P0.2 — Backend core architecture
**Depends on:** P0.1 · **Priority:** P0

```text
MILESTONE P0.2 — Backend core architecture (no business features yet).
Read: SRS §19.8, §20, §20.7, §23, §24; Plan §3, §4, §7.1.

Packages (verify each declares Laravel 13 / PHP 8.3 support on Packagist before requiring; never use --ignore-platform-reqs;
record chosen versions in DECISIONS):
  laravel/sanctum, laravel/reverb, spatie/laravel-permission, spatie/laravel-translatable, yajra/laravel-datatables (oracle + html
  [+ buttons only if needed]), pragmarx/google2fa (admin MFA), predis not needed (phpredis present).

Build:
1. Folder structure exactly as Plan §4.1. RepositoryServiceProvider with a $bindings map; IntegrationServiceProvider (empty map now)
   with the guard that throws at boot if a fake/log driver is configured outside local/testing.
2. Base abstractions: BaseRepositoryInterface + BaseRepository; Enum contracts HasLabel/HasColor + trait EnumHelpers (values, options
   for selects, labels, tryFromValue) + BadgeColor enum; App\Support\Money\{Money, Currency, MoneyCast, MoneyFormatter} with full unit
   tests (add/subtract/multiply by basis points with documented rounding, allocation without losing minor units, formatting ar/en);
   App\Support\Http\ApiResponse (success/error/paginated, envelope per ADR-14).
3. Exceptions: DomainException base, BusinessRuleViolation(code, field?, context), ConflictException, ForbiddenActionException.
   Render them (and ValidationException, AuthorizationException, ModelNotFound, ThrottleRequests) into the envelope for JSON/AJAX
   requests, never leaking internals; include request id. Admin/Web HTML keeps normal views.
4. Middleware: CorrelationId (X-Request-Id), SetLocale (user pref → Accept-Language → ar; also sets Carbon locale), Idempotency
   (table idempotency_keys: actor_type, actor_id, key, route, request_hash, response_status, response_body, expires_at; replay;
   mismatch → 422 idempotency_key_reused; concurrent same key → 409 while in progress via Redis lock), ForceJsonResponse for api.
5. Route files: routes/api_v1.php (prefix api/v1, name api.v1.), routes/admin.php (prefix env ADMIN_PATH default "admin", name admin.,
   middleware web + HandlePrecognitiveRequests + admin auth later), routes/website.php, routes/channels.php. Register in bootstrap/app.php.
6. Audit foundation: admin_audit_logs migration (id, actor_type, actor_id, action, auditable_type, auditable_id, before json, after json,
   reason, ip, user_agent, request_id, meta json, created_at; indexes) + AuditAction enum + AuditLogger service + Auditable concern
   (auditSnapshot() with $auditExcluded attributes). Records are append-only (no update/delete in repository).
7. Common tables: currencies (code, exponent, symbol_ar/en, active), countries (iso2, name translatable, phone_code, currency, timezone,
   default_locale, active). Seed EG/EGP.
8. Localization skeleton: lang/ar and lang/en (validation, auth, pagination, passwords, enums.php, api.php, admin.php), ar default.
9. Rate limiters (named, configurable via settings later): otp-request, otp-verify, location, offers, public-forms, admin-login.
10. Reverb installed and configured (env documented), broadcasting enabled, queue connections + named queues (critical, default,
   notifications, low), scheduler heartbeat command writing cache key + `system:heartbeat` scheduled every minute.
11. Health endpoint GET /api/v1/health (db, redis, queue, scheduler heartbeat age) without leaking details publicly
   (detail only for authorised admin page later).

Tests: Money unit tests; envelope/exception rendering; idempotency replay/mismatch/concurrency; locale negotiation; audit logger.
Done when: all green; ARCHITECTURE.md and API.md updated with conventions actually implemented.
```

#### P0.3 — Admin UI kit, AJAX/Precognition forms, DataTables, filters card, generator
**Depends on:** P0.2 · **Priority:** P0

```text
MILESTONE P0.3 — Admin UI foundation. This is the template for every admin screen; quality here decides quality everywhere.
Read: SRS §16 (intro), §18, §26; Plan §5 (all of it, binding).

1. Vite entries admin (resources/css/admin.css, resources/js/admin/app.js) and website (separate). npm deps: alpinejs, axios, jquery,
   datatables.net (2.x) + datatables.net-responsive, tom-select, flatpickr (with ar locale), leaflet, leaflet-draw, chart.js.
   Pin exact versions; document in DECISIONS. Self-host an OFL Arabic+Latin font.
2. Tailwind 4 theme tokens in admin.css (@theme: brand palette, semantic colours success/warning/danger/info/neutral, radius, shadows,
   font). Tailwind skins for DataTables, Tom Select, flatpickr, Leaflet controls. Logical utilities only (Plan §5.1).
3. Layouts: admin auth layout + app layout (collapsible sidebar from config/admin_menu.php filtered by permissions, topbar with
   locale switcher ar/en persisted per admin, toast stack, global modal container, dir/lang on <html>).
4. Implement EVERY component in Plan §5.2 as anonymous or class-based Blade components under components/admin. Badge accepts any
   HasLabel&HasColor enum. Money component formats minor units. Translatable inputs render ar/en tabs, ar required.
5. JS core modules (resources/js/admin/core): http.js (axios instance: CSRF, Accept JSON, X-Request-Id, X-Locale, error normaliser),
   ajax-form.js implementing EXACTLY the protocol in Plan §5.3 (client checks → Precognition validation call → inline errors + prevent
   submit, or dispatch 'ajax-form:validated' then fire 'ajax-form:submit' → real AJAX submit → handle redirect / reload_table /
   close_modal / reset / replace_html; 409/419/403/5xx handling; double-submit lock; live per-field validation with
   Precognition-Validate-Only; dot→bracket error key mapping incl. nested arrays; focus/scroll first error), toast.js, modal.js,
   confirm-action.js (reason textarea when required; AJAX POST; table reload), datatables.js (AdminTables registry, filters
   merge into ajax data, URL ?filters[] sync, reload/reset, localized language), filters-card.js (Alpine component, collapsed by
   default, active count badge), remote-select.js (Tom Select ↔ /admin/lookups/{lookup}?q=), date pickers, map-point/polygon.
6. Backend support: AdminController base; AdminDataTable abstract (Plan §5.4: defaults, column helpers, actions column via policies,
   allowlisted ordering, queued CSV export hook with permission + audit); LookupController + LookupRegistry (allowlisted lookups,
   each backed by a repository method, permission-checked, max 20 results); helpers to return ApiResponse for AJAX.
7. Generator `php artisan make:admin-module {Name} {--module=} {--translatable=*} {--enum-status=}` producing all files listed in
   Plan §5.5 from stubs/admin-module/*.stub, plus menu/route/lang snippets and a Feature test. Generated code must pass Pint +
   PHPStan unmodified.
8. Proof module (local env only, route-guarded, removed from menu in production): generate a "Demo Item" module with the generator
   to exercise: listing with 4 filter types, create/edit with every form component, a confirm-with-reason action, a modal form.
   Write Feature tests per the Admin Module Contract §5.6 (they become the reference tests) and a JS smoke test of ajax-form.js
   with Vitest + jsdom (precognition 422 renders errors and blocks submit; 204 fires submit event; double submit blocked).
9. Add a test that fails if admin Blade files use physical direction utilities (ml-, mr-, pl-, pr-, left-, right-, text-left, text-right).

Done when: demo module fully works in ar (RTL) and en (LTR); Vitest + PHP tests green; generator output passes checks;
Plan §5 is reflected 1:1 in code (note any deviation in DECISIONS with a reason).
```

#### P0.4 — Flutter foundation + shared package
**Depends on:** P0.1 (can run in parallel with P0.2/P0.3) · **Priority:** P0

```text
MILESTONE P0.4 — Flutter foundation for customer_app, driver_app and packages/platform_core.
Read: SRS §3, §18, §25; Plan §6, §7.1.

1. Create packages/platform_core (pure Flutter package) and depend on it from both apps via path. Add packages (latest stable
   compatible with Dart 3.13; verify; record versions): flutter_riverpod, riverpod_annotation, riverpod_generator, build_runner,
   freezed, freezed_annotation, json_serializable, json_annotation, go_router, dio, flutter_secure_storage, shared_preferences,
   intl, flutter_localizations, flutter_map, latlong2, geolocator, permission_handler, dart_pusher_channels, connectivity_plus,
   package_info_plus, url_launcher, share_plus, image_picker, cached_network_image, pinput (OTP), a phone input package with country
   picker (EG preselected). Firebase packages are added in P3.6.
2. platform_core per Plan §6.1: Env via --dart-define (API_BASE_URL, REVERB_HOST/PORT/KEY/SCHEME, MAP_TILE_URL, FLAVOR);
   ApiClient with interceptors (auth bearer, Accept-Language, Idempotency-Key helper, X-Request-Id, 401 → session-expired stream,
   GET-only retry with backoff), envelope parsing into ApiResult/ApiException(code, message, fieldErrors, requestId);
   TokenStore (secure storage); Money + MoneyFormatter (Arabic-Indic digits preference); enum base with unknown fallback; design
   tokens + ThemeData (original brand, Material 3); shared widgets list in Plan §6.1 with widget tests in RTL and LTR.
3. Each app: app/bootstrap.dart (init storage, locale before first frame, error zone, flavor), router.dart (go_router, auth redirect
   placeholder, splash → language → onboarding → auth), theme, l10n via ARB (app_ar.arb template + app_en.arb; gen-l10n configured),
   language picker screen (Arabic preselected, persisted), offline banner, generic Error/Empty/Loading states.
   Driver app UI tokens: bigger touch targets and typography (driving context).
4. Folder skeleton per Plan §6.1 with one example feature ("app_config": GET /api/v1/config/app is implemented in P1.2;
   until then the repository is wired and tested with a mocked ApiClient — no fake data in the UI).
5. Android/iOS: app ids (configurable), Arabic app names, minimum SDKs documented, internet/location permission strings in ar/en
   (iOS InfoPlist.strings), no signing material committed.
6. Tests: unit (ApiClient envelope/errors, Money), widget (language picker RTL/LTR, shared widgets), `flutter analyze` clean.

Done when: both apps launch to the language screen in Arabic RTL, switch to English LTR, tests and analyze green.
```

---

### PHASE 1 — Core Platform

#### P1.1 — Admin authentication, roles/permissions, admin users, audit log UI
**Depends on:** P0.3 · **Priority:** P0

```text
MILESTONE P1.1 — Admin identity & access.
Read: SRS §4.2, §16.16, §26, §13.3; Plan §3 (ADR-06), §5.6, Appendix A & B.

1. admins table (uuid, name, email unique, phone, password, locale, status AdminStatus, mfa_secret encrypted, mfa_enabled_at,
   last_login_at, lock_version, soft deletes) + `admin` session guard + password broker.
2. AdminPermission enum with ALL permissions from Appendix A (value = 'module.ability'); AdminRole enum for the 12 SRS roles;
   PermissionSeeder syncs enum → spatie permissions (guard admin) idempotently; RoleSeeder maps roles → permissions per Appendix B.
   Super Admin via Gate::before. A local-only command `admin:create` creates the first admin interactively (no default credentials).
3. Auth screens (auth layout, <x-admin.form>): login (throttled, generic error), TOTP MFA challenge + enrolment with QR + recovery
   codes, forgot/reset password, logout. Setting "mfa required for roles" (stored for now in config; moved to settings in P1.2).
   Login activity table (admin_login_events: success/failure, ip, ua, at) and active sessions list with revoke.
4. Admin modules via the generator + Admin Module Contract:
   - Admin Users (CRUD, assign roles, suspend/reactivate with reason, reset MFA with reason; cannot remove own super-admin role)
   - Roles (CRUD + permission matrix grouped by module with select-all per group; system roles protected from deletion)
   - Audit Logs (read-only listing, show page with before/after diff viewer; filters per Appendix C)
   - Login Activity (read-only listing)
5. Dashboard placeholder page showing only what the admin is permitted to see (real KPIs in P5.3) — no fake numbers; show
   "available after <module>" empty states.
6. Sidebar menu config with permission per item (Appendix D).

Tests: login throttle, MFA flow, permission enforcement on every route (data provider over all admin routes asserting middleware
'can:' or policy exists — a guard test that fails when a new admin route lacks authorization), audit rows for user/role changes,
contract tests for each module.
```

#### P1.2 — Settings, markets, locales, policy versions, app config API
**Depends on:** P1.1 · **Priority:** P0

```text
MILESTONE P1.2 — Typed platform settings & market configuration.
Read: SRS §3, §16.15, §32, §13.3 (consents); Plan ADR-09/10.

1. SettingKey enum (type, default, validation rules, group SettingGroup, isSensitive=false always for DB settings, description key).
   Initial keys: default_country, default_locale, supported_locales, default_currency, digits_preference, support_phone/email/whatsapp,
   otp_length, otp_ttl_seconds, otp_resend_cooldown_seconds, otp_max_attempts, booking_search_ttl_seconds, offer_ttl_seconds,
   max_offers_per_provider_per_booking, max_offer_revisions, dispatch_radius_steps_km, dispatch_batch_size, dispatch_wave_interval_seconds,
   location_stale_after_seconds, provider_single_active_job, provider_cash_debt_limit_minor, auto_redispatch_on_provider_cancel,
   ride_pin_required, share_link_ttl_minutes, rating_customer_by_provider_enabled, direct_phone_exposure, admin_mfa_required_roles,
   min_app_version_customer/driver, force_update flags, maintenance_mode_apps. (Category-level overrides come in P1.4.)
2. platform_settings table + SettingsRepository + SettingsService (typed get/set, cached, cache bust on write, audit with before/after).
3. Admin "Settings" screens: one tab per SettingGroup, fields auto-rendered from enum metadata via components, saved via
   <x-admin.form> (Precognition validates using SettingKey::rules()). Permission settings.view / settings.manage.
4. Markets: Countries & Currencies admin modules (generator); timezone per country; cities come in P1.5.
5. Policy versions: policy_versions (type: terms/privacy/driver_terms/coupon_rules/restricted_goods/cancellation, locale content,
   version, published_at, status ContentStatus) admin module with publish (reason, audit). user_consents table is created in P1.3.
6. API: GET /api/v1/config/app (locales, default locale, currency + exponent, digits preference, support contacts, min versions,
   maintenance flag, current policy versions, feature flags placeholder), GET /api/v1/locales. Cached, ETag support.
7. Flutter: wire app_config feature in both apps (startup fetch, cache, force-update and maintenance screens with real data).

Tests: settings validation per type, cache invalidation, config API shape (snapshot test), force-update logic widget test.
```

#### P1.3 — Identity API: OTP, users, customer/provider profiles, devices, consents, deletion
**Depends on:** P1.2 · **Priority:** P0

```text
MILESTONE P1.3 — Mobile identity.
Read: SRS §6.2, §7.1 (steps 1–2), §13.1, §13.3, §20.1, §20.7, §27.2 (OTP tests), §28 (OTP bypass rule).

1. Schema: users (uuid, name, phone_e164 unique, phone_verified_at, email nullable, photo_path, preferred_locale, status UserStatus,
   last_seen_at, soft deletes), customer_profiles, provider_profiles (created later in P2.1 but table/relationship stub OK),
   otp_challenges (phone hash, purpose, code_hash, attempts, expires_at, consumed_at, ip, device_id), user_devices (platform,
   device_id, push_token, app_version, last_active_at), user_consents (policy_version_id, locale, accepted_at, ip),
   account_deletion_requests (status, reason, requested_at, processed_at, processed_by).
2. SmsGateway contract + drivers: `log` (local/testing only; writes OTP to log channel `otp_dev`, never to API response) and one
   real driver skeleton documented in INTEGRATIONS (choose a commonly used provider in Egypt; real call implemented, untested without
   credentials, clearly marked). Test OTP: in local/testing only, a configured fixed code for configured test numbers; impossible in
   production (boot guard + test).
3. OtpService: generate (crypto-random, hashed), cooldown, attempts, expiry, rate limits per phone/IP/device; responses never reveal
   whether the phone has an account.
4. AuthService: verify OTP → create/find user → issue Sanctum token with ability per app (`customer` or `provider` — app identifies
   via header X-App: customer|provider) → register device. Refresh = re-issue on valid token; logout revokes current token.
5. Endpoints (SRS §20.1): otp/request, otp/verify, logout, GET/PATCH /me, POST /me/photo (image validation, private disk + signed URL
   in resource), GET /me/consents, POST /me/consents, POST /me/account-deletion-requests, POST /me/devices. Middleware
   EnsureAccountActive returns typed error codes per UserStatus.
6. Admin: nothing new beyond optional "OTP challenges" read-only log for system admins (no codes shown).
7. Flutter (both apps): phone entry (EG preselected), OTP screen with resend countdown + attempt errors, profile completion,
   consent screen with policy links, auth state + router redirect, logout, secure token storage; account status screens
   (suspended/restricted/deletion pending). Driver app stops after phone verification and routes to onboarding (P2.1).

Tests: throttling, expiry, attempts lock, enumeration-safe responses, bypass impossible in production env, token abilities
(customer token rejected on provider routes and vice-versa), IDOR on /me endpoints, Flutter OTP widget tests (errors, resend limit).
```

#### P1.4 — Service catalog, vehicle categories, form schemas, versioned snapshots
**Depends on:** P1.3 · **Priority:** P0

```text
MILESTONE P1.4 — Data-driven service catalog.
Read: SRS §5 (all), §16.4, §19.2, §20.2; Plan §2.3 (form schemas), §7.1.

1. Schema: service_families (code, name/description translatable, kind ServiceFamilyKind, icon, sort, active),
   service_categories (family_id, code, name/description translatable, icon, map_marker, sort, active, allowed_booking_types json of
   BookingType, negotiation_enabled, reference_estimate_enabled, min/max_customer_fare_minor, counter_offer_min/max_pct,
   offer_ttl_seconds?, search_ttl_seconds?, dispatch radius steps?, batch size?, max_passengers, max_luggage, max_weight_kg,
   max_volume_m3, supports_stops, max_stops, pin_required, pickup_proof_required, delivery_proof_types json ProofType,
   recipient_otp_required, tipping_enabled, coupon_eligible, cash_allowed, payment_methods json, service_hours json, lock_version),
   vehicle_categories (code, name translatable, capacity fields, icon, active), category_vehicle_category pivot,
   service_form_schemas (category_id, version, fields json, published_at, status), service_category_versions (immutable snapshot of
   category + schema + policies at publish time).
   Null category-level policy fields fall back to SettingKey defaults (resolved by CategoryPolicyResolver).
2. FormFieldType enum + FormSchemaValidator (validates the schema itself on admin save: unique keys, types, options, min/max,
   required, conditional visibility `visible_if`) + ServiceFormValidator (compiles a published schema version into Laravel rules for
   booking `service_details`; rejects unknown keys).
3. Publishing: editing changes a draft; "Publish" creates a new service_category_versions row (reason, audit). Bookings will
   reference version ids (P2.3). Safe guards: cannot deactivate a category with active bookings without explicit confirm + reason;
   cannot deactivate the last active category of the platform.
4. Admin modules (contract): Service Families, Service Categories (tabs: general, booking rules, pricing bounds & negotiation,
   dispatch, proofs & safety, payments & coupons, vehicle categories, form schema builder [repeater component with field type,
   labels ar/en, help ar/en, validation, options, visible_if], preview tab rendering the form as customer/driver would see it,
   versions history), Vehicle Categories.
5. Seeder (SRS §28): families + categories: tuk-tuk, economy car/taxi, standard car, courier motorcycle, pickup truck, furniture
   moving truck, freight truck, with sensible schemas (passengers/luggage; parcel size/weight/fragile/recipient; furniture items,
   floors, elevator, helpers; cargo type, weight, loading equipment) and Arabic-first labels.
6. API: GET /service-families, GET /service-categories?lat&lng&booking_type (availability by zone wired in P1.5 — for now active
   only), GET /vehicle-categories?service_category_id; Resources include the published form schema version.
7. Flutter platform_core: DynamicFormRenderer for every FormFieldType incl. visible_if, client validation mirroring schema rules,
   ar/en labels, RTL; widget tests per field type. Customer app: service family/category selection UI fed by API (route selection
   comes in P2.6).

Tests: schema validator (invalid schemas rejected), ServiceFormValidator (valid/invalid details, unknown keys), version snapshot
immutability after edits, admin contract tests, API resources.
```

#### P1.5 — Geography: cities, service zones, availability, maps adapter
**Depends on:** P1.4 · **Priority:** P0

```text
MILESTONE P1.5 — Service areas & maps.
Read: SRS §3, §6.3, §10.3, §16.5, §19.2, §20.2, §22; Plan ADR-03, ADR-12.

1. Schema: cities (country_id, name translatable, timezone, center POINT, active), service_zones (city_id, name translatable,
   polygon POLYGON SRID 4326 with spatial index, status ZoneStatus, operating_hours json, restricted_pickup bool, notes),
   zone_service_category pivot (active, overrides json: fare bounds, radius, hours), saved_places (user_id, label, type
   home/work/other, point, address snapshot).
2. GeoService: point-in-zone (ST_Contains), resolve(lat,lng) → city, zones, available categories (active + hours + overrides),
   distance helpers (haversine for ranking; MySQL spatial for filters). Validate coordinates and reject impossible values.
3. MapsProvider contract (geocode, reverseGeocode, placesSearch, route(origin, dest, stops) → distance_m, duration_s, polyline,
   provider status) with drivers: `osrm_nominatim` (configurable base URLs, respectful rate limiting) and `google` (key in env),
   plus `fake` for tests. Caching of geocode/route results with TTL; graceful errors mapped to BusinessRuleViolation codes
   (maps_unavailable, outside_service_area).
4. Admin modules (contract): Cities, Service Zones (Leaflet polygon draw/edit component, overlap warning, per-zone category
   availability matrix with overrides, operating hours editor), "Coverage preview" tool: drop a point (or two for a route) and see
   resolved zone + enabled categories + route estimate.
5. API: GET /service-areas/resolve, GET /places/search, GET /routes/estimate (route only; pricing added P2.3),
   CRUD /me/saved-places; GET /service-categories now filters by resolved zone.
6. Seed: Cairo + Giza (or one city) with a couple of demo zones, clearly labelled as demo data.
7. Flutter customer app: home map (flutter_map, configurable tiles, current location with permission rationale and manual fallback),
   pickup/destination search with debounced places search, map pin adjust, swap, saved places, outside-area state, map/geo error states.

Tests: point-in-polygon, zone override resolution, hours, resolve endpoint, maps adapter error mapping, saved places IDOR.
```

#### P1.6 — Customers admin module
**Depends on:** P1.5 · **Priority:** P0

```text
MILESTONE P1.6 — Customers in the admin panel.
Read: SRS §16.2, §13.3; Plan §5.6, Appendix C (Customers).

1. Customers listing (filters per Appendix C), show page with tabs: profile, bookings (empty until P2), payments (P3), coupons (P3),
   tickets (P3), ratings (P2), consents history, devices, saved places, restrictions/audit. Tabs whose module does not exist yet
   show an explicit "available after milestone X" state. No fake rows.
2. Actions (confirm + mandatory reason + audit): suspend (optional expiry → scheduled auto-reactivation job), reactivate, restrict,
   send help message (in-app; push later), process deletion request (approve → anonymise per retention rules documented in
   DECISIONS; reject with reason), export personal data (queued JSON export, signed link, audited, permission customers.export).
3. Viewing full phone numbers is masked by default; reveal requires customers.view_pii and is audited.
4. Customers can't be hard-deleted from the panel.

Tests: contract tests, PII masking/reveal audit, suspension expiry job, deletion anonymisation keeps financial references intact.
```

---

### PHASE 2 — Fare-Negotiated Ride MVP (the heart of the product)

#### P2.1 — Provider onboarding, vehicles, documents, verification
**Depends on:** P1.6 · **Priority:** P0

```text
MILESTONE P2.1 — Provider onboarding & verification.
Read: SRS §7.1, §7.7, §16.3, §19.1–19.2, §20.4 (applications/documents/vehicles); Plan §7.3, ADR-11.

1. Schema: provider_profiles (user_id, uuid, provider_type, status ProviderStatus, city_id, date_of_birth?, rating_avg, rating_count,
   completed_jobs, acceptance metrics, is_online, online_since, current_vehicle_id, lock_version), provider_service_category pivot
   (requested/approved), vehicles (provider_id, vehicle_category_id, make, model, year, color, plate (normalised + display),
   capacities, capabilities json, status VehicleStatus, rejection_reason), vehicle_photos, document_types (code, name translatable,
   applies_to provider|vehicle, requires_expiry, requires_number, allowed mimes, max size), document_requirements (document_type,
   country/city?, service_category?/vehicle_category?, provider_type?, mandatory), provider_documents & vehicle_documents (type,
   private path, file hash, number encrypted, issued_at, expires_at, status DocumentStatus, reviewed_by, reviewed_at,
   rejection_reason), provider_status_events (from, to, actor, reason).
2. Services: ProviderOnboardingService (state machine per Plan §7.3 with guards, events), DocumentService (upload to private disk,
   MIME + extension + size + image decode checks, FileScanner adapter (noop driver allowed with clear status), signed URL generation
   (5 min), every admin view/download audited), DocumentRequirementResolver (what is required for this provider + services +
   vehicle), ProviderEligibilityService (Plan I11; reasons enum) — used now for the "verification progress" view and later by dispatch.
3. API (provider ability): POST /provider/applications, GET /provider/application (status, pending tasks, missing documents, expiry
   dates), POST/GET /provider/documents, POST/PATCH /provider/vehicles, POST /provider/vehicles/{vehicle}/submit-review,
   GET /provider/eligibility (reasons). Policies prevent touching other providers' records.
4. Admin (contract): Document Types & Requirements; Verification Queue (listing of under_review applications with SLA age) and
   side-by-side review screen (documents viewer left via signed URLs, checklist + decision right): approve, request more info
   (per-document reasons), reject (reason), all audited; Providers listing + show (tabs: profile, services, vehicles, documents,
   verification history, jobs/earnings placeholders until P2/P3) with suspend/reactivate (reason, optional expiry); Vehicles listing
   + approve/reject. Read-only roles cannot see approve buttons and get 403 server-side.
5. Scheduled: documents:check-expiry (daily) → mark expired, notify (notification hook stub until P3.6 — log channel), provider
   eligibility recomputed.
6. Driver app: onboarding wizard (profile, provider type, service selection, vehicle(s) with photos, document uploads with expiry
   pickers and retry, declarations/consents, submit), verification status screen with pending tasks and rejection reasons,
   resubmission flow. Resume where left off.
7. Seeder: approved provider with valid docs + vehicle, pending provider, rejected provider, provider with expired document.

Tests: state machine transitions/guards, required documents resolution, private file access (no signed URL → 403; other provider → 403),
admin verification actions require reason + audit, eligibility reasons, driver app widget tests for wizard steps.
```

#### P2.2 — Availability, live location, eligibility, dispatch engine
**Depends on:** P2.1 · **Priority:** P0

```text
MILESTONE P2.2 — Online/offline, location ingestion, geo index, dispatch.
Read: SRS §7.2, §10 (all), §21; Plan §2.3, §7.5, ADR-03, ADR-07.

1. ProviderGeoIndex contract: RedisGeoIndex (GEOADD/GEOSEARCH per city + hash of {lat,lng,accuracy,heading,speed,recorded_at,
   vehicle_category_ids, service_category_ids}; TTL-based freshness) and DatabaseGeoIndex (for tests/local without Redis).
2. provider_locations (append, POINT, accuracy, heading, speed, source, recorded_at; partitioning/retention plan in DECISIONS) and
   provider_last_locations (one row per provider).
3. AvailabilityService: goOnline(provider, vehicle) uses ProviderEligibilityService (returns 422 with typed reasons), goOffline
   (blocked or confirmed workflow when an active job exists), auto-offline when location stale > threshold (scheduled sweep).
4. LocationIngestService: POST /provider/location (rate limited; batch of samples allowed); validates coordinates, rejects impossible
   jumps (speed > configurable threshold → flag `location_anomaly`, not silently dropped), updates geo index + last location,
   writes history sampled, broadcasts booking.location_updated to the booking channel when the provider has an active booking.
5. Dispatch: booking_dispatch_recipients (booking_id, provider_id, wave, distance_m, notified_at, seen_at, response) ;
   FindEligibleProvidersService (geo radius → filter by category/vehicle capability/capacity, approval, docs, freshness, no active job if
   single-job policy, debt limit, not already dispatched/declined) ; DispatchService + DispatchBookingWave job (queue critical,
   radius steps from CategoryPolicyResolver, batch size, delayed next wave until search TTL, emits booking.search_expanding,
   pushes booking.requested to each recipient's private-provider channel). Never claims all drivers were reached.
6. channels.php authorization for private-customer / private-provider / private-booking / private-admin.operations using policies.
7. Driver app: online/offline toggle with eligibility reasons, foreground location service with state-based frequency (idle / to
   pickup / active), Android foreground-service notification + iOS background mode justification, permission rationale screens,
   realtime connection to private-provider channel with reconnect + state indicator, stops tracking when offline.

Tests: eligibility matrix (each reason), going online blocked, location validation & anomaly flag, geo search correctness, wave
expansion timing (fake clock + Queue::fake/Bus assertions), channel authorization (customer cannot join another booking's channel).
```

#### P2.3 — Reference pricing & booking creation
**Depends on:** P2.2 · **Priority:** P0

```text
MILESTONE P2.3 — Price guidance and booking request creation.
Read: SRS §6.4, §8.1, §9.1–9.3, §19.3, §20.3 (POST /bookings, GET list/show); Plan I4, I9, I12, ADR-04, ADR-16.

1. fare_policies (category, zone?, formula version, base_minor, per_km_minor, per_min_minor, min_fare_minor, stop_fee_minor,
   attribute surcharges json, effective_from/to, lock_version) — admin module (contract) under "Pricing".
   ReferencePriceCalculator → {low, recommended, high, factors[]} as guidance only; versioned; no demand multipliers.
2. Schema: bookings (uuid, code human-friendly unique, customer_id, service_category_id, service_category_version_id,
   booking_type, status, sequence, pickup/dropoff POINT + address snapshot json, scheduled_at, passengers, service_details json
   (validated) + indexed searchable columns, proposed_fare_minor, currency, reference_estimate json, route_snapshot json,
   policy_snapshot json, payment_method, payment_status, coupon_reservation_id (P3.3), selected_offer_id, provider_id,
   search_expires_at, pin_hash/pin_encrypted (P2.5), cancelled_* fields, completed_at, idempotency_key, lock_version) ;
   booking_stops ; booking_status_events (from, to, actor_type, actor_id, reason, metadata, sequence, created_at).
3. BookingTransitionService (ADR-16) with the transition table from Plan §7.2 encoded in BookingStatus::allowedTransitions(),
   guard hooks, event persistence + broadcast after commit, sequence increment. Unit-test the whole transition matrix.
4. CreateBookingService: validate customer status, zone resolution for pickup (and dropoff policy), category availability,
   booking type allowed, schedule window, passenger/capacity limits, service_details via ServiceFormValidator, stops, proposed fare
   within bounds (category/zone/policy), payment method allowed (cash only until P3.2), route estimate via MapsProvider,
   snapshots (category version, policies, localized names), idempotency; transition draft→searching_offers; kick off dispatch.
   One active on-demand booking per customer (configurable).
5. Endpoints: GET /routes/estimate now returns price guidance; POST /bookings (Idempotency-Key required);
   GET /bookings (paginated, filters service/status/date); GET /bookings/{booking} (snapshot, timeline, offers summary,
   allowed_actions computed server-side). Customer Resources never expose provider private data beyond the current stage.
   PATCH /bookings/{booking}/request (change route/major details while searching) → invalidates active offers
   (invalidated_request_changed) and re-dispatches.
6. Scheduler + jobs: ExpireBookingSearch (delayed, exact) + bookings:expire-searches sweep (every 5s) → expired_no_provider,
   notify customer; both idempotent.

Tests: every validation rule (fare bounds, capacity, schema), idempotent create, snapshot immutability after admin edits,
expiry without client involvement, transition matrix exhaustive test, IDOR (customer B cannot read A's booking).
```

#### P2.4 — Offers, counter-offers & atomic selection
**Depends on:** P2.3 · **Priority:** P0 (the defining feature)

```text
MILESTONE P2.4 — Negotiation core.
Read: SRS §1, §6.5, §7.3, §7.4, §8.2, §9.3, §10.2 (steps 4–8), §20.3–20.4; Plan §2.2 (I1–I3, I9, I10), §2.3, §7.2.

1. Schema: booking_offers (uuid, booking_id, provider_id, vehicle_id, kind OfferKind, status OfferStatus, current_version_id,
   expires_at, eta_seconds, distance_m, revision_count, idempotency_key) ; booking_offer_versions (offer_id, version, amount_minor,
   currency, note, scope json (structured; used heavily in P4), created_at — append-only) ; unique (booking_id, provider_id) for
   the active offer; indexes on (booking_id,status), (expires_at,status).
2. OfferSubmissionService:
   - acceptCustomerFare(provider, booking) and counterOffer(provider, booking, amount, note, scope)
   - guards: provider was dispatched this booking; ProviderEligibilityService passes; booking in searching_offers|offers_received
     and not expired; counter amount within category counter bounds + currency; max offers per provider + max revisions; offer
     spam limiter; revision = new version row (never update amount in place) + audit.
   - estimated commission and net earning returned to provider (preview only; real calc P3.1).
   - transitions booking → offers_received on first offer; broadcasts booking.offer_received / offer_updated to the booking channel.
   - withdraw(provider, offer) while not accepted.
3. OfferSelectionService::accept(customer, booking, offer, idempotencyKey) — THE critical section:
   DB::transaction → lock booking row FOR UPDATE → re-check booking status & selected_offer_id IS NULL → lock offer → check status
   active and expires_at > now and provider still eligible (or grace window setting) → conditional UPDATE … WHERE selected_offer_id IS NULL
   (assert 1 row) → offer accepted_by_customer, all other active offers lost_booking_assigned → booking provider_selected with
   agreed fare snapshot (amount, scope, version id) → generate trip PIN (P2.5 uses it) → events after commit:
   booking.provider_selected to customer/booking/provider channels and offer_expired/lost to the others.
   Losers get 409 booking_already_assigned / offer_expired with fresh booking payload. Customer can also reject/dismiss an offer.
4. Expiry: ExpireOffer delayed job per offer + offers:expire sweep every 5s; provider offline/ineligible → active offers
   invalidated_provider_unavailable (hook from AvailabilityService/eligibility changes); when no active offers remain and search not
   expired → booking back to searching_offers.
5. Fairness warning (optional, configured): server returns `fare_hint` codes (e.g. below_recent_offers) — never modifies price.
6. Provider API: GET /provider/requests/available (only dispatched, still open, still eligible; ProviderRequestCardResource with masked
   pickup — rounded coords/area name, distance to pickup, destination area, proposed fare, details summary, expires_at),
   POST /provider/bookings/{booking}/offers/accept-customer-fare, POST /provider/bookings/{booking}/offers,
   POST /provider/offers/{offer}/withdraw, POST /provider/bookings/{booking}/skip (records response, no penalty),
   GET /provider/offers (with statuses: pending/selected/rejected/expired/invalidated).
   Customer API: GET /bookings/{booking}/offers (offer cards: provider display name, photo, rating + count, completed jobs, vehicle
   make/model/color/plate per privacy setting, amount + kind, ETA/distance when reliable, capability badges, expires_at),
   POST /bookings/{booking}/offers/{offer}/accept (Idempotency-Key), POST /bookings/{booking}/offers/{offer}/reject.

Tests (heaviest suite of the project): accept vs accept race (two sequential-in-transaction simulations + a parallel Process-pool
test in tests/Concurrency run against MySQL), accept expired offer → 409, accept after another accepted → 409, provider not
dispatched cannot offer, counter bounds/limits/revisions, versions immutability, withdraw rules, expiry by scheduler without client,
request change invalidates offers, staged disclosure (provider card has no exact address/phone; full details only after selection).
```

#### P2.5 — Trip lifecycle: heading, arrived, PIN, start, complete, cancellation v1, ratings, share link
**Depends on:** P2.4 · **Priority:** P0

```text
MILESTONE P2.5 — Ride lifecycle after selection.
Read: SRS §6.6, §6.7 (rating/receipt basics), §7.5, §8.3, §8.4, §13.1–13.2 (share link), §15, §20.3–20.4; Plan §7.2.

1. Provider endpoints (all Idempotency-Key, ownership policy, via BookingTransitionService):
   POST /provider/bookings/{booking}/acknowledge (→ provider_heading_to_pickup), /arrived (GPS evidence + timestamp, starts free-wait
   timer), /verify-start-pin (attempt limit → lock + support flag; PIN stored encrypted for display to customer, compared in constant
   time), /start (guard PIN verified when required + pickup proof when required), /complete (guard proofs; for rides: final totals =
   agreed fare snapshot + approved adjustments only; payment_status cash_due for cash). booking_proofs table (type, storage ref,
   hash, actor, metadata) created now (used fully in P4).
   GET /provider/bookings/active and GET /provider/bookings/{booking} (ProviderAssignedBookingResource: full pickup/dropoff,
   customer display name, masked phone or relay per setting, payment policy, cancellation terms).
2. Cancellation v1: cancellation_policies (category/zone, free_grace_seconds, fee rules by stage/actor, reasons allowed) admin module;
   CancellationService computes fee decision snapshot (fee may be 0 until ledger exists — posting added in P3.5), validates actor/
   stage/reason; customer endpoint POST /bookings/{booking}/cancel with `preview=true` mode returning the fee before confirmation;
   provider cancel endpoint; auto_redispatch on provider cancel (setting) → back to searching_offers with history kept.
3. Share trip: POST /bookings/{booking}/share → time-limited token (hashed), public web page /t/{token} (website layout, minimal data:
   status, provider first name, vehicle, live position, ETA; expires; noindex).
4. Ratings: ratings table (booking_id, rater, ratee, stars, tags json, comment, moderation_status) ; POST /bookings/{booking}/rating
   (once, only completed, within window); provider rating of customer if setting enabled; RatingAggregationService (documented
   formula, minimum sample indicator).
5. Report issue: POST /bookings/{booking}/reports creates a placeholder support ticket record minimal (full support in P3.7) — or
   returns 501-free behaviour by storing to booking_reports table that P3.7 migrates into tickets. Do not show a fake success.
6. Location visibility: customer sees provider location only from provider_selected to completion (+ retention policy).

Tests: full transition sequence happy path, start without PIN → 422, wrong PIN attempts lock, other provider cannot transition,
cancel preview vs commit consistency, share token expiry & data minimisation, duplicate rating rejected, idempotent transitions.
```

#### P2.6 — Customer app: complete ride flow
**Depends on:** P2.5 · **Priority:** P0

```text
MILESTONE P2.6 — Customer Flutter ride experience.
Read: SRS §6 (all), §18, §25, §27.3 (customer); Plan §6.

Implement, with real API integration and all states (loading/empty/error/offline/session-expired), Arabic RTL first:
1. Bottom navigation: Home, My Trips, Wallet (placeholder screen stating availability after P3 is NOT allowed — instead hide the tab
   behind a server feature flag until P3.8), Offers (same rule), Account.
2. Home: map, pickup/destination, service family + category cards from GET /service-categories (capacity, description, price range
   only when backend sends one), persistent active-booking banner.
3. Booking form: route confirm, DynamicFormRenderer for the category schema, reference estimate as guidance (explained factors),
   fare input with quick suggestions and bounds, payment method (cash), summary, submit with Idempotency-Key, keep input on errors.
4. Finding offers: realtime subscription to private-booking channel + polling fallback; countdown for search; offer cards
   (Plan/SRS fields) with per-offer expiry countdown; compare (sort by price/ETA/rating); accept (409 handling with refresh message);
   reject; edit fare / change request (warn that offers will be invalidated); no-offers state with retry / edit price / cancel
   (never promise that raising the fare guarantees offers).
5. Live trip: provider card, vehicle, agreed fare, PIN display, statuses timeline, map with smoothed provider marker + last-updated
   + stale warning, call (tel: or relay per config) / chat entry (chat in P3.7 — hide until then), share trip (system share sheet),
   safety button (SOS flow in P5.1 — until then shows configured emergency numbers and support contact, clearly labelled),
   cancel with fee preview confirmation.
6. Completion: receipt (agreed fare, add-ons, discount placeholder only if present, payment method), rating with tags + comment,
   report issue, rebook.
7. My Trips: history list with filters (service/status/date), trip details with timeline and receipt.
8. Deep links/notification taps route to booking (push wiring in P3.6).

Tests: widget tests for offer card (both kinds, expiry), booking form validation, no-offer state, stale location banner, RTL/LTR
goldens for home/offers/trip; integration_test for create → offers → accept against local backend with test OTP (document how to run).
```

#### P2.7 — Driver app: complete ride flow
**Depends on:** P2.5 · **Priority:** P0

```text
MILESTONE P2.7 — Driver Flutter ride experience.
Read: SRS §7 (all), §18.3 (driver distraction), §25, §27.3 (driver); Plan §6.

1. Home: online toggle with eligibility reasons, vehicle/service selector, map, earnings snapshot (hide until P3.4 via feature flag),
   rating, connection indicator.
2. Incoming requests feed: realtime + refresh, sound/vibration preferences, request cards (category, capability, masked pickup area,
   distance, destination area, proposed fare, details summary, expiry timer), actions: accept fare, counter-offer (large numeric
   keypad, bounds shown, commission/net preview from API), skip. Duplicate-tap safe (idempotency), expired/lost states.
3. My offers: pending/selected/rejected/expired/invalidated with reasons; withdraw.
4. Active job: big-button workflow Navigate to pickup (external maps intent), Arrived, PIN entry, Start, Complete; customer
   instructions, contact (relay/tel per config), cancel with policy, report issue; background location per state; app resume
   restores the active job from GET /provider/bookings/active.
5. Completion summary: amount, payment method (cash to collect), net earning preview.
6. Job history list + detail.

Tests: widget tests for request card timer/expiry, counter-offer validation, PIN flow errors, ineligible cannot go online;
integration_test: go online → receive request → counter → selected → arrive → PIN → start → complete (with local backend).
```

#### P2.8 — Admin: bookings, offers monitor, live operations v1
**Depends on:** P2.5 · **Priority:** P0

```text
MILESTONE P2.8 — Admin booking operations.
Read: SRS §16.6, §16.7, §8.3 (overrides); Plan §5.6, Appendix C (Bookings, Offers).

1. Bookings listing (filters: code, statuses multi, service category, city/zone, booking type, payment status, date range, customer
   phone, provider phone, has incident/ticket later) with status badges and live-refresh toggle.
2. Booking show: summary, route map, snapshots (json viewer), status timeline (booking_status_events), offers table with all versions,
   dispatch recipients (waves, responses), assignment, PIN status (never the PIN), proofs, ratings, payment section (P3), audit tab.
3. Overrides (permission + mandatory reason + audit + notifications to parties): cancel by operations (fee decision explicit),
   extend search, re-dispatch now, force status transition limited to the allowed graph (bookings.assign_override), emergency PIN
   override (separate permission). Overrides never edit historic offers/prices.
4. No-offer queue: searching bookings older than N seconds or expired_no_provider in last X hours with actions retry/extend/contact/cancel.
5. Live operations map: Leaflet map of online providers (aggregated/clustered, no PII on hover beyond id/category) and active bookings;
   updates through Echo on private-admin.operations; realtime connection health + last event time indicator.
6. Offers monitor: listing of offers with filters, conversion stats (submitted → selected), anomaly flags (very low/high vs reference).

Tests: contract tests, override reason/audit, override cannot leave the transition graph, operations channel authorization.
```

#### P2.9 — E2E ride test, race tests, MVP hardening
**Depends on:** P2.6, P2.7, P2.8 · **Priority:** P0

```text
MILESTONE P2.9 — Prove the MVP.
Read: SRS §27.2, §27.5 (steps 1–7, 9–10), §31 (customer/provider lifecycle); Plan §2.2.

1. Backend end-to-end Feature test (single test class, real HTTP layer): customer OTP register → approved provider online with vehicle
   → customer creates tuk-tuk/car booking with proposed fare → provider receives request (dispatch recipients) → provider counter-offers
   → customer accepts → provider acknowledge/arrive/PIN/start/complete → customer rates → history shows trip → admin booking show page
   displays timeline + audit.
2. tests/Concurrency: Process-pool scripts (php artisan qa:race-accept / qa:race-offer) hammering a local server: N parallel accepts of
   different offers on one booking → exactly one success; duplicate idempotent requests → identical responses. Document how to run.
3. Review every P2 endpoint for: policy coverage, idempotency where required, N+1 (enable Model::preventLazyLoading in non-prod and fix),
   indexes on hot queries (EXPLAIN the dispatch, available-requests and offers queries), error codes documented in API.md.
4. Seeder: demo bookings in key lifecycle states (SRS §28).
5. Update IMPLEMENTATION_STATUS with an honest MVP readiness report.

Done when: E2E + concurrency proofs pass; no known P0 defect open.
```

---

### PHASE 3 — Money, Coupons, Notifications, Support

#### P3.1 — Double-entry ledger, wallets, commission rules, price breakdown
**Depends on:** P2.9 · **Priority:** P1

```text
MILESTONE P3.1 — Financial core.
Read: SRS §9.2, §12.3, §12.4, §7.6 (ledger parts), §19.6; Plan §2.2 (I5, I6, I12), §7.4, ADR-04, ADR-18.

1. Schema: ledger_accounts (owner_type/id nullable for platform accounts, type LedgerAccountType, currency, balance_minor cached,
   allow_negative, credit_limit_minor, lock_version) ; journal_entries (uuid, type JournalEntryType, reference_type/id, description key,
   posted_at, reversal_of_id, created_by, idempotency_key unique) ; journal_lines (entry_id, account_id, side EntrySide, amount_minor,
   currency). No update/delete paths anywhere (repository has no such methods; DB triggers optional).
2. LedgerService::post(JournalEntryData): validates Σdebit == Σcredit per currency, locks accounts in deterministic order (by id) to
   avoid deadlocks, enforces negative-balance policy, updates cached balances, idempotent by key; reverse(entry, reason).
   ledger:reconcile command compares cached balances vs line sums → report + alert (no auto-fix).
3. commission_rules + versions (scope: service category, zone, provider tier, campaign, effective dates; type percentage / fixed /
   hybrid; min/max) admin module (contract) with preview calculator. CommissionResolver picks the rule; snapshot stored on booking at
   selection time (update P2.4 to snapshot).
4. PriceBreakdownCalculator (single source): agreed fare, accepted add-ons, coupon discount + funding split (P3.3 plugs in), customer
   fee, tax (configurable, default 0), tip, customer payable, provider gross, commission, provider net, platform revenue; all Money.
   Stored on booking at completion as breakdown snapshot.
5. BookingSettlementService: on completion posts the journal per the worked examples (Plan §7.4) for cash (card in P3.2); provider
   wallet below credit limit → eligibility reason CashDebtLimitExceeded.
6. Admin (contract, finance permissions): Ledger accounts listing, journal entries listing + show (lines), wallet view per
   customer/provider tab (finance.view), reconciliation report page.

Tests: every worked example in Plan §7.4 as a unit test (balanced, correct balances), reversal, idempotent posting, negative balance
rules, deadlock-safe lock ordering, commission resolution precedence and snapshot immutability.
```

#### P3.2 — Payments: gateway adapter, cash flow, webhooks, tips
**Depends on:** P3.1 · **Priority:** P1

```text
MILESTONE P3.2 — Payments.
Read: SRS §12.1, §12.2, §6.7, §20.5 (payments, webhook), §20.7; Plan I8, ADR-12.

1. PaymentGateway contract: createIntent, confirm/authorize/capture (if supported), verifyWebhook(signature, timestamp/replay window),
   queryStatus, refund, reconcile(listTransactions). Drivers: `sandbox` (local/testing; simulates async webhook via signed internal
   callback, never marks paid synchronously) and one real Egyptian-market gateway driver (choose a widely used one; document merchant
   requirements and env in INTEGRATIONS; implemented against its documented API, untested without credentials and flagged so).
2. Schema: payment_methods (user, type, gateway token reference, brand/last4 only, default), payments (uuid, booking_id, method,
   gateway, amount_minor, currency, status PaymentStatus, gateway_reference, failure_code), payment_events, gateway_webhook_events
   (gateway, event_id unique, signature_valid, payload hash, processed_at), payment_refunds.
3. PaymentService: intents for card/wallet bookings (at completion or authorization at selection — record decision), webhook processing
   (verify → dedupe → state transition → LedgerService posting → broadcast payment.status_changed), late/duplicate/out-of-order
   webhooks, timeout → reconciliation_required. Cash: provider confirms "cash collected" → cash_collected + settlement posting.
   Booking status completed_pending_payment ↔ completed handled via BookingTransitionService.
4. Tips: POST /bookings/{booking}/tips (eligibility per category/market, not preselected, posted to provider wallet commission-free by
   default).
5. API: POST /bookings/{booking}/payment-intents, GET/DELETE /me/payment-methods, POST /me/payment-methods/setup-intent,
   POST /payment-gateways/{gateway}/webhook (no auth, signature only, separate rate limit).
6. Admin: Payments listing/show (events timeline), Gateway webhook events (failures highlighted, manual re-process with reason),
   Refunds (finance permission, reason, dual approval above threshold setting) posting reversals through the ledger.
7. Reconciliation command payments:reconcile → mismatches report in admin.

Tests: webhook signature invalid/replayed/duplicate, out-of-order events, Flutter callback alone never marks paid, refund ledger
balance, dual approval enforcement.
```

#### P3.3 — Coupon engine and coupon manager
**Depends on:** P3.2 · **Priority:** P1

```text
MILESTONE P3.3 — Coupons & promotions.
Read: SRS §11.1, §11.2, §16.8, §6.4 step 5, §20.5 (coupons); Plan I7, ADR-17.

1. Schema: coupons (code nullable for automatic campaigns, title/description/terms translatable, discount_type, value, max_discount,
   min_fare, per_customer_limit, global_limit, budget_minor, spent_minor, used_count, reserved_count, funding_source + split json,
   stacking flags, excluded items, segments json (new/returning), scope pivots: service families/categories/vehicle categories/cities/
   zones/payment methods, schedule json, status CouponStatus, starts_at/ends_at with timezone, version, lock_version) ;
   coupon_reservations ; coupon_redemptions ; coupon_attempts (result enum, reason code) ; promotion_funding_entries.
2. CouponEligibilityService (pure, unit-testable, returns discount Money + explanation code), CouponReservationService (lock coupon row;
   caps/budget checked in lock; reserve at booking create; release on cancel/expire; commit at completion; reverse on refund per policy),
   ledger postings for funding (platform promo_expense / provider / partner).
3. Live coupon edits: changes to financial terms of an active coupon create a new version; existing reservations/redemptions keep
   their version.
4. API: POST /coupons/validate (quote for a prospective booking), GET /me/coupons; booking create accepts coupon_code (server
   computes everything; client discount ignored).
5. Admin (contract): Coupons listing (filters Appendix C) + create/edit with eligibility builder, preview text, "test eligibility"
   tool (pick customer + category + fare → result + reason), pause/resume/archive (reason), redemptions & attempts tabs,
   performance tab (uses, spend, budget burn), export (permission + audit). Scheduled status transitions command
   (scheduled→active→expired/exhausted).
6. Seed coupons: percentage, fixed, new customer, service-restricted, expired.

Tests: each eligibility rule, concurrency caps (parallel reservations never exceed global/per-user/budget), release/commit/reverse,
version isolation, client-sent discount ignored.
```

#### P3.4 — Provider earnings, payouts, adjustments, cash reconciliation
**Depends on:** P3.3 · **Priority:** P1

```text
MILESTONE P3.4 — Earnings & payouts.
Read: SRS §7.6, §12.3 (payout rules), §16.9, §20.4 (earnings/payouts).

1. Schema: provider_payout_methods (type, details encrypted, verified_at), payout_requests (amount, fee, status PayoutStatus,
   reserved journal entry, reviewer(s), transfer reference, evidence path, rejection reason), payout_batches, payout_events,
   financial_adjustment_requests (target account, amount, direction, reason, evidence, requested_by, approved_by, status ApprovalStatus),
   cash_reconciliations (provider, period, cash collected, commission due, settled amount, status).
2. PayoutService: request (min/max/fee settings, available balance = balance − reservations; reservation journal), review/approve
   (dual approval above threshold; approver ≠ requester), mark processing/paid/failed (reversal on failure), batches export.
   AdjustmentService: request → approve (second admin, wallets.adjust permission) → post ledger entry; never edits existing entries.
   CashReconciliationService: provider settles cash commission debt (payment from provider or deduction) with ledger postings.
3. API: GET /provider/earnings/summary (day/week/month, by category), GET /provider/earnings/transactions (booking-level breakdown),
   GET/POST /provider/payouts, payout methods CRUD, GET /me/wallet, GET /me/wallet/transactions (customer).
4. Admin (contract): Payout requests queue + review screen, Payout batches, Adjustment requests (maker-checker UI), Cash reconciliation,
   provider statements export (permission + audit).

Tests: double payout prevented (concurrent requests), dual approval, failed payout reversal, adjustment requires second approver,
statement totals equal ledger sums.
```

#### P3.5 — Cancellation/no-show policies with fees, fare adjustments, receipts
**Depends on:** P3.4 · **Priority:** P1

```text
MILESTONE P3.5 — Policies with money.
Read: SRS §8.4, §9.4, §6.7 (receipt), §7.5 (waiting); Plan §7.4.

1. Extend cancellation policies: fee formulas per stage/actor/time since confirmation/arrival, recipient of fee (provider/platform split),
   no-show flows (customer no-show after free wait at pickup with GPS + time evidence; provider no-show reported by customer), waiting
   charges (free wait, per-minute rate or negotiated adjustment), abuse cooldowns. Fee postings via ledger (JournalEntryType
   cancellation_fee); cash fees become wallet debts. Appeal path creates a support ticket (P3.7 link).
2. booking_adjustment_requests: requester (provider/customer), type (extra stop, waiting, toll/parking at cost with evidence, scope
   change), old/new amounts, reason, evidence, status; other party must approve in-app; approved adjustments enter the breakdown;
   POST /bookings/{booking}/adjustments + approve/reject endpoints for both apps; realtime events.
3. Receipts: receipt snapshot at completion (immutable), GET /bookings/{booking}/receipt (JSON) + PDF/HTML receipt (localized, ar RTL),
   provider job receipt.
4. Admin: cancellation policy editor (preview calculator), adjustments listing, receipts visible on booking show.

Tests: fee matrix per stage/actor, no fee without evidence when policy requires, adjustment requires counterpart approval, receipts
immutable after policy changes.
```

#### P3.6 — Notifications platform (push, SMS, email, in-app inbox)
**Depends on:** P3.5 · **Priority:** P1

```text
MILESTONE P3.6 — Notifications.
Read: SRS §14.1, §21 (push content), §22; Plan ADR-12.

1. Schema: notification_templates (event key, channel, locale content with variables, active, version), notification_preferences
   (user, category, channel, enabled; essential categories locked), notification_deliveries (channel, provider id, status, error,
   attempts), in-app notifications (Laravel database notifications table or custom inbox).
2. NotificationService: event → template resolution (user locale, fallback ar) → channels allowed by preference/essential rule →
   queued delivery with retry + dedupe key → delivery status. PushNotifier contract (FCM HTTP v1 driver; `log` driver local),
   SmsGateway reused, Mailer. Minimal lock-screen content; deep-link payloads.
3. Wire every event listed in SRS §14.1 (OTP excluded from logs) — booking, offers, documents, payments, coupons, payouts, support.
4. API: GET /me/notifications (inbox), POST /me/notifications/{id}/read, GET/PUT /me/notification-preferences.
5. Admin: Notification templates (contract; preview with sample variables in ar/en), Delivery log listing (filters), optional push
   campaign module (marketing permission; respects consent + preferences; scheduled; audit).
6. Flutter (both apps): firebase_core + firebase_messaging + flutter_local_notifications, token registration via /me/devices,
   foreground/background handling, tap → deep link to booking/ticket with expired/invalid-state handling, inbox screen, preferences
   screen explaining essential notifications. Firebase config files stay out of git (documented).

Tests: template fallback, preference enforcement + essential override, dedupe, retry, deep-link routing widget tests.
```

#### P3.7 — Booking chat and support tickets/disputes
**Depends on:** P3.6 · **Priority:** P1

```text
MILESTONE P3.7 — Messaging & support.
Read: SRS §14.2, §14.3, §16.11; Plan §5.6.

1. Chat: conversations (booking-scoped), participants, messages (text, optional image attachment validated + scanned + size limits),
   read receipts; allowed only between customer and selected provider during configured window; retention job; staff access audited.
   API GET/POST /bookings/{booking}/messages; realtime booking.message_received.
2. Support: support_tickets (number, user, category TicketCategory, priority, status, booking/payment/payout links, assigned group/agent,
   SLA due_at), ticket_messages (visibility public/internal), ticket_attachments (private), ticket_events. Migrate P2.5 booking_reports
   into tickets. TicketService (assignment rules, SLA calc, escalation to finance/safety/operations, status transitions with audit +
   notifications). Refund/adjustment from a ticket only links to the finance flows (P3.2/P3.4) with finance permission.
3. API: GET/POST /support/tickets, GET /support/tickets/{ticket}, POST /support/tickets/{ticket}/messages (both apps).
4. Admin: Ticket inbox (contract; filters Appendix C; "my tickets"/unassigned quick filters), ticket screen (thread with internal notes
   visually distinct, macros/response templates ar/en module, assign, escalate, resolve with reason, linked booking panel, linked
   financial operations), Macros module. Support agents only see assigned/authorized tickets (policy).
5. Flutter (both apps): chat screen (realtime, optimistic send with retry, attachment), help center entry, tickets list/detail/reply.

Tests: chat authorization (non-participant 403, after window closed), attachment validation, ticket visibility policy, internal notes
never returned by user API, escalation audit.
```

#### P3.8 — Flutter: wallet, payments, coupons, earnings, payouts
**Depends on:** P3.7 · **Priority:** P1

```text
MILESTONE P3.8 — Money features in the apps.
Read: SRS §6.7, §6.8, §7.6, §27.3.

Customer app: Wallet tab (balance, transactions, refunds), payment methods (gateway setup flow via adapter, delete), payment at
completion (intent → gateway UI/webview → wait for server status via realtime/polling; recoverable failure state), tips (not
preselected), Offers tab (my coupons, apply coupon in booking form with server quote and explanation), receipt with full breakdown,
adjustment approval sheet.
Driver app: earnings dashboard (day/week/month, by category), booking-level breakdown, wallet ledger, cash-debt banner with
eligibility impact, payout methods, payout request + history/status, adjustment requests (create/approve), incentives placeholder
hidden until P5.7.
Remove feature flags hiding these tabs. Widget + integration tests for payment failure recovery, coupon invalid, payout states.
```

---

### PHASE 4 — Courier, Furniture Moving, Freight

#### P4.1 — Courier/parcel backend
**Depends on:** P3.8 · **Priority:** P1

```text
MILESTONE P4.1 — Courier.
Read: SRS §5.1-C, §6.6 (delivery part), §7.5 (proofs), §8.1 (delivery_attempt_failed), §20.4 (proofs/attempts), §27.2.

1. booking_contacts (pickup/dropoff contact name/phone encrypted, role sender/recipient), recipient OTP (generated at pickup collected,
   sent to recipient via SMS adapter, hashed, attempts), custody events (picked_up, in_transit, arrived_dropoff, delivered, attempt_failed,
   returned) appended to booking timeline, restricted goods acknowledgement (policy version) required at create.
2. Proofs fully implemented: POST /provider/bookings/{booking}/proofs (photo/signature/checklist/weight with validation, hashing, private
   storage), POST /provider/bookings/{booking}/delivery-attempts (outcome enum, reason, evidence) → delivery_attempt_failed; retry or
   return-to-sender/operations flows. Completion guard checks category delivery_proof_types + recipient_otp_required.
3. Category-specific searchable columns (package size, weight, declared value, fragile) indexed for admin filters.

Tests: cannot complete without required proofs/OTP, attempt failed path, recipient OTP attempts, custody timeline order, privacy of
recipient phone.
```

#### P4.2 — Furniture moving & freight backend (scoped offers)
**Depends on:** P4.1 · **Priority:** P1

```text
MILESTONE P4.2 — Moving & freight.
Read: SRS §5.1-D, §5.1-E, §7.4 (structured scope), §9.4; Plan §2.3.

1. Structured offer scope (DTO + JSON schema validated): vehicle class, helpers count, packing, stairs/floors handling, assembly/
   disassembly, loading equipment, waiting allowance, extra stops, tolls/parking treatment (included / at cost with evidence / excluded),
   exclusions free text. Counter-offers in moving/freight categories REQUIRE scope; accept-customer-fare uses the customer's requested
   scope. Accepted scope stored in the agreement snapshot; shown identically to both sides.
2. Moving: booking_items (structured checklist from schema + free text, dimensions/volume/weight, photos/videos attachments with limits),
   floors/elevator/walking distance/parking fields from schema; freight: cargo type, weight/dimensions vs approved vehicle capacity
   validation (dispatch filters by capacity; offers rejected if vehicle capacity insufficient), pickup window, multiple stops with
   contacts, documents, goods value declaration.
3. Post-confirmation changes only through adjustment requests (P3.5) referencing scope items.

Tests: counter-offer without scope rejected for moving, capacity filter in dispatch, scope immutability, adjustment flow for extra
helper/stairs.
```

#### P4.3 — Flutter courier/moving/freight flows
**Depends on:** P4.2 · **Priority:** P1

```text
MILESTONE P4.3 — Apps for non-ride services.
Customer app: courier form (contacts, package details, restricted goods acknowledgement, photos), moving form (items checklist,
floors, elevator, helpers, photos/video), freight form — all through DynamicFormRenderer + dedicated widgets for contacts, items and
dimensions; offer cards showing structured scope with clear included/excluded items; delivery timeline with custody events,
recipient OTP status, proof viewer; failed attempt handling.
Driver app: scope builder for counter-offers (chips/steppers, large controls), pickup proof capture (camera, retry upload queue
offline-safe), recipient OTP entry, delivery attempt failed flow with reasons + evidence, multi-stop navigation list.
Tests: scope builder validation, proof upload retry, OTP flow, golden tests for scope offer card in RTL.
```

#### P4.4 — Admin & E2E for non-ride services
**Depends on:** P4.3 · **Priority:** P1

```text
MILESTONE P4.4 — Ops for courier/moving/freight.
Admin: booking show gets contacts (masked + audited reveal), items, scope comparison (requested vs accepted), custody timeline, proofs
gallery (signed URLs, audited), attempts; listing filters for package/cargo attributes. Category form-schema previews updated.
E2E Feature tests: (1) parcel booking with recipient OTP + photo proof + one failed attempt then success; (2) furniture moving with
counter-offer scope (2 helpers, stairs) → selection → completion with adjustment for an extra stop. Update status report.
```

---

### PHASE 5 — Full Operations & Public Website

#### P5.1 — Safety center, SOS, trusted contacts
**Depends on:** P4.4 · **Priority:** P1

```text
MILESTONE P5.1 — Safety.
Read: SRS §13 (all), §16.10, §20.5 (incidents).
1. trusted_contacts (user, name, phone encrypted, consent flags), safety_incidents (booking, reporter, category, severity, status,
   location snapshot, narrative, priority, assigned officer, SLA), incident_events, incident_attachments (restricted), trusted-contact
   action records, legal hold flag.
2. POST /safety/incidents (SOS from active job: captures booking, time, actor, GPS, callback info, category) — rate limited but never
   blocks urgent first report; notifies safety team (admin realtime + notification), optional trusted-contact share per consent; response
   tells the user exactly what happened and shows local emergency numbers (no claim of automatic emergency dispatch).
3. Admin Safety Center (contract): urgent queue with SLA timers, incident screen (timeline, linked booking/users, location snapshot map,
   attachments with audited access, staff notes, escalation, resolution), restricted export (incidents.export_restricted, audit).
4. Apps: SOS hold-to-activate button on active job (customer + driver), incident report form, trusted contacts management, share trip
   to trusted contacts.
Tests: access separation (support agent cannot open incident evidence), every evidence view audited, SOS works under rate limit.
```

#### P5.2 — Reviews moderation & risk signals
**Depends on:** P5.1 · **Priority:** P1

```text
MILESTONE P5.2 — Reputation & abuse.
Read: SRS §15, §16.12.
rating_reports, moderation_actions (hide/restore with reason, audit), appeals; risk_signals (type: cancellation_rate, no_show,
offer_spam, location_anomaly, referral_abuse, account_farm, payment_dispute; subject; score; evidence; status) computed by scheduled
jobs with explainable evidence, never public, never auto-permanent-suspension — creates review tasks. Admin: Reviews listing +
moderation, Risk signals queue with "open provider/customer" and action shortcuts (each with reason). Rating formula documented and
exclude hidden reviews.
Tests: hidden reviews excluded from aggregate, no admin edit of review text, risk job idempotent.
```

#### P5.3 — Operations dashboard, reports, exports
**Depends on:** P5.2 · **Priority:** P1

```text
MILESTONE P5.3 — Analytics.
Read: SRS §16.1, §16.14; dataviz best practices (consistent colours, accessible, ar/en).
1. ReportingRepository + aggregation jobs into daily_metrics tables (by city/zone/category/date in market timezone) for heavy KPIs; live
   counters for operational ones; every KPI has a definition (numerator/denominator/timezone/freshness) shown via info tooltip.
2. Dashboard (permission-aware widgets): KPIs and time series per SRS §16.1 with date range + timezone filter, drill-down links to
   filtered listings (filters pre-applied via ?filters[] querystring), data freshness indicator. Chart.js components wrapped as Blade
   components (`x-admin.chart.line|bar|donut`) with RTL-aware axes.
3. Reports pages (SRS §16.14 list) each = filters card (collapsed) + chart + Yajra table + export (queued CSV, reports.export permission,
   audit, PII columns only with elevated permission).
Tests: KPI calculations against seeded fixtures, permission per widget, export audit.
```

#### P5.4 — CMS
**Depends on:** P5.3 · **Priority:** P1

```text
MILESTONE P5.4 — Content management.
Read: SRS §16.13, §17.2.
Modules (contract): Pages (blocks-based homepage sections + generic pages), Service pages (linked to service categories), City pages
(linked to cities), FAQs (categories), Articles/Help center, Banners/announcements (app + web placements), Menus/footer, App links,
Media library (public disk, image validation, resize), SEO fields per entity (title, description, OG, canonical, noindex). Workflow:
draft → in_review → scheduled/published → archived with roles (content.manage, content.publish), preview, sanitized rich text
(HTML purifier allowlist) or Markdown, ar required / en optional with fallback, publish scheduling job, revision history.
API: GET /content/banners, /content/faqs, /content/pages/{slug} for apps (help center inside apps).
Tests: sanitization (XSS payloads stripped), workflow permissions, scheduled publish.
```

#### P5.5 — Public website
**Depends on:** P5.4 · **Priority:** P1

```text
MILESTONE P5.5 — Website.
Read: SRS §17 (all), §18.1, §26.
Separate website layout + Tailwind entry, original brand. Routes /{locale?}/… with ar default (no prefix or /ar per decision),
localized slugs, hreflang, canonical, sitemap.xml (dynamic from CMS), robots.txt, Open Graph, JSON-LD (Organization, FAQPage where valid).
Pages: Home (propose-a-fare model explained), Customer app, Become a provider (lead form → onboarding lead record + notification;
no document uploads publicly), each service page, How it works (customer + provider), Safety (only real safeguards), Cities, FAQ/help,
Contact (validated, throttled, honeypot + time-trap or captcha adapter, creates support ticket + reference number), About, legal pages
(from policy_versions), app download with QR codes, blog/articles. Forms use the same AJAX + precognition pattern (website variant of
ajax-form, no admin dependencies). Lighthouse-minded: optimized images, no layout shift, accessible forms.
Admin: Leads module (contract). Tests: localized metadata present, forms validated/throttled, sitemap entries, RTL rendering.
```

#### P5.6 — Integration health, app versions, feature flags, system pages
**Depends on:** P5.5 · **Priority:** P1

```text
MILESTONE P5.6 — System operations.
Read: SRS §16.15, §23.2, §23.4.
Integration health page: DB, Redis, queue depth/failed jobs (with retry action, permission), scheduler heartbeat age, Reverb health,
storage, maps/SMS/payment/push adapter status (configured / not configured / failing + last error, no secrets), webhook failures.
App versions module (min version, force update, store URLs per platform/app) feeding /config/app. Feature flags module (key enum,
targeting by app/city/percentage) feeding apps and backend checks. Translations management for DB-driven strings (translations.manage)
if any remain outside lang files. Structured JSON logging with correlation ids; optional error tracker adapter (Sentry DSN env).
Tests: health checks with failing fakes, flags evaluation.
```

#### P5.7 — Referrals, loyalty, provider incentives (P2 scope)
**Depends on:** P5.6 · **Priority:** P2

```text
MILESTONE P5.7 — Growth features.
Read: SRS §11.3, §7.6 (incentives).
Referral codes/links (customer + provider campaigns), relationships, milestones, rewards via ledger (credits with expiry), fraud checks
(device/phone/payment patterns → manual review queue, no irreversible automatic punishment). Provider incentive campaigns (eligibility,
budget, start/end, caps, quest progress) with ledger posting and admin modules (contract). App screens: referral share, credits,
incentive progress. If time-boxed out, ensure NO UI exposes these features and status doc says so.
Tests: reward posting balanced, caps/budget, fraud flags create review tasks.
```

---

### PHASE 6 — Production Hardening & Release

#### P6.1 — Security, IDOR, race & webhook review
**Depends on:** P5.7 (or P5.6 if P5.7 deferred) · **Priority:** P0

```text
MILESTONE P6.1 — Security review.
Read: SRS §13, §20.7, §23.3, §27.2, §31 (technical quality).
1. Generate a route inventory (api, admin, website, channels, webhooks). For each: auth guard, ability, policy, rate limit,
   idempotency (if critical), resource disclosure level. Fix gaps; add an automated test asserting every non-public route has auth
   + authorization (extend the P1.1 guard test to API routes).
2. IDOR test matrix: for each resource type, user A vs B (customer/provider/agent) → 403/404.
3. Re-run and extend concurrency suite: accept/accept, offer/expiry, coupon caps, payout double request, webhook duplicates, ledger
   parallel postings (no deadlocks, balances reconcile).
4. Headers (CSP for admin/website, HSTS, frame-ancestors, referrer policy), CORS strict, cookie flags, session fixation, admin IP
   allowlist option, MFA enforcement check, file upload hardening, signed URL expiry, secrets scan (gitleaks or similar) over history,
   `composer audit`, `npm audit`, `flutter pub outdated` review, logs scan for PII/OTP/location.
5. Write docs/SECURITY_REVIEW.md with findings, fixes, residual risks.
```

#### P6.2 — Performance, indexes, retention
**Depends on:** P6.1 · **Priority:** P0

```text
MILESTONE P6.2 — Performance & data lifecycle.
1. Seed a realistic volume (e.g. 200k bookings, 2M location samples, 5k providers) in a perf DB; EXPLAIN hot queries (dispatch,
   available requests, offers, admin listings with each filter, dashboard aggregates); add/adjust indexes; ensure all DataTables use
   selective columns + indexes; cache catalog/config with invalidation.
2. Load-test baseline (k6 or similar scripts in /loadtest): booking create, offers, accept, location ingest at target rates; record
   real numbers (no invented claims) in docs/PERFORMANCE.md.
3. Retention jobs per DECISIONS: location samples, chat, share tokens, idempotency keys, OTP challenges, webhook payloads, with legal
   hold exceptions; archive strategy for bookings/ledger (never delete ledger).
4. Queue tuning: workers per queue, timeouts, retries/backoff, failed job alerts; Reverb scaling notes.
```

#### P6.3 — Localization, RTL & accessibility review
**Depends on:** P6.2 · **Priority:** P0

```text
MILESTONE P6.3 — Arabic-first quality pass.
1. Missing translation scanner for Laravel (keys used vs lang files ar/en) and Flutter (ARB parity) — CI-failing.
2. Visual review of every admin screen, website page and app screen in ar (RTL) and en (LTR) on small phone / large phone / tablet /
   desktop: fix overflow, truncation, mirrored icons (only directional ones), mixed-direction text (plates, phones, addresses) with
   proper bidi isolation, Arabic-Indic digits preference.
3. Accessibility: semantics labels for map actions and fares/statuses, contrast check of tokens, text scaling 200% in apps, keyboard
   navigation + focus states in admin/website, status never colour-only (badges include text/icon).
4. Golden tests updated; record review results in docs/L10N_A11Y_REVIEW.md.
```

#### P6.4 — CI, deployment, backups, release report
**Depends on:** P6.3 · **Priority:** P0

```text
MILESTONE P6.4 — Release readiness.
Read: SRS §29, §31, §33 (final execution rules).
1. CI pipeline (GitHub Actions or GitLab CI — document both if remote unknown): backend (composer install, pint --test, phpstan,
   tests on MySQL + Redis services, npm build, Vitest), Flutter (format, analyze, test, build apk debug for both apps; iOS build
   documented for macOS runners), secrets scan, translation parity check.
2. docs/DEPLOYMENT.md: server requirements (PHP extensions, MySQL, Redis, Node for build), nginx config with TLS, separate admin
   path/subdomain option, supervisor configs (queue workers per queue, Reverb), cron for scheduler, storage (private disk on S3-compatible),
   env per environment (local/staging/production), zero-downtime deploy steps, migrations compatibility & rollback, mobile API base
   URLs per flavor, Firebase/APNs setup, Android signing & iOS provisioning outside git.
3. docs/OPERATIONS.md: backups (automated encrypted DB + private storage backups, retention, restore procedure — perform one test restore
   locally and record it), monitoring/alerts, failed job runbook, webhook failure runbook, incident response.
4. Production readiness checklist + external credentials checklist (SMS, maps, payment gateway, FCM/APNs, mail, storage, Sentry).
5. Final verification: run every check in the preamble across all projects; inspect `git diff main...` for secrets, debug bypasses,
   dead routes, untranslated text, unprotected endpoints, placeholder UI.
6. docs/RELEASE_REPORT.md: completed / partially completed / not implemented (with reasons + next steps), exact commands and results,
   env vars and credentials needed, deployment steps. Truthful — nothing claimed that wasn't verified.
```

---

## 11. Quality Gates & Definition of Done

### 11.1 Per-milestone gate (all must hold)

- [ ] `vendor/bin/pint --test` clean · `vendor/bin/phpstan analyse` clean · `php artisan test --parallel` green
- [ ] `npm run build` succeeds · Vitest green (from P0.3)
- [ ] `flutter analyze` clean · `flutter test` green in each touched app/package · `dart format` clean
- [ ] New endpoints documented in `docs/API.md`; new env vars in `.env.example` + `docs/INTEGRATIONS.md`
- [ ] `docs/IMPLEMENTATION_STATUS.md` updated with real command output summaries
- [ ] No hard-coded user-visible strings; ar + en present; RTL checked
- [ ] No dead buttons or fake success; incomplete features hidden, not faked

### 11.2 Feature Definition of Done (SRS §0)

Permission rules · validation (server, plus Precognition in admin) · DB behaviour (migrations, indexes, FKs, snapshots) · error handling with stable codes · audit/logging · Arabic and English labels · RTL · automated tests at the right layers · documentation.

### 11.3 Code-review checklist (self-review before closing a milestone)

- Controller has ≤ ~15 lines per action and calls exactly one service method?
- Any `Model::query()`/`DB::table()` outside a repository? (should be none, except migrations/seeders)
- Every status/type a PHP enum, cast on the model, mirrored in Flutter with `unknown` fallback?
- Money anywhere as float? (should be none)
- Transactions around multi-write operations; locks where races exist; idempotency on critical endpoints?
- Resources expose only stage-appropriate fields?
- Every admin listing: Yajra + filters card collapsed by default + permission-aware actions?
- Every admin form: `<x-admin.form>` with Precognition validation before submit?

---

## 12. Appendices

### Appendix A — `AdminPermission` catalog (enum values)

```text
dashboard.view
admins.view admins.manage roles.view roles.manage audit_logs.view login_activity.view
settings.view settings.manage markets.manage policies.manage policies.publish
customers.view customers.view_pii customers.update customers.suspend customers.export customers.process_deletion
drivers.view drivers.view_pii drivers.verify drivers.reject drivers.suspend documents.view documents.manage_types
vehicles.view vehicles.approve vehicles.reject
services.manage vehicle_categories.manage service_areas.manage fare_policies.manage commission_rules.manage cancellation_policies.manage
bookings.view bookings.manage bookings.cancel bookings.assign_override bookings.pin_override bookings.view_pii
offers.view offers.moderate
coupons.view coupons.manage coupons.export campaigns.manage
finance.view finance.reconcile payments.view refunds.request refunds.approve
payouts.view payouts.review payouts.approve wallets.view wallets.adjust wallets.approve_adjustment ledger.view
incidents.view incidents.manage incidents.view_evidence incidents.export_restricted
support.view support.manage support.view_all macros.manage
reviews.view reviews.moderate risk.view risk.manage
content.manage content.publish translations.manage leads.view
notifications.manage push_campaigns.manage
reports.view reports.export
system.health system.feature_flags system.app_versions system.queues
```

### Appendix B — Role → permission mapping (seeded defaults; editable in admin)

| Role | Permissions (summary) |
|---|---|
| Super Admin | all (Gate::before) |
| Operations Admin | dashboard, customers.* (no pii export), drivers.view/suspend, vehicles.view, bookings.*, offers.*, services/areas/policies manage, support.view, incidents.view, reports.view |
| Dispatch/Live Ops Agent | dashboard, bookings.view/manage/cancel, offers.view, drivers.view, customers.view |
| Driver Verification Officer | drivers.view/verify/reject, documents.view, vehicles.view/approve/reject |
| Customer Support Agent | customers.view, bookings.view, support.view/manage, macros.manage, refunds.request |
| Safety & Incident Officer | incidents.*, bookings.view/view_pii, customers.view_pii, drivers.view_pii |
| Finance Manager | finance.*, payments.view, refunds.approve, payouts.*, wallets.*, ledger.view, commission_rules.manage, reports.view/export |
| Finance Payout Officer | payouts.view/review, wallets.view, ledger.view |
| Marketing/Coupon Manager | coupons.*, campaigns.manage, push_campaigns.manage, reports.view |
| Content/CMS Editor | content.manage, translations.manage, leads.view (content.publish only if granted) |
| Analyst/Read-only Auditor | dashboard.view, *.view (no pii), reports.view, audit_logs.view |
| System/Integration Admin | settings.*, system.*, notifications.manage, admins.view |

Dual control applies to: wallets.adjust (second admin with wallets.approve_adjustment), refunds above threshold, payouts above threshold, policy publish (optional).

### Appendix C — Listing filters (each inside a collapsed `<x-admin.filters-card>`)

| Listing | Filters |
|---|---|
| Admin users | name/email, role, status, MFA enabled, created range |
| Audit logs | actor, action (enum), target type, target id, date range, has reason |
| Customers | uuid/phone/name, status, registered range, city, has active booking, deletion requested |
| Providers | uuid/phone/name, status, provider type, service category, city, online now, document expiring within N days |
| Verification queue | status (under_review / more_info), provider type, city, waiting time > X, assigned officer |
| Vehicles | plate, vehicle category, status, provider |
| Service categories | family, active, booking type, negotiation enabled |
| Zones | city, status, category availability |
| Bookings | code, statuses (multi), service category, booking type, city/zone, payment status, payment method, date range, customer phone, provider phone, has incident, has ticket |
| Offers | booking code, provider, status, kind, amount range, anomaly flag, date range |
| Coupons | code, status, discount type, funding source, service family, active on date, date range |
| Payments | reference, booking code, status, method, gateway, amount range, date range |
| Journal entries | type, reference, account, date range, reversal only |
| Payout requests | status, provider, amount range, requested range, approver |
| Tickets | number, status, priority, category, assigned agent/group, SLA breached, linked booking, date range |
| Incidents | severity, status, category, assigned officer, SLA breached, date range |
| Reviews | stars range, moderation status, reported only, ratee type, date range |
| CMS pages/articles | type, status, locale completeness, updated range |
| Notification deliveries | channel, status, event key, date range |
| Leads | city, service interest, status, date range |

### Appendix D — Admin navigation map

```text
Dashboard
Operations ........ Live map · Bookings · No-offer queue · Offers monitor
People ............ Customers · Providers · Verification queue · Vehicles
Catalog ........... Service families · Service categories · Vehicle categories · Document types & requirements
Geography ......... Countries · Cities · Service zones · Coverage preview
Pricing ........... Fare policies · Commission rules · Cancellation policies
Marketing ......... Coupons · Referral campaigns · Incentives · Banners · Push campaigns
Finance ........... Payments · Gateway events · Refunds · Ledger · Wallets · Payouts · Adjustments · Cash reconciliation · Reconciliation reports
Support ........... Tickets · Macros
Safety ............ Incidents · Risk signals
Moderation ........ Reviews
Content ........... Pages · Service pages · City pages · FAQs · Articles · Menus · Media · Leads · Policy versions
Reports ........... Funnel · Supply/demand · Fares · Cancellations · Coupons ROI · Earnings · Payout ageing · SLA · Safety · Retention
System ............ Settings · Admin users · Roles · Audit logs · Login activity · Notification templates · App versions · Feature flags · Integration health
```

### Appendix E — Key artisan commands & scheduled jobs

| Command / Job | Schedule | Purpose |
|---|---|---|
| `offers:expire` | every 5s | backstop for offer expiry |
| `bookings:expire-searches` | every 5s | backstop for search expiry |
| `providers:auto-offline-stale` | every minute | stale location → offline |
| `documents:check-expiry` | daily | expire docs, notify, recompute eligibility |
| `coupons:sync-statuses` | every minute | scheduled/active/expired/exhausted |
| `payments:reconcile` | hourly | gateway vs local |
| `ledger:reconcile` | daily | cached balances vs lines |
| `metrics:aggregate-daily` | hourly + nightly | dashboard/reports |
| `retention:prune` | daily | location/chat/tokens/idempotency per policy |
| `system:heartbeat` | every minute | health page |
| `make:admin-module` | manual | admin module generator |
| `qa:race-accept`, `qa:race-offer` | manual | concurrency proofs |

---

**End of plan.** Start with **P0.1**: paste the Session Preamble (§9), then the P0.1 prompt.
