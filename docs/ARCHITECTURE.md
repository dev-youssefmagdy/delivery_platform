# Architecture

> Target architecture per Plan §3–§7. Items marked *(planned, Px.y)* do not exist in code yet; this document is updated as
> milestones land. Current real state: see `IMPLEMENTATION_STATUS.md`.

## 1. System context

```text
 ┌──────────────┐   ┌──────────────┐        ┌──────────────────────────┐
 │ customer_app │   │  driver_app  │        │ Browser: admin / website │
 │  (Flutter)   │   │  (Flutter)   │        │  Blade + Tailwind + Alpine│
 └──────┬───────┘   └──────┬───────┘        └─────────────┬────────────┘
        │ HTTPS JSON /api/v1 (Sanctum)                    │ HTTPS session (admin guard / web)
        │ WSS Reverb (private channels)                   │ AJAX + Precognition, Echo (live ops)
        ▼                  ▼                              ▼
 ┌─────────────────────────────────────────────────────────────────────┐
 │                    backend/ — Laravel 13 modular monolith            │
 │  routes: api_v1.php · admin.php · website.php · channels.php         │
 │  Http → FormRequest → DTO → Service → Repository → Model             │
 │  Jobs/Queues (critical, default, notifications, low) · Scheduler     │
 │  Integrations (adapters): SMS, Maps, Payments, Push, Relay, Scanner  │
 └───────┬──────────────────────┬──────────────────────┬───────────────┘
         ▼                      ▼                      ▼
   MySQL 8 (InnoDB,       Redis 7 (cache, queues,   Object storage (private
   utf8mb4, spatial)      sessions, locks, GEO)     disk / public CMS disk)
```

## 2. Module map (SRS §2.2)

| Module (namespace `<Module>`) | Responsibility | First milestone |
|---|---|---|
| Admin / Audit / Access | admins, roles, permissions, MFA, audit log | P1.1 |
| Settings / Markets / Locales / Policies | typed settings, currencies, policy versions, app config | P1.2 |
| Identity | users, OTP, devices, consents, deletion | P1.3 |
| Catalog | service families/categories, vehicle categories, form schemas | P1.4 |
| Geo | cities, service zones, maps adapter | P1.5 |
| Customer | customer profiles + admin module | P1.6 |
| Provider | onboarding, vehicles, documents, verification | P2.1 |
| Dispatch / Availability | eligibility, live location, GEO index, waves | P2.2 |
| Booking / Offer | booking creation, pricing reference, offers, selection, lifecycle | P2.3–P2.5 |
| Finance (Ledger, Wallet, Commission, Payout) | double-entry ledger, wallets, payouts | P3.1, P3.4 |
| Payment | gateway adapter, cash, webhooks, tips | P3.2 |
| Coupon | coupon engine, reservations | P3.3 |
| Notification | push/SMS/email/inbox | P3.6 |
| Support / Chat | booking chat, tickets, disputes | P3.7 |
| Delivery (courier/moving/freight) | contacts, proofs, scoped offers | P4.1–P4.2 |
| Safety | SOS, incidents, trusted contacts | P5.1 |
| Review | ratings moderation, risk signals | P5.2 |
| Report | dashboards, exports | P5.3 |
| CMS / Website | pages, FAQs, public site | P5.4–P5.5 |
| System | integration health, app versions, feature flags | P5.6 |

## 3. Layering (binding, Plan §4.2)

```text
 HTTP request
   │
   ▼
 Middleware (SetLocale, CorrelationId, auth:sanctum / auth:admin, idempotent, throttle)
   │
   ▼
 Controller ──► FormRequest (authorize + validate + toDto())
   │                 │
   │                 ▼
   │            DTO (final readonly)
   ▼
 Service (business rules, DB::transaction, locks, events, audit, integrations)
   │
   ▼
 Repository interface ──► Eloquent repository (all queries, lockForUpdate)
   │
   ▼
 Model (casts: enums, Money, translatable; relations; scopes)
   │
   ▼
 Response: API Resource / ApiResponse envelope / Blade view
```

Rules: controllers call exactly one service method; no queries outside repositories (migrations/seeders excepted); enums for
every status/type; policies for object-level authorization; resources per audience/disclosure stage.

### 3.1 Implemented core (P0.2)

| Concern | Implementation |
|---|---|
| Route files | `routes/api_v1.php` (`api` group + `api/v1` prefix + `api.v1.` names), `routes/admin.php` (`web` + `HandlePrecognitiveRequests`, prefix `config('admin.path')` / `ADMIN_PATH`, names `admin.`), `routes/website.php` (`web`), `routes/channels.php`, `routes/console.php`; all wired in `bootstrap/app.php` |
| Middleware | Global: `CorrelationId` (first). `api` group: `ForceJsonResponse` (prepended), `throttle:api`, `SetLocale`. `web` group: `SetLocale` (after session). Aliases: `idempotent`, `locale`. Priority list puts `SetLocale` and `Idempotency` after `Authenticate` |
| Request context | `CorrelationId` puts `request_id` in Laravel `Context` (logged on every line, propagated to queued jobs) and `ip`/`user_agent` as *hidden* context (not logged) for the audit logger, so services never read `request()` |
| Envelope / errors | `App\Support\Http\ApiResponse` (only producer); `App\Exceptions\ApiExceptionRenderer` registered via `withExceptions()->render()` for `api/*` and `expectsJson()` requests; `DomainException` (→ `BusinessRuleViolation` 422, `ConflictException` 409, `ForbiddenActionException` 403) implements `HttpExceptionInterface` so HTML requests get normal status pages; domain exceptions are `dontReport` |
| Repositories | `BaseRepositoryInterface`/`BaseRepository` (generic `@template TModel`), bindings in `RepositoryServiceProvider::$bindings`; module folders `Repositories/{Contracts,Eloquent}/<Module>/` |
| Enums | `Enums/Contracts/{HasLabel,HasColor}`, `Enums/Concerns/EnumHelpers` (`values`, `options`, `labels`, `tryFromValue`, `fromLabel`, `inRule`), `Enums/Support/BadgeColor`, `Enums/Common/*` (`Locale`, `ActorType`, `Platform`, `AuditAction`, `QueueName`, `RateLimiterName`, `HealthStatus`); labels in `lang/{ar,en}/enums.php` |
| Money | `App\Support\Money\{Money, Currency, RoundingMode, MoneyCast, MoneyFormatter, CurrencyRegistry, DatabaseCurrencyRegistry}` — integer minor units, bcmath for fractional steps, one explicit rounding (default half-up), `allocate()` conserves every minor unit, formatter never converts to float |
| Audit | `admin_audit_logs` (append-only; model refuses update/delete; repository has only `append`), `AuditLogger::record()` / `recordChanges()`, `Support\Audit\Auditable` trait (`auditSnapshot()` minus hidden + `$auditExcluded`) |
| Polymorphism | `Relation::enforceMorphMap()` in `AppServiceProvider`: every polymorphic model needs a stable alias (`user`, `currency`, `country`, `admin_audit_log`, `idempotency_key`) |
| Integrations | `IntegrationServiceProvider` (area → contract → drivers map, empty) + `IntegrationGuard` refuses `fake`/`log` drivers outside `local`/`testing` at boot |
| Health | `HealthCheckService` → `HealthReport` DTO (statuses + private details); `GET /api/v1/health`; `system:heartbeat` every minute |

## 4. Request flow example — accept an offer (planned, P2.4)

1. `POST /api/v1/bookings/{booking}/offers/{offer}/accept` with `Idempotency-Key`.
2. Middleware: Sanctum ability `customer`, `idempotent` (replay stored response if key seen), correlation id.
3. `AcceptOfferRequest` authorizes via `BookingPolicy::selectOffer` (ownership) and builds `AcceptOfferData`.
4. `OfferSelectionService::accept()` in a transaction: `lockForUpdate` booking row, check status ∈ {searching_offers, offers_received}
   and `selected_offer_id IS NULL`, check offer `expires_at > now()` and status, conditional update, invalidate other offers,
   transition via `BookingTransitionService`, write `booking_status_events`, queue broadcasts after commit.
5. Loser of a race receives `409` with `errors._code = booking_already_assigned` and a fresh booking payload.

## 5. Realtime flow (planned, P2.2+)

```text
 Service (in DB transaction) ──► booking_status_events / outbox_events (sequence++)
         │ after commit
         ▼
 ShouldBroadcast event ──► Reverb ──► private-booking.{uuid} / private-customer.{uuid}
                                     private-provider.{uuid} / private-admin.operations
 Client: apply event if sequence == last+1, else refetch GET /bookings/{uuid} snapshot.
```

Channel authorization in `routes/channels.php` via policies. Push notifications (FCM/APNs) are sent for the same events when
the app is backgrounded.

## 6. Data stores

| Store | Use | Notes |
|---|---|---|
| MySQL 8 `delivery_platform` | system of record | InnoDB, `utf8mb4_unicode_ci`, POINT SRID 4326 + spatial indexes, JSON translatable columns |
| MySQL 8 `delivery_platform_testing` | test suite | ParaTest adds `_test_N` per process |
| Redis DB 0 | queues, sessions, locks, rate limits, GEO (`providers:online:{city}`) | prefix `delivery-platform-` |
| Redis DB 1 | cache | |
| Redis DB 15 | tests | prefix `delivery-platform-testing-` |
| Private disk | documents, proofs, attachments | signed routes + audit |
| Public disk | CMS images | |

## 7. Background processing

Queues (`App\Enums\Common\QueueName`): `critical` (expiry, dispatch waves), `default`, `notifications`, `low`; one worker
command consumes them in priority order: `php artisan queue:work redis --queue=critical,default,notifications,low`.
Scheduler (`routes/console.php`), implemented: `system:heartbeat` every minute (health page), `model:prune` hourly for
`idempotency_keys`. Planned: `offers:expire` and `bookings:expire-searches` every 5 s, `ledger:reconcile` nightly.
All jobs idempotent. Realtime: Laravel Reverb 1.12 installed (`php artisan reverb:start`), `BROADCAST_CONNECTION=reverb`.

## 8. Flutter architecture (planned, P0.4)

`packages/platform_core` (ApiClient on dio, envelope + error mapping, Money, enums with `unknown` fallback, design tokens,
shared widgets, realtime client, location abstraction). Apps are feature-first: Riverpod notifiers → repository contracts →
datasources → `ApiClient`. go_router, ARB l10n with `ar` default and `Directionality` from locale.
