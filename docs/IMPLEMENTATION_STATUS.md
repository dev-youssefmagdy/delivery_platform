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
| P1.1 | Admin authentication, roles/permissions, admin users, audit log UI | P0 | ✅ | 2026-10-19 |
| P1.2 | Settings, markets, locales, policy versions, app config API | P0 | ✅ | 2026-10-19 |
| P1.3 | Identity API: OTP, users, profiles, devices, consents, deletion | P0 | ✅ | 2026-10-20 |
| P1.4 | Service catalog, vehicle categories, form schemas, versioned snapshots | P0 | ✅ | 2026-10-20 |
| P1.5 | Geography: cities, service zones, availability, maps adapter | P0 | ✅ | 2026-10-21 |
| P1.6 | Customers admin module | P0 | ✅ | 2026-10-21 |
| P2.1 | Provider onboarding, vehicles, documents, verification | P0 | ✅ | 2026-10-22 |
| P2.2 | Availability, live location, eligibility, dispatch engine | P0 | ✅ | 2026-10-23 |
| P2.3 | Reference pricing & booking creation | P0 | ✅ | 2026-10-24 |
| P2.4 | Offers, counter-offers & atomic selection | P0 | 🟡 | 2026-10-25 |
| P2.5 | Trip lifecycle: heading, arrived, PIN, start, complete, cancellation v1, ratings, share link | P0 | 🟡 | 2026-10-26 |
| P2.6 | Customer app: complete ride flow | P0 | 🟡 | 2026-10-27 |
| P2.7 | Driver app: complete ride flow | P0 | 🟡 | 2026-10-28 |
| P2.8 | Admin: bookings, offers monitor, live operations v1 | P0 | 🟡 | 2026-10-29 |
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

## P2.8 — Admin: bookings, offers monitor, live operations v1 · 🟡 2026-10-29 (gate NOT run)

Committed directly on `main`. ADR-109..114. **By user instruction no test, pint, phpstan, `php artisan test`, vitest, browser smoke or `scripts/check.sh` was written or run, so the milestone is not marked done.** Every test that should exist is in `MISSED_TESTS.md` (P2.8 section). Sanity checks that did run: `php -l` on every new file, `route:list`, `php artisan view:cache` (all Blade compiles), `event:list` (the feed listener is discovered for its three events), container resolution of the new services, a read-only tinker run of every new repository query and DataTable JSON (including the offers `stats` payload and the anomaly SQL), a rolled-back tinker scenario (create bookings with the factory, run extend / re-dispatch / cancel / forbidden force / PIN lift / forced start, check audit rows and timeline events, render the show summary and tab views) and one `npm run build` (Vite compiled, 0 vulnerabilities from `npm install`).

### What was built (backend, admin panel)

- **Bookings** (`bookings.view`): Yajra listing with the Appendix C filters in a collapsed card (code, statuses, service, type, city, zone, payment status and method, created range, customer phone, provider phone, has incident; "has ticket" waits for P3.7), masked contacts, status/payment badges, a "needs support" flag, and a Live refresh switch (timer + operations feed). Show page: summary, parties, money, route map, status banners, tabs (timeline from `booking_status_events`, offers with ALL versions, dispatch recipients by wave, snapshots in the JSON viewer, PIN status without the PIN + proofs + reports, ratings, payment placeholder P3.2, audit).
- **Overrides** (`BookingOverrideService`, ADR-109): cancel by operations with an explicit fee decision, extend search, re-dispatch now, force status inside the transition graph, lift PIN lock (`bookings.pin_override`, mandatory reason, audit, parties notified through the log stub, apps get a status event). Forms are `<x-admin.form>` modal fragments with Precognition; none edits an offer version or a price; `BookingTransitionService` stays the only status writer.
- **No-offer queue:** searching bookings without active offers for 120 s and `expired_no_provider` of the last 24 h; retry / extend / cancel (searching) and contact customer (in-app message).
- **Live operations map:** Leaflet + markercluster, providers and bookings layers, tooltip with id/category/status only, updates from `private-admin.operations` through Echo/Reverb, realtime health indicator with last-event time, gap detection, snapshot polling fallback (ADR-112). New npm packages `laravel-echo`, `pusher-js`, `leaflet.markercluster`.
- **Offers monitor** (`offers.view`): filters per Appendix C, conversion and response tiles that refresh with each draw, anomaly flag (ADR-113).
- **Carry-over:** PIN-lock lift; booking ratings tab; customer Ratings tab and provider Jobs tab are real DataTables; provider Earnings tab shows gross completed-job totals (no commission or payouts until P3.4) with the honest placeholder underneath; the customer Bookings tab now links to the booking page; `GET /api/v1/provider/bookings` (paginated history, API.md 8.14).

### Files

Backend: `app/Services/Booking/{BookingOverrideService,BookingAdminService,BookingDetailTabsService,NoOfferQueueService,ProviderJobHistoryService}.php`, `app/Services/Operations/*`, `app/Services/Offers/OfferMonitorService.php`, `app/Listeners/Operations/PublishOperationsFeed.php`, `app/Events/Realtime/{OperationsFeedEvent,ProviderPositionRecorded}.php`, `app/DataTables/Admin/Booking/*`, `ProviderJobsDataTable`, `CustomerRatingsDataTable`, `app/Http/Controllers/Admin/Booking/*`, `Api/V1/Provider/JobHistoryController`, requests under `Http/Requests/Admin/Booking`, DTOs (`BookingAdminFilterData`, `OfferFilterData`, `BookingOverride*`, `BookingAdminDetails`, `OperationsSnapshot`, `OfferStats`), enums (`BookingOverrideAction`, `OperationsCancellationFee`, `OfferAnomaly`, colours on `OfferStatus` and `DispatchResponse`), repositories (booking, offer, rating, report, dispatch recipient additions and `OperationsMapRepository`), `BookingPolicy` (admin abilities), `BookingOfferPolicy`, `routes/admin/bookings.php`, `config/booking.php` (`operations`), `config/admin.php` (`realtime`), menu (Operations group), views under `admin/bookings`, `admin/offers`, `admin/operations`, components `realtime-status`, `live-refresh`, `stat-tile` (`stat` prop), JS `core/{realtime,live-refresh,operations-map}.js`, lang ar/en. No migration.

### Known gaps / notes for later milestones

- **Driver app follow-up (not done here):** replace the history built from selected offers with `GET /api/v1/provider/bookings` (paginated, API.md 8.14), keep the detail screen on `/provider/bookings/{booking}`.
- No export of the bookings or offers listings yet (needs `reports.export` and the audited export job); fare distribution charts by service/zone/time, the reference-formula and offer-limit settings screens of SRS §16.7, reassign-to-another-provider, and the audited corrective workflow for accepted offers are later work.
- `expired_no_provider` is final in the transition graph, so the queue can only contact those customers; reviving it would need a graph change decided in a later milestone.
- Cancellation fees decided by operations are stored on the booking only; posting them is P3.5. Notifications to the parties are log lines until P3.6. Payment tab is a placeholder until P3.2.
- Dispatch re-run notifies providers that already saw an older wave again by design (recipients are cleared); a provider that cancelled the job is not special-cased on an operator re-dispatch.
- The map tooltips are mouse/focus-less (Leaflet circle markers); a keyboard-accessible list of the same items is a P6.3 accessibility item.
- Realtime needs Reverb running (`php artisan reverb:start`) and `REVERB_*` in `.env`; without a key the indicator reads "not configured" and the map and live switch fall back to polling.

### Next steps

P2.9 — end-to-end ride test, race tests and MVP hardening. Useful facts for it: operator overrides are in `BookingOverrideService` (race against customer selection and provider actions happens under the booking row lock); the operations feed is dispatched after commit; `MISSED_TESTS.md` lists the P2.8 tests.

---

## P2.7 — Driver app: complete ride flow · 🟡 2026-10-28 (gate NOT run)

Committed directly on `main`. ADR-106..108. **By user instruction no test, `flutter test`, `flutter analyze`, `dart format` check, integration test or `scripts/check.sh` was written or run, so the milestone is not marked done.** Every test that should exist is in `MISSED_TESTS.md` (P2.7 section). Only compile checks ran: `flutter pub get --offline` (adds `url_launcher`), `flutter gen-l10n` and `dart analyze lib` (whole driver_app `lib`, no issues).

### What was built (driver_app, `features/jobs`, against the real API)

- **Shell (approved drivers):** Home, Requests (badge with the open count), My offers, History; active-job banner above every tab; tabs stay mounted so alerts work from any tab. Application/documents status moved behind a Home entry (`/application`). Earnings, payouts, incentives and the earnings snapshot are NOT shown (no server feature flag yet, P3.8); the rating on Home is not shown either (no field in the provider API).
- **Home:** the existing availability card (online switch, eligibility reasons, vehicle picker, tracking line, connection indicator) plus sound/vibration preferences for new requests.
- **Requests feed:** `GET /provider/requests/available`, refetched on every driver-channel event, on reconnect and by polling (6 s socket down, 25 s up); new-request sound and vibration per preference; card with category, masked pickup area, distance to pickup, destination area, trip size, customer fare, load summary chips, expiry countdown; actions accept customer fare, counter-offer, skip; offline / not eligible (typed reasons) / empty states.
- **Counter-offer:** large in-app numeric keypad (no system keyboard), bounds from the API, inline below/above/same-as-fare check, earnings preview (gross, commission, net) shown only when the server returns `available: true`.
- **Duplicate-tap and lost offers:** one in-flight call and one Idempotency-Key per intent (`JobActions`); 409/422 "lost" codes refresh the feed and offers and show a clear message.
- **My offers:** pending first, then closed ones with the reason (rejected, lost, expired, request changed, unavailable, withdrawn), countdown, withdraw, open job for the selected one.
- **Active job:** big buttons drawn from `allowed_actions` (acknowledge, arrived with GPS evidence read once per intent, PIN pad with attempts left and lock, start, complete with a cash confirmation); external maps navigation to pickup then destination; customer card (call only when the server exposes the number), instructions, route, free-wait countdown, support-flag notice; cancel (reasons from the policy, `preview=true` cost before confirming, re-dispatch notice), report. Restores from `GET /provider/bookings/active` at start, on resume and on every event; a booking the driver no longer holds ends the screen ("no longer yours").
- **Completion summary:** amount, payment method, cash to collect, net preview when available, rate customer (stars, tags, comment), report, back home. **History:** list + detail (same job screen, read only).

### Files

`driver_app/lib/features/jobs/{domain,data,presentation}`, `app/router.dart` (`/job/:id`, `/job/:id/cancel`, `/job/:id/report`, `/application`), `provider_onboarding_screen.dart` (hands approved drivers to `DriverShell`), ARB ar/en (+ regenerated l10n), `pubspec.yaml` (`url_launcher`).

### Known gaps / notes for later milestones

- **No history endpoint:** job history is built from `GET /provider/offers` (state `selected`) plus `GET /provider/bookings/{id}` per row. A `GET /provider/bookings` list should replace it (P2.9 or P3.4).
- Destination is shown as rounded coordinates ("area") because the masked card has no area name for the drop-off; add one server side.
- No map on Home (flutter_map is not a driver dependency yet); the status line and the maps hand-off cover navigation.
- Call relay does not exist: with `direct_phone_exposure` off the call button is hidden and a note explains.
- Commission/net preview needs P3.1 (`earnings_preview.available`); the UI is ready.
- Push notifications for new requests are P3.6; until then sound/vibration only fire while the app runs.
- The `arrived` GPS evidence comes from a one-off fix (6 s timeout); never blocks.

## P2.6 — Customer app: complete ride flow · 🟡 2026-10-27 (gate NOT run)

Committed directly on `main`. ADR-103..105. **By user instruction no test, `flutter test`, `flutter analyze`, `dart format` check, integration test or `scripts/check.sh` was written or run, so the milestone is not marked done.** Every test that should exist is in `MISSED_TESTS.md` (P2.6 section). Only compile checks ran: `flutter gen-l10n` and `dart analyze lib` (whole customer_app `lib`, no issues).

### What was built (customer_app, against the real API)

- **Shell:** bottom navigation Home, My Trips, Account (`ShellTabSpec` registry; Wallet and Offers join only when their server feature flag is on, none exists yet, so no placeholder tabs). `AppConfig.features` is now parsed.
- **Home:** map, pickup/destination, catalog, request button, persistent active-booking banner (`GET /bookings?in_flight=1`).
- **Booking form:** route confirmation (map + polyline), `DynamicFormRenderer` of the category schema, reference estimate as guidance (factors, notice, "unavailable" text), fare input with Arabic-digit parsing, suggestions and bounds, passengers, cash payment, summary, submit with a per-intent Idempotency-Key; input is kept on errors.
- **Finding offers:** snapshot + `private-customer.{uuid}` realtime hints + 5 s/20 s polling fallback + reconnect refetch; search countdown; offer cards (both kinds, per-offer expiry countdown), sort by price/ETA/rating, accept with confirmation (409/422 lost codes refresh with a specific message), reject, edit fare (warns offers are invalidated, no promise of more offers), no-offers state.
- **Live trip:** provider/vehicle card, agreed fare, PIN (from `trip_pin`), status steps, free-wait countdown, map with smoothed provider marker, last-updated and stale banner, share (system share sheet, stop sharing), safety sheet (support contacts and own emergency contact, clearly labelled, SOS is P5.1), report, cancel. Buttons follow `allowed_actions`.
- **Cancel:** reason, `preview=true`, fee card, commit with `accepted_fee_minor` + key, `cancellation_fee_changed` re-confirm.
- **Completion/ended:** receipt, rating (tags, comment), report, rebook, no-provider state (try again / edit price, no guarantee wording), cancellation fee summary, timeline.
- **My Trips:** paginated history with service/status/date filters; details reuse the booking screen.
- Deep link route `/booking/:id` (push wiring is P3.6).

### Known gaps / notes for later milestones

- **Call and chat are hidden:** the customer API exposes no provider phone or relay; chat is P3.7.
- **Emergency numbers:** `GET /config/app` has no emergency numbers; the safety sheet shows only configured support contacts and the customer's own emergency contact. P5.1 should add them and SOS.
- A deep link opened while signed out loses its target (redirects to splash).
- Retry-after-reject "retry search" for `expired_no_provider` is a new booking (no API restarts an ended search).
- Existing tests (`router_test`, `launch_flow_test`, `catalog_flow_test`, `map_flow_test`, `auth_flow_test`) assume the old single-screen Home and will need updating (see MISSED_TESTS).
- The integration test (create, offers, accept against a local backend) is not written; steps in MISSED_TESTS.

## P2.5 — Trip lifecycle, cancellation v1, ratings, share link · 🟡 2026-10-26 (gate NOT run)

Committed directly on `main` in small steps. ADR-98..102. **By user instruction no test, pint, phpstan, `php artisan test`, build or `scripts/check.sh` was written or run, so the milestone is not marked done.** Every test that should exist is in `/var/www/delivery_platform/MISSED_TESTS.md` (P2.5 section, including which existing tests are likely to break).

### What was built

- **Schema** (`2026_10_26_090000_create_trip_lifecycle_tables`, migrate / rollback / migrate checked): trip columns on `bookings` (heading, arrival + GPS evidence, free-wait end, start, PIN attempts / verified / locked, support flag, `final_fare_minor`, cancellation code / fee / decision, `cancellation_policy_id`); `booking_proofs` (append-only), `cancellation_policies`, `booking_ratings`, `booking_share_links` (token hash only), `booking_reports`; `rating_avg` / `rating_count` on `users`.
- **Provider API** (idempotent, ownership-checked, through `BookingTransitionService`): `GET bookings/active`, `acknowledge`, `arrived` (GPS evidence), `verify-start-pin` (constant-time compare, attempt limit, lock + support flag), `start`, `complete` (final fare = agreed snapshot, cash gives `cash_due`), `cancel` (preview), `rating`, `reports`; the booking resource now carries trip state, PIN state, cancellation terms, masked phone per setting and server-computed `allowed_actions`.
- **Cancellation v1**: `CancellationFeeCalculator` (pure), `CancellationService`, customer `POST /bookings/{b}/cancel` with `preview=true` (no key) and fee acknowledgement on commit, provider cancel with auto re-dispatch (clears assignment, agreed fare, PIN, arrival data and the old winner marker, keeps history, never re-asks that provider). Fees are only computed and snapshotted (ledger posting is P3.5).
- **Admin**: Cancellation policies module under Pricing (Tailwind components, Precognition form with fee-rule repeater, Yajra table with collapsed filters card, audit, `lock_version`, overlap guard, reasons required for dangerous actions).
- **Share trip**: `POST/DELETE /bookings/{b}/share`, public `/t/{token}` page + `/t/{token}/data` (noindex, no-store, throttled, minimal data, same 404 for every failure).
- **Ratings** (once per side, window, tags, documented mean + provisional flag), **reports** (`booking_reports`, daily limit), receipt basics and `trip` / `cancellation` / `final_fare` / `receipt` blocks on the customer booking.
- **Realtime to the customer channel** (ADR-98): `booking.status_changed` and `booking.location_updated` (moved off the shared booking channel) on `private-customer.{uuid}`, status also on `private-provider.{uuid}`, `booking.support_flagged` on both; the booking stream is closed to non-selected providers.
- Anonymisation scrubs arrival coordinates, comments, report texts and share links; the export lists ratings and reports. Settings: `ride_pin_max_attempts`, `rating_window_hours`, `rating_min_sample`.

### Files / modules touched

`app/Services/Booking/{TripLifecycleService,TripPinService,CancellationService,CancellationFeeCalculator,CancellationPolicyResolver,CancellationPolicyService,ShareLinkService,TripShareViewService,RatingService,RatingAggregationService,BookingReportService,ProviderActionResolver}`, `BookingTransitionService`, `BookingSnapshotBuilder`, `BookingActionResolver`; new repositories under `Repositories/*/Booking`; controllers `Api/V1/Provider/{TripController,TripFeedbackController}`, `Api/V1/Customer/{BookingCancellationController,BookingShareController,BookingFeedbackController}`, `Website/TripShareController`, `Admin/Booking/CancellationPolicyController`; requests/resources under `Trip`; events `PartyEvent`, `TripStatusChanged`, `TripLocationUpdated`, `TripSupportFlagged`; `routes/api_v1.php`, `routes/website.php`, `routes/admin/cancellation-policies.php`; views `admin/cancellation-policies/*`, `website/trip-share*.blade.php`, `components/website/layouts/app`; lang `ar` / `en` (`admin`, `api`, `enums`, `website`).

### Commands and results

Not run (by instruction): pint, phpstan, `php artisan test`, `npm run build`, `scripts/check.sh`. Run: `php -l` on every new / changed file, `migrate` + `migrate:rollback --step=1` + `migrate`, `route:list` (all new routes present), container resolution of every new service and controller, `view:cache`, and two rolled-back tinker scenarios (trip + cancellation + share + ratings + reports; cancellation-policy service), all with the expected output.

### Known gaps / notes for the next milestones

- **P2.6 customer app:** subscribe to `private-customer.{uuid}` (status, location, support-flag and offer events) and refetch `GET /bookings/{b}` on a sequence gap; render buttons from `allowed_actions`; show `trip_pin` while it is not null; cancel flow = preview call (no key), show `meta.cancellation.message`, then commit with `accepted_fee_minor`; share returns a URL; rating / report forms; `provider_location_visible` tells when the map marker is allowed.
- **P2.7 driver app:** subscribe to `private-provider.{uuid}`, load `GET /provider/bookings/active` on start and on any status event; buttons from `allowed_actions`; send the GPS fix with `arrived`; PIN entry shows `attempts_remaining` and the lock state; a status event for a booking you no longer hold (re-dispatch) means drop the job; all writes need `Idempotency-Key`.
- Lifting a PIN lock, operations cancellation / override, the customer Ratings and provider Jobs admin tabs and the live operations screens were built in P2.8. Fee posting is P3.5; online-payment completion (`completed_pending_payment`) P3.2; photo / signature / OTP proof upload endpoints P4 (`POST /proofs` is not built yet; the table and guards are). `completed_at` + payment window for tips is P3.2.
- Customer-side call relay does not exist: with `direct_phone_exposure` off the provider only sees a masked number until chat (P3.7).
- No default cancellation policy is seeded: without one the built-in defaults (120 s free, no fees) apply.

## P2.4 — Offers, counter-offers & atomic selection · 🟡 2026-10-25 (gate NOT run)

Committed directly on `main` in small steps. ADR-94..97. **By user instruction no test, pint, phpstan, `php artisan test`, build or `scripts/check.sh` was written or run for this milestone, so it is not marked done.** Every test that should exist is listed in `/var/www/delivery_platform/MISSED_TESTS.md` (P2.4 section), including the parallel Process-pool race tests.

### What was built

- **Schema** (`create_booking_offers_tables`): `booking_offers` (state only; `active_marker` and `winner_marker` unique indexes give one active offer per provider and exactly one winner per booking), append-only `booking_offer_versions` (amount, currency, note, scope), `bookings.agreed_*` columns and the `selected_offer_id` foreign key. Migration ran, rolled back and ran again on local MySQL.
- **Services** (`App\Services\Offers`): `OfferSubmissionService` (accept customer fare, counter-offer, revision as a new version, withdraw, skip; all guards under the booking lock), `OfferSelectionService` (the critical section, calls `BookingTransitionService::transition(ProviderSelected, TransitionContext(providerId, offerId))`, copies agreed fare and scope, generates the trip PIN, closes the losers), `OfferExpiryService` + `ExpireOffer` job + `offers:expire` (every 5 s), `ProviderOfferInvalidator` (hooked into `AvailabilityService::takeOffline`), `OfferSettlement` (back to `searching_offers` when the last offer ends), `OfferCloser`, `OfferNotifier`, `BookingOfferBridge` (rebinding of `BookingOfferRepositoryInterface`; `PreOfferBookingOfferRepository` deleted), `ProviderRequestService`, `CustomerOfferService`.
- **API** (docs/API.md 8.12): provider `requests/available` (masked cards), `bookings/{b}/offers/accept-customer-fare`, `bookings/{b}/offers`, `offers/{o}/withdraw`, `bookings/{b}/skip`, `offers`, `bookings/{b}` (full details, selected provider only); customer `bookings/{b}/offers`, `offers/{o}/accept`, `offers/{o}/reject`. Booking detail gained `provider`, `vehicle`, `agreed_fare`, `agreed_scope`, `trip_pin`, `select_offer`.
- **Realtime**: `booking.offer_received/updated/expired`, `booking.provider_selected` on the customer and provider private channels (not the shared booking channel, ADR-96).
- **QA helpers**: `qa:race-accept`, `qa:race-offer` (Process pool, local only; how to run in docs/SETUP.md).

### Sanity checks actually run (no test suites)

`php -l` on every new and edited PHP file (clean); `php artisan migrate`, `migrate:rollback --step=1`, `migrate` (OK); `php artisan route:list` (12 new routes present); `php artisan schedule:list` (`offers:expire` every 5 s); `php artisan offers:expire` (ran, 0 expired); container resolution of every new service and controller through `php artisan tinker`; a tinker script inside a rolled-back transaction proving at database level: version rows are immutable, a second active offer for the same provider and booking is refused, the customer summary counts the open offer, `OfferRepository::accept()` succeeds once and refuses the second call.

### Not verified (needs the tests in MISSED_TESTS.md)

The full flows (submission guards, selection under concurrency, expiry by sweep, provider-unavailable hook, staged disclosure) have not been exercised end to end because eligible providers need the test fixtures; pint and phpstan were not run (new files may need formatting; `new static` in `OfferEvent` may trigger a phpstan rule).

### Known gaps / notes for P2.5

- P2.5 owns everything after selection: heading / arrived / PIN verification (`pin_hash` is bcrypt, `pin_encrypted` Crypt), start, complete, cancellation. When a provider cancels and the booking goes back to `searching_offers` (`auto_redispatch`), `BookingTransitionService` clears `provider_id` / `selected_offer_id` but **not** `agreed_*`, `pin_*`, `provider_selected_at`, and the accepted offer keeps `winner_marker = 1` (so a new winner on that booking is impossible): P2.5 must clear the agreed columns and release the old winner marker (and decide the status of that offer) in that flow.
- The customer app (P2.6) must subscribe to `private-customer.{uuid}` for offers; the driver app (P2.7) to `private-provider.{uuid}`.
- Eligibility lost without going offline (cash debt over the limit) is only enforced at selection.
- Commission preview is `available: false` until P3.1; `fare_hint` is informational; no extra settings were added for it.
- Queue: `ExpireOffer` and the dispatch waves need a real queue worker (`QUEUE_CONNECTION=redis`); the tests must fake those jobs.
- Provider phone is shown to the selected provider as-is; a masking/relay adapter is not built (SRS says optional).

## P2.3 — Reference pricing & booking creation · ✅ 2026-10-24

Committed directly on `main` in small steps (no milestone branch, by instruction), after `317c22d`. ADR-89..93.

### What was built

- **Schema** (`create_fare_policies_table`, `create_bookings_tables`): `fare_policies`, `bookings` (snapshots, searchable columns, `request_version`, `sequence`, unique `(customer_id, idempotency_key)`), `booking_stops`, append-only `booking_status_events`; the two P2.2 foreign keys (`provider_locations.booking_id`, `booking_dispatch_recipients.booking_id`). Four settings: `max_active_on_demand_bookings`, `scheduled_booking_min_lead_minutes`, `scheduled_booking_max_days`, `scheduled_search_lead_minutes`; `config/booking.php`.
- **Lifecycle (ADR-89):** `BookingStatus` (15 statuses, owns `allowedTransitions()`), `BookingTransitionService` as the only writer of `bookings.status` (row lock, graph check, per-target guards, event + sequence, broadcast after commit; the model refuses a changed status outside `BookingStatusGate`, a test keeps the gate to that one service), `BookingEventSequencer` (`bookings.sequence`).
- **Pricing (ADR-90):** `ReferencePriceCalculator` (formula V1, pure, integer math, no demand multiplier), fare policy resolution (zone beats category, latest wins, no overlaps), guidance on `GET /routes/estimate`, estimate stored with its inputs and policy values. **Admin:** Pricing > Fare policies (contract module: collapsed filters card, Precognition forms with repeater for surcharges, mandatory reasons, audit before/after, `lock_version`, calculator tab), `ZoneLookup`, `FarePolicySeeder` (starting EGP prices, to review).
- **Bookings (ADR-91):** `POST /bookings` (strict account check, `Idempotency-Key` required, every rule of Plan step 4, one active on-demand booking per customer, scheduled bookings wait in `draft`), `GET /bookings`, `GET /bookings/{booking}` (timeline, `allowed_actions`, offers summary), `PATCH /bookings/{booking}/request` (invalidates offers, restarts search and dispatch). `BookingEnteredSearch` -> `StartBookingDispatch` starts dispatch and the exact-expiry job; `ExpireBookingSearch` + `bookings:expire-searches` (every 5 s, also opens due scheduled bookings); `BookingNotifier` log hook.
- **Carry-over (ADR-92, 93):** `CatalogUsageRepository`, `ProviderWorkloadRepository`, `ProviderActivityRepository`, `CustomerActivityRepository` query real bookings; money lives behind the neutral `FinancialPositionRepositoryInterface` until P3.1; `DispatchableRequestSource` bound to `DispatchableBookingSource` (staged disclosure); `BookingParticipationRepository` verified (`customer_id`) and completed with the selected provider; Customers admin: real Bookings tab, "has active booking" filter, booking scrub in `CustomerDeletionService`, `bookings` section in `PersonalDataExporter`; zone suspend needs confirmation when bookings are in flight, zone delete refused.

### Commands and results (2026-10-24)

| Command | Result |
|---|---|
| `php artisan migrate`, rollback of both new migrations and migrate again (local MySQL) | OK |
| `scripts/check.sh` (the whole gate) | **All checks passed** |
| `vendor/bin/pint --test` / `vendor/bin/phpstan analyse` (level 6) | passed / 0 errors |
| `php artisan test --parallel` | passed: 854 tests, 7591 assertions (new: 15 x 15 transition matrix twice (enum and through the real service), calculator, create / read / change / expiry / snapshot / carry-over / guidance API, fare policy admin contract, seeder) |
| `npm run build` | built OK (no admin JS changed) |
| `scripts/browser-smoke.sh` (new screens) | passed; now also: fare policy edit blocked by a server rule through Precognition (no real PUT), corrected form saves and redirects, calculator validates then posts, Bookings tab lazy-loads its DataTable |
| customer_app / driver_app / platform_core: `dart format`, `flutter analyze`, `flutter test` | clean / no issues / 86, 109, 163 passed (untouched by this milestone) |

### Known gaps / notes for P2.4

- **Offers do not exist yet.** `BookingOfferRepositoryInterface` is bound to `PreOfferBookingOfferRepository` (summary 0, invalidate 0, throws once `booking_offers` exists): P2.4 must rebind it. Selection must go through `BookingTransitionService::transition(..., ProviderSelected, TransitionContext(providerId, offerId))` after verifying the offer under the lock; the service guards "no assignment" and "provider eligible (Working)" but trusts that the offer was verified. `selected_offer_id` has no foreign key yet (add it with `booking_offers`). Customer resources show `provider: null`; the provider-facing stage resources (I10) are P2.4/P2.5.
- Provider-side booking endpoints (`/provider/requests/available`, offers) are P2.4; `booking_dispatch_recipients.seen_at` / `response` are written through `DispatchRecipientRepositoryInterface` by it.
- Cancellation (`/bookings/{booking}/cancel`, fees), PIN generation/verification, proofs, ratings and share are P2.5 / P3.5: the transition guards already require the facts (`pinVerified`, `proofsSatisfied`) and `pin_hash` / `pin_encrypted` columns exist unused.
- Dispatch waves and search expiry are delayed jobs: `QUEUE_CONNECTION=sync` would chain every wave at once, so a real queue is required outside tests (the booking tests fake the two jobs; documented in SETUP).
- No Flutter work (customer booking UI is P2.6); the booking enums and payload shapes are in docs/API.md 8.11 and still to be mirrored in platform_core.
- Admin Bookings module (listing with all Appendix C filters, live operations) is P2.8; the customer tab shows only the basics. The tab has no filters card of its own, like the other customer tabs.
- A booking whose maps provider is down cannot be created (`503 maps_unavailable`); the reference estimate is never invented. Hourly bookings (no drop-off) get no guidance (`no_route`).
- The Process-pool race script for concurrent creates (P2.9) is not built; correctness relies on the customer row lock, the unique `(customer_id, idempotency_key)` index and the idempotency middleware lock.
- Customer notifications are a log-only hook until P3.6; fare policies and settings defaults are starting points to review before launch.

## P2.2 — Availability, live location, eligibility, dispatch engine · ✅ 2026-10-23

Committed directly on `main` in small steps (no milestone branch, by instruction), after `b85fe50`. ADR-84..88.

### What was built

- **Schema (`create_provider_location_and_dispatch_tables`):** `provider_locations` (append-only history, POINT 4326 + spatial index, accuracy, heading, speed, source, `anomaly`, `booking_id`, ms timestamps), `provider_last_locations` (one row per provider with the resolved city), `booking_dispatch_recipients` (booking, provider, wave, distance, notified / seen / response), `provider_profiles.last_offline_reason`.
- **Geo index:** `ProviderGeoIndex` with `RedisGeoIndex` (GEOADD / GEOSEARCH per city + TTL hash per provider) and `DatabaseGeoIndex`; `PROVIDER_GEO_INDEX` selects it (tests: database; the Redis twin is tested against a real local Redis).
- **Eligibility:** `EligibilityScope::Working` adds `location_stale`, `active_job_exists`, `cash_debt_limit_exceeded` to the single service; active job and debt sit behind `ProviderWorkloadRepositoryInterface` (placeholder that answers "none" and throws once `bookings` / `wallets` exist).
- **Services:** `AvailabilityService` (go online / offline with confirmation, force offline, stale sweep), `LocationIngestService` (batches, anomaly flags, history sampling, booking broadcast), `ProviderIndexPublisher`, `LocationRetentionService`, `FindEligibleProvidersService`, `DispatchService` + `DispatchBookingWave` (critical queue, unique per wave), `DispatchableRequest` / `DispatchableRequestSource` contract, `EventSequencer`; listener taking suspended providers offline; `SweepStaleProviders` (30 s) and `locations:prune` (daily).
- **API (docs/API.md 8.10):** `GET /provider/availability`, `POST /provider/availability/online|offline`, `POST /provider/location`, `POST /broadcasting/auth`. New settings `location_max_speed_kmh`, `location_history_interval_seconds`.
- **Realtime:** events `provider.availability_changed`, `booking.requested`, `booking.search_expanding`, `booking.location_updated`; `routes/channels.php` + `RealtimeChannelPolicy` for customer / provider / booking / admin.operations (permission gated).
- **Flutter:** platform_core availability models / repository, `TrackingLocationSource` (foreground service), `RealtimeClient.connectionState`, `ApiException.meta`, auth endpoint fixed to the API base; driver_app availability card on the approved home (switch, eligibility reasons, rationale dialog before the OS prompt, vehicle picker, offline confirmation, tracking line, connection indicator), `TrackingController` (server-driven frequency, buffering, heartbeat) and `RealtimeController` (reconnect, refetch on reconnect / gap); Android / iOS background configuration; ARB ar/en regenerated.

### Commands and results (2026-10-23)

| Command | Result |
|---|---|
| `php artisan migrate`, rollback of the new migration and migrate again (local MySQL) | OK |
| `scripts/check.sh` (the whole gate) | **All checks passed** |
| `vendor/bin/pint --test` / `vendor/bin/phpstan analyse` (level 6) | passed / 0 errors |
| `php artisan test --parallel` | passed: 722 tests, 5579 assertions (new: geo index on both implementations incl. real Redis, location ingest and anomalies, availability API, working eligibility matrix, dispatch waves with fake clock and Bus/Event fakes, channel authorization with real signing, retention) |
| `npm run build` | built OK (no admin JS touched, so `scripts/browser-smoke.sh` was not re-run) |
| customer_app / driver_app / platform_core: `dart format`, `flutter analyze`, `flutter test` | clean / no issues / customer_app 86, driver_app 109 (29 new: availability card, tracker, realtime controller), platform_core 163 (6 new: repository shapes, 422 meta) passed |

### Known gaps / notes for P2.3

- Dispatch is not wired to bookings: bind `DispatchableRequestSource`, call `DispatchService::start()`, replace `ProviderWorkloadRepository` (active booking with tracking state, debt in P3.1), complete `BookingParticipationRepository` (assumes `bookings.uuid`, `bookings.customer_user_id`) and add the two foreign keys (ADR-86).
- `ProviderActivityRepository` still throws once bookings / wallets exist (P2.1 note).
- No admin screen for locations or live ops yet (P2.8); the admin Echo client is not built, only the permission-gated channel.
- Foreground service, background indicator and the live Reverb connection are untested on a device or against a running Reverb here; the Flutter tests use fakes.
- Location history partitioning is a documented plan, not applied (ADR-85).

## P2.1 — Provider onboarding, vehicles, documents, verification · ✅ 2026-10-22

Branch `milestone/p2.1-provider-onboarding` (from `main` @ `efdeb6c`), fast-forwarded into `main`.

### What was built

- **Schema (migration `create_provider_onboarding_tables`, the P1.3 stub is extended, not recreated):** `provider_profiles` (uuid, type, status, reason/expiry, city, birth date, rating and acceptance counters, online flags, current vehicle, assigned officer, `lock_version`), `provider_service_category` (requested / approved / rejected), `vehicles` (unique normalised plate, capabilities), `vehicle_photos`, `document_types`, `document_requirements`, `provider_documents` and `vehicle_documents` (private path, sha256, scan status, **encrypted number**, dates, review fields, soft delete for replaced uploads), `provider_status_events`; `account_deletion_requests.previous_provider_status`.
- **Services (ADR-78..82):** `ProviderStatusService` (single status writer, history, event), `ProviderOnboardingService` (start, edit, submit, approve, request more information, reject, claim), `ProviderAccountService` (suspend / reactivate / auto-reactivate), `ProviderServiceCategoryService`, `VehicleService`, `DocumentService` + `UploadInspector` + `UploadScanner` + `PrivateFileStorage`, `DocumentAccessService` (signed, admin-bound, audited-when-served), `DocumentChecklistService` (requirement resolver), `OnboardingProgressService`, `ProviderEligibilityService` (typed reasons, never stored), `DocumentExpiryService`, `DocumentTypeService`, `ProviderDeletionService` + `AccountScrubber` (ADR-77), `VerificationReviewService`, `ProviderNotifier` (log-only hook until P3.6).
- **Integration:** `FileScanner` contract with `noop` (local/testing, stores `skipped`) and `clamav` (clamd over TCP) drivers; boot guard extended with `noop`; `private` disk; `config/provider.php`.
- **API (docs/API.md 8.9):** `GET /cities`; `POST/GET/PATCH /provider/application(s)`, `POST /provider/application/submit`, `GET /provider/eligibility`, vehicles CRUD + photos + `submit-review`, documents upload/list/show + signed owner-only files. `driver_terms` is now a required policy of the provider app.
- **Admin (contract modules, collapsed filters cards, Precognition forms, AJAX confirm dialogs with mandatory reasons):** Providers (listing with every Appendix C filter, show with Profile / Services / Vehicles / Documents / Verification history / Jobs and Earnings placeholders (`available after P2.5 / P3.4`) / Data requests / Audit; audited PII reveal; suspend, reactivate, per-service decisions, deletion processing), Verification queue (SLA age, claim) and the **side-by-side review screen** (document and photo viewer on signed URLs, checklist, approve / request more information / reject), Vehicles (listing, show with photos and documents, approve / reject), Document types and requirements; menu entries; lookups `providers` (and public-name lookups for service/vehicle categories); settings `provider_verification_sla_hours`, `provider_document_expiry_warning_days`, `rate_limit_provider_uploads_per_minute`.
- **Scheduler:** `documents:check-expiry` daily 02:00, `providers:reactivate-expired` every minute.
- **Seeders:** `DocumentTypeSeeder` (Egypt defaults, reference data) and `ProviderSeeder` (approved with valid documents and vehicle, pending, rejected, expired licence; clearly named "(demo)"; off in production).
- **Flutter:** platform_core `ProviderRepository`, models and enums; driver_app **wizard** (profile, services, vehicles with photos, documents with number / issue / expiry pickers and retry, declarations, review and submit; resumes at the server's `resume_step`) and **verification status screens** (under review, more information with reasons and tasks, rejected, approved with computed eligibility reasons and expiring documents, suspended); the old "coming soon" screen is gone; ARB ar/en regenerated.

### Commands and results (2026-10-22)

| Command | Result |
|---|---|
| `php artisan migrate` (local MySQL), rollback of the new migration and migrate again | OK |
| `scripts/check.sh` (the whole gate) | **All checks passed** |
| `vendor/bin/pint --test` | passed |
| `vendor/bin/phpstan analyse` (level 6) | passed, 0 errors |
| `php artisan test --parallel` | passed: 649 tests, 5290 assertions (new: ~150 in `Feature/Provider`, `Feature/Admin/Provider`, `Unit/Provider`: status machine, requirement resolver, eligibility, upload pipeline and scanners, private-file access and audit, IDOR, deletion, expiry job, seeders, the admin contracts) |
| `npm run build` | built OK |
| `npx vitest run` | 6 files, 23 tests passed (new: document viewer) |
| `scripts/browser-smoke.sh` (admin JS touched) | passed, now also: queue -> review screen -> viewer loads a private file through a signed URL (no public path requested) -> reject without a reason sends nothing -> Precognition then submit -> reload; this run found and fixed a lazy-loading bug in the review screen that the feature tests had missed (see the seeded-pages test) |
| customer_app / driver_app / platform_core: `dart format --set-exit-if-changed`, `flutter analyze`, `flutter test` | clean / no issues / customer_app 86, driver_app 80 (35 new: wizard steps, uploads and retries, resume, declarations, review, every status screen, RTL), platform_core 157 (8 new: repository shapes, multipart, idempotency) passed |


### Known gaps / notes for P2.2 and later

- `ProviderActivityRepository` (like the customer one) throws once bookings / wallets / payouts tables exist: P2.3 / P3.1 / P3.4 must replace it with real queries before deletions of providers with live jobs or money can be processed.
- Going online is not built: P2.2's `AvailabilityService` must call `ProviderEligibilityService::check()` (returns `usableVehicleIds` for the vehicle choice) and add location freshness, active-job and debt-limit reasons; `provider_profiles.is_online`, `online_since` and `current_vehicle_id` exist and are cleared on suspension / document expiry.
- Document **numbers** are shown masked in the admin; a `drivers.view_pii` reveal for them is not built. Document renewals by an approved provider are reviewed on the provider's Documents tab (they are not in the verification queue).
- The ClamAV driver and the S3 `private` disk are untested against real services (INTEGRATIONS.md); with no scanner configured uploads are stored as `not_scanned` and flagged.
- Notifications to the driver (status changes, expiry reminders) are log lines until P3.6 (`ProviderNotifier`).
- The Flutter wizard was tested with scripted repositories (no emulator / real camera run here); the minimum vehicle photo count (2) is a hint text, the server decides.
- Requirements seeded for Egypt are defaults to review, not legal advice (ADR-79).

## P1.6 — Customers admin module · ✅ 2026-10-21

Branch `milestone/p1.6-customers-admin` (from `main` @ `98a463d`), fast-forwarded into `main`.

### What was built

- **Module (contract, ADR-73..76):** Customers listing (Yajra, collapsed filters card: ID/phone/name search, status, registered range, city, deletion requested; "has active booking" shown as available after P2.3), masked phones everywhere, show page with tabs: Profile (reveal of contact details), Bookings / Payments / Coupons / Tickets / Ratings (explicit "available after P2.3 / P3.2 / P3.3 / P3.7 / P2.5" states, no fake rows), Consents, Devices, Saved places (`customers.view_pii`), Data requests, Messages, Restrictions and audit. Each lazy tab is its own DataTable on its own URL.
- **Actions (mandatory reason + audit before/after):** suspend (optional expiry), restrict, reactivate, send help message, approve (anonymise) / reject deletion request, reveal contact (audited, `customers.view_pii`), listing CSV export and per-customer JSON personal-data export (queued, signed link, audited, `customers.export`). No create/edit/delete route; `UserPolicy` has no delete ability.
- **Scheduler:** `ReactivateExpiredSuspensions` every minute (lock + re-check per account, audited as system).
- **Retention (ADR-75):** anonymisation keeps the row/uuid (financial references intact) and consents/audit/request, removes name, email, phone, photo, tokens, devices, saved places, messages and profile contents; blocked by active bookings / open money (`CustomerActivityRepositoryInterface` seam, throws once those tables exist) and for provider accounts.
- **API:** `GET /me/messages`, `POST /me/messages/{uuid}/read` (docs/API.md 8.8). Migration `add_customer_admin_support` (user standing columns, `customer_messages`, `admin_exports.format/subject_id`).
- **UI kit:** `<x-admin.action-modal>`, `<x-admin.available-after>`, `<x-admin.reveal-fields>` (+ `adminReveal`). **Bug fixed:** `adminTabs` used `$el` instead of `$root`, so lazy tabs never loaded on click and tab keyboard navigation was dead.

### Commands and results (2026-10-21)

| Command | Result |
|---|---|
| `scripts/check.sh` | **All checks passed**: pint, phpstan (level 6, 0 errors), `php artisan test --parallel` 526 tests / 4244 assertions (was 497), `npm run build`, customer_app 86, driver_app 45, platform_core 149 (format, analyze, test clean) |
| `npx vitest run` | 5 files, 19 tests passed (new: `adminReveal`) |
| `scripts/browser-smoke.sh` (admin JS touched) | passed: customers listing masked, lazy devices tab (own DataTable), reveal, suspend modal (empty reason blocked client-side, then Precognition call before the real submit), no JS errors |

New tests: customers contract (403 page/JSON/datatable, filters, masking, reveal audit, saved places PII gate, lazy tabs, no hard delete), account actions (Precognition, reason, audit, expiry job, job vs manual changes, schedule), deletion (permission, anonymisation keeps references, blockers, reject restores), exports (CSV masked, JSON personal data, owner-only), customer inbox API.

### Known gaps / notes for P2.1 and later

- Customer Flutter inbox screen is not built (API only); push delivery of help messages waits for P3.6.
- P2.3 / P3.1 must replace `CustomerActivityRepository` (and `CatalogUsageRepository`) with real queries, add the Bookings/Payments/Coupons/Tickets/Ratings tabs and the "has active booking" filter, and add their scrub steps to `CustomerDeletionService` and sections to `PersonalDataExporter`.
- Provider accounts: `customer_is_provider` blocks deletion here; P2.1 owns the provider-side equivalent (`drivers.view_pii`, `drivers.suspend`) and can reuse `UserPolicy` patterns, `<x-admin.action-modal>`, `<x-admin.reveal-fields>` and the lazy-tab DataTable pattern (`CustomerTabDataTable`).
- DataTables serialise every model attribute: always use `$table->only([...])` for PII-bearing models.

## P1.5 — Geography: cities, service zones, availability, maps adapter · ✅ 2026-10-21

Branch `milestone/p1.5-geography` (from `main` @ `0ab6844`), fast-forwarded into `main`.

### What was built

- **Schema (ADR-69):** `cities` (country, name, timezone, `center` POINT), `service_zones` (city, name, `polygon` POLYGON + spatial index, `ZoneStatus`, `operating_hours`, `restricted_pickup`, notes, `is_demo`), `zone_service_category` (active, overrides), `saved_places` (uuid, point, address snapshot). Models in the morph map, repositories bound, casts `GeoPointCast`/`GeoPolygonCast`, `Support\Geo` (`GeoPoint`, `GeoPolygon`, `OperatingHours`, `Distance`, `WkbReader`).
- **Services:** `GeoService` (resolve point -> city, zone, categories with availability/reason/opens_at), `CityService`, `ServiceZoneService` (overlap warning, polygon validity, activation/suspension guards, availability matrix), `MapsService`, `RouteEstimateService`, `CoveragePreviewService`, `SavedPlaceService`.
- **Maps adapter (ADR-71):** `MapsProvider` + `osrm_nominatim`, `google`, `fake` drivers, caching decorator, error mapping to `maps_unavailable` / `route_not_found`. Real drivers are tested against faked HTTP only (no credentials). Env vars in docs/INTEGRATIONS.md.
- **Admin (`service_areas.manage`, contract modules):** Cities (generator, activate/deactivate with reason), Service zones (Leaflet polygon draw component, live overlap warning `zone-overlap.js`, operating-hours repeater, show page with map, availability matrix tab with overrides, overlaps tab, activate/suspend with reason), Coverage preview (point or two-point trip, resolved zone, categories, route estimate), `cities` lookup, menu entries, collapsed filters cards (Appendix C: city, status, category availability).
- **API (docs/API.md 8.7):** `GET /service-areas/resolve`, `/places/search`, `/places/reverse`, `/routes/estimate`, `/me/saved-places` CRUD; `GET /service-categories?lat&lng` now filters by the zone matrix and hours (`ServiceCategoryRepositoryInterface::publicList($zoneId)`).
- **Seed:** `GeographySeeder` (Cairo + Giza demo zones, flagged `is_demo`, labelled demo, skipped when `GEO_SEED_DEMO` is false / production).
- **Flutter:** platform_core `GeoRepository` + models + enums; customer_app map panel on home (flutter_map, rationale-first permission, manual fallback), debounced place search, pin adjust, swap, saved places, outside-area and error states, catalog request by pickup; ARB ar/en regenerated (ADR-72).

### Commands and results (2026-10-21)

| Command | Result |
|---|---|
| `php artisan migrate` (local MySQL) | OK (`create_geography_tables`) |
| `vendor/bin/pint --test` | passed |
| `vendor/bin/phpstan analyse` (level 6) | passed, 0 errors |
| `php artisan test --parallel` | 497 tests: 496 passed on the full run; the one failure (a P1.4 test asserting the old no-zone `lat/lng` behaviour) was updated and re-run green (7/7). New: geo unit, GeoService, maps drivers (Http::fake, cache, guard), admin cities/zones/coverage contract tests, API endpoints, saved places IDOR, seeder |
| `vitest run` | 4 files, 17 tests passed |
| `npm run build` | built OK |
| `scripts/browser-smoke.sh` | passed (admin JS touched) |
| platform_core / customer_app / driver_app: `dart format --set-exit-if-changed`, `flutter analyze`, `flutter test` | clean / no issues / platform_core 149, customer_app 86, driver_app 45 passed |

### Known gaps / notes for P1.6 and later

- OSRM/Nominatim and Google drivers are untested against live services; the public OSM servers are development only.
- No active-bookings guard when suspending a zone (no bookings yet): P2.3 must add it (ADR-70).
- Customer admin (P1.6) can show saved places via `SavedPlaceRepositoryInterface::forUser` (PII: gate with `customers.view_pii`).
- Dispatch (P2.2) should use `GeoService::resolve` + zone overrides (`dispatch_radius_steps_km`, fare bounds) for pricing/dispatch; pricing (P2.3) must apply `min/max_fare_minor` overrides.
- Map default center is a constant (Cairo); the Flutter map shows flutter_map's OSM policy warning with the default tile URL.

## P1.4 — Service catalog, vehicle categories, form schemas, versioned snapshots · ✅ 2026-10-20

Branch `milestone/p1.4-service-catalog` (from `main` @ `3d7ff5b`), fast-forwarded into `main`.

### What was built

- **Schema (ADR-65):** `service_families`, `vehicle_categories`, `service_categories` (all policy fields of Plan P1.4, null policy fields fall back to settings), `category_vehicle_category`, `service_form_schemas` (one draft row per category + immutable published rows), `service_category_versions` (immutable JSON snapshot: configuration, effective policies, vehicles, form). Models registered in the morph map; repositories bound in `RepositoryServiceProvider`.
- **Enums:** `ServiceFamilyKind`, `BookingType`, `FormFieldType` (15 types), `ProofType`, `VisibilityOperator`, `Weekday`, `PaymentMethodType`.
- **Services:** `CategoryPolicyResolver` (null -> `SettingKey` defaults), `FormSchemaValidator` (schema checks, ADR-67), `ServiceFormValidator` (schema -> Laravel rules for `service_details`, unknown keys rejected, `visible_if`), `ServiceCategorySnapshotBuilder`, `ServiceCategoryPublisher` (publish with reason + audit, "unpublished changes" detection), `ServiceCategoryService` / `ServiceFamilyService` / `VehicleCategoryService` / `ServiceFormSchemaService`, `CatalogReadService`. Guards (ADR-66): never deactivate the last active category (or the family holding it), active bookings need an explicit confirmation, reasons audited; `CatalogUsageRepository` is the seam P2.3 must replace.
- **Admin (contract modules, `services.manage` / `vehicle_categories.manage`):** Service families, Vehicle categories, Service categories with tabs: edit page (General, Booking rules, Pricing bounds and negotiation, Dispatch, Proofs and safety, Payments and coupons) and show page (Overview + effective policy, Vehicle categories, **Form schema builder** (repeater), **Preview** of the draft form with live `visible_if`, Versions history (lazy tab with snapshots), Activity). Collapsed filters cards (family / active / booking type / negotiation enabled ...), lookups, Publish / Activate (confirm), Deactivate modal (reason + active-bookings confirmation), optimistic locking, Precognition everywhere with errors on the exact repeater cell. Repeater row partial gained a `textarea` type and per-field width.
- **Seeder (SRS §28, idempotent, part of `DatabaseSeeder`):** 4 families, 8 vehicle classes, 8 published and active categories (tuk-tuk, taxi, economy, standard, courier motorcycle, pickup truck, furniture moving truck, freight truck) with Arabic-first schemas.
- **API (docs/API.md 8.6):** `GET /service-families`, `/service-categories?lat&lng&booking_type&service_family_id`, `/vehicle-categories?service_category_id`; public, ETag, served from the published snapshot (includes the form schema version and the version id bookings will reference).
- **Flutter:** platform_core `DynamicFormRenderer` for every `FormFieldType` (+ `DynamicFormController`, `FormLogic`, `DynamicFormStrings`, `PhotoFieldHandler`, `ImagePickerPhotoHandler`), `CatalogRepository` + models, `VisibilityOperator` enum (ADR-68). Customer app: service family chips + category cards from the API on the home screen (all async states), selected-service panel, `/service-details` screen rendering the category's dynamic form and keeping `service_details` for P2.6; ARB ar/en + regenerated l10n.

### Files touched

Backend: `app/{Enums/{Catalog,Payments},DTOs/Catalog,Models/{ServiceFamily,VehicleCategory,ServiceCategory,ServiceFormSchema,ServiceCategoryVersion},Repositories/{Contracts,Eloquent}/Catalog,Services/Catalog(+Forms),Policies,Http/{Controllers/{Admin/Catalog,Api/V1/Common/CatalogController.php},Requests/{Admin,Api/V1}/Catalog,Resources/Api/V1/Catalog},DataTables/Admin/Catalog,Support/Admin/Lookups}`, `config/{admin,admin_menu}.php`, `routes/{api_v1.php,admin/{service-families,service-categories,vehicle-categories}.php}`, migration `2026_10_20_180000_create_service_catalog_tables`, factories, `CatalogSeeder`, `lang/{ar,en}/{admin,api,enums,catalog}.php`, `resources/views/admin/{service-families,service-categories,vehicle-categories}`, `resources/js/admin/components/schema-preview.js(+test)`, repeater row partial, `tests/{Unit/Catalog,Feature/{Admin/Catalog,Api/CatalogEndpointsTest.php,Catalog},Browser/admin-login-smoke.mjs}`. Flutter: `packages/platform_core/lib/src/{forms,catalog,enums/visibility_operator.dart}` + tests, `customer_app/lib/{app/{router.dart,l10n},features/{catalog,home}}` + tests. Docs: ADR-65..68, API 8.6. `scripts/browser-smoke.sh` (admin gets `services.manage`).

### Commands and results (2026-10-20)

| Command | Result |
|---|---|
| `php artisan migrate` (local MySQL); `php artisan db:seed --class=CatalogSeeder` twice | OK; 8 categories / 8 versions, second run no change |
| `vendor/bin/pint --test` | passed |
| `vendor/bin/phpstan analyse` (level 6) | passed, 0 errors |
| `php artisan test --parallel` | passed - 423 tests, 3474 assertions (was 336 / 2854); new: 34 unit (schema validator, form validator, operators), 43 admin catalog contract/publishing, 7 API, 3 seeder |
| `npm run build` / `npx vitest run` | built OK / 15 passed (new: schema preview + `visible_if` parity) |
| `scripts/browser-smoke.sh` (real headless Chrome) | passed - login protocol plus catalog: builder repeater add, duplicate key stopped by the Precognition call (no real submit), valid save = Precognition then submit, live preview shows the conditional field after answering, no JS errors |
| `scripts/check.sh` (everything) | All checks passed: `platform_core` 141 tests (new: renderer per field type in RTL and LTR, logic parity, repository), `customer_app` 58 (new: catalog flow, details form, router rule), `driver_app` 45; format and analyze clean |

### Known gaps / notes

- Zone availability (`lat`/`lng`) is validated and accepted but not applied until P1.5.
- `CatalogUsageRepository::activeBookingCount()` returns 0 and throws once a `bookings` table exists: **P2.3 must implement it** (ADR-66).
- Photo answers are attachment references; uploads arrive with P4.x (the app stores the picked local path until then).
- The driver app does not use the catalog yet (provider service selection is P2.1); `DynamicFormRenderer` is ready for it.
- Permissions: `services.manage` covers view, edit and publish of families/categories (Plan Appendix A has no finer split).
- The customer home shows only the first page of the catalog; there is no search or pagination (the catalog is small by design).

### Next steps

P1.5 geography. Reuse: `ServiceCategoryRepositoryInterface::publicList()` (add the zone filter there and in `CatalogReadService::categories()`; the endpoint already validates `lat`/`lng`), the `service-categories` admin lookup for zone-service pivots, `CategoryPolicyResolver`, `AdminDataTable` + generator patterns, the customer `catalogProvider` (pass `lat`/`lng` once the user's location is known).

---

## P1.3 — Identity API: OTP, users, profiles, devices, consents, deletion · ✅ 2026-10-20

Branch `milestone/p1.3-identity-api` (from `main` @ `d48b999`), fast-forwarded into `main`.

### What was built

- **Schema (ADR-60):** `users` restructured into the phone-identity table (uuid, unique `phone_e164`, `phone_verified_at`, `status`, `preferred_locale`, `last_seen_at`, soft deletes, nullable name/email, no password); new `customer_profiles`, `provider_profiles` (stub), `otp_challenges` (keyed phone hash, code HMAC only, attempts, delivery status), `user_devices`, `user_consents` (-> `policy_versions.id`, append-only), `account_deletion_requests`.
- **SMS:** `SmsGateway` contract + `log` driver (log channel `otp_dev`, local/testing only) + `smsmisr` real driver (implemented from vendor docs, untested without credentials, INTEGRATIONS.md).
- **OTP/auth (ADR-61/62):** `OtpService` (crypto-random, hashed, cooldown/TTL/length/attempts from `SettingsService`, per-phone/IP/device limits, enumeration-safe answers, SMS failure = 503 not fake success), `AuthService` (verify -> find/create user -> device -> Sanctum token with one ability `customer`|`provider` from `X-App`; refresh; logout), `EnsureAccountActive` (typed 403 per `UserStatus`), `user.seen`.
- **Endpoints:** otp/request, otp/verify, refresh, logout, GET/PATCH `/me`, `/me/photo` (re-encoded, private disk, signed URL), GET/POST `/me/consents`, `/me/devices`, POST/DELETE `/me/account-deletion-requests`, public `GET /policies/{type}`, signed media route. Details in docs/API.md 8.5.
- **Test-OTP safety:** fixed code only for configured numbers in local/testing; `TestOtpGuard` aborts boot elsewhere, the service re-checks the environment, no request parameter can enable it (tests for all three).
- **Admin:** read-only OTP challenges log (permission `otp_challenges.view` -> System/Integration Admin), Yajra DataTable + collapsed filters card (phone/state/delivery/app/IP/date), masked phones, no codes.
- **Flutter (ADR-64):** platform_core `IdentityRepository`, models, `DeviceIdStore`, `AuthStage`/`computeAuthStage`, `ApiClient.post(headers:)`. Customer: phone (EG preselected) -> OTP (resend countdown, typed errors, rate-limit wait) -> profile -> consent (policy reader, separate marketing choice) -> home (sign out, delete account with one idempotency key); status screen for suspended/restricted/deletion pending/verification/deleted and a retry state when `/me` fails; router redirect from `AuthStage`; token in secure storage. Driver: phone -> OTP -> `/provider-onboarding` ("coming soon", real route) with the same status screen.

### Files touched

Backend: `app/{Enums/Identity,DTOs/Identity,Models/{User,CustomerProfile,ProviderProfile,OtpChallenge,UserDevice,UserConsent,AccountDeletionRequest},Repositories/{Contracts,Eloquent}/Identity,Services/Identity,Services/Policy/PolicyReadService.php,Integrations/Sms,Http/{Controllers/Api/V1/{Auth,Me,Common},Requests/Api/V1/{Auth,Me},Resources/Api/V1/Identity,Middleware/{EnsureAccountActive,TouchLastSeen}.php,Controllers/Admin/Identity},DataTables/Admin/Identity,Policies/OtpChallengePolicy.php,Support/{Auth,Media},Exceptions/{RateLimited,ServiceUnavailable}Exception.php}`, providers, `bootstrap/app.php`, `config/{identity,services,logging,admin_menu}.php`, `routes/{api_v1.php,admin/otp-challenges.php,console.php}`, 2 migrations, `lang/{ar,en}/*`, `resources/views/admin/otp-challenges`, tests. Flutter: `packages/platform_core/lib/src/{identity,api/api_client.dart}`, both apps' `lib/{app/router.dart,app/l10n,features/{auth,splash,home|provider_onboarding}}` and tests. Docs: ADR-60..64, API 8.5, INTEGRATIONS.

### Commands and results (2026-10-20)

| `php artisan migrate` (local MySQL) | OK (2 new migrations) |
| `vendor/bin/pint --test` | passed |
| `vendor/bin/phpstan analyse` (level 6) | passed, 0 errors |
| `php artisan test --parallel` | passed - 336 tests, 2854 assertions (was 291 / 2542); new: 23 OTP/bypass, 18 `/me`, 4 admin OTP log |
| `npm run build` | built OK |
| `scripts/check.sh flutter` | All checks passed: `platform_core` 64 tests (new: identity repository, auth stage), `customer_app` 49 (new: full flow, OTP errors/resend, status screens), `driver_app` 45 (new: stops at onboarding entry, status); format/analyze clean |

### Known gaps / notes

- Profile photo upload and push-token registration are not wired in the apps (endpoints exist). `POST /auth/refresh` exists but the apps do not call it yet.
- Deletion requests are only recorded; admin processing (anonymise, retention) is P1.6. Active bookings will need to block deletion (P2.5).
- Terms and privacy must be published (Admin > Policy versions) before launch; with none published no consent is required.
- SMS Misr driver is unverified against the live service; no SMS is sent until `SMS_DRIVER` is configured (503 otherwise).
- `restricted` accounts pass non-strict routes; booking creation should use `account.active:strict` (P2.3).

### Next steps

P1.4 service catalog (done, see above). Reuse: `ClientApp`, `ability:`/`account.active` route middleware, `PolicyReadService`, `RateLimitedException`, `meta()` on domain exceptions, `AdminDataTable::rawColumn()`, platform_core `IdentityRepository` pattern for new feature repositories.

---

## P1.2 — Settings, markets, locales, policy versions, app config API · ✅ 2026-10-19

Branch `milestone/p1.2-settings-markets-config` (from `main` @ `8d78b63`), fast-forwarded into `main`.

### What was built

- **Typed settings (ADR-10, ADR-55):** `SettingKey` enum (42 keys: type, default, rules, group, admin input, bounds, unit; never sensitive), `SettingGroup` (10 tabs), `SettingType`, `SettingInput`, `DigitsPreference`; `platform_settings` table, `SettingsRepository`, `SettingsService` (typed `get/string/int/bool/list`, cached forever and busted on write, `set()` and `updateGroup()` validate against `SettingKey::rules()`, run in a transaction under row locks, one audit row per changed key with before/after, cross-field rules, per-tab fingerprint -> `409 stale_version`).
- **Admin Settings screen:** one tab per group, fields generated from the enum (`admin/settings/_field.blade.php` has no per-key markup), `<x-admin.form>` with Precognition using the same rules, read-only view for `settings.view` without `settings.manage`, active tab kept after save.
- **Carry-over from P1.1:** `admin_mfa_required_roles`, `admin_login_lockout_seconds` and the eight rate limits (`rate_limit_*`) are settings now; the old config/env values are their defaults. `AdminMfaService`, `AdminAuthService` and the named limiters in `AppServiceProvider` read the settings.
- **Markets:** Countries and Currencies admin modules (hand-written to the generator shape, ADR-56): filters cards, `lock_version`, audit, ISO code / currency code / exponent immutable, default country or used/default currency cannot be deactivated or deleted, registry and app-config caches busted. Permission `markets.manage` (Appendix A has no separate view permission).
- **Policy versions:** `policy_versions` (uuid, type `PolicyType`, per-type version assigned at publish, ar/en title + text, `ContentStatus`), admin module with draft CRUD, publish (reason, `policies.publish`, per-type lock, previous version archived, row frozen), listing filters, content tab per locale, activity tab (ADR-57).
- **API:** `GET /api/v1/config/app` (market, locales, currency + exponent + symbols, digits preference, support contacts, maintenance, per-app min version / force update, current policy versions, feature-flag placeholder) and `GET /api/v1/locales`; public, `Cache-Control: public, max-age=30` + `ETag` (304), assembled payload cached and busted by every writer (ADR-58). docs/API.md 8.4.
- **Flutter (both apps + platform_core):** `appConfigProvider` wired: startup fetch with last-good cache in `SharedPreferences` (fallback on any non-cancel failure), DTO aligned to the real response (freezed/json regenerated), `AppGate` in the `MaterialApp.router` builder shows real maintenance and forced-update screens (`BlockingNotice`, `evaluateAppGate`, version compare in platform_core), refetch on app resume and "Check again", splash waits (max 4 s) for the config, server default language until the user chooses, language picker limited to the supported locales, `moneyFormatterProvider` (server digit style + currency symbols). ARB `ar`/`en` and generated l10n updated (ADR-59).

### Files touched

Backend: `app/{Enums/{Settings,Policy,Content},DTOs/{Settings,Markets,Policy,Config},Models/{PlatformSetting,PolicyVersion,Country,Currency},Services/{Settings,Markets,Policy,Config},Repositories/{Contracts,Eloquent}/{Settings,Policy,Common},Http/{Controllers/{Admin/{Settings,Markets,Policy},Api/V1/Common/ConfigController.php},Requests/Admin/{Settings,Markets,Policy},Resources/Api/V1/Config},DataTables/Admin/{Markets,Policy},Policies,Support/Config,Support/Money/CurrencyRegistry.php}`, `app/Services/Admin/{AdminAuthService,AdminMfaService}.php`, `app/Providers/{App,Repository}ServiceProvider.php`, `config/{admin,admin_menu,rate_limits}.php`, `routes/{api_v1.php,admin/{settings,countries,currencies,policy-versions}.php}`, 3 migrations, `database/factories/PolicyVersionFactory.php`, `lang/{ar,en}/{admin,api,enums}.php`, `resources/views/admin/{settings,countries,currencies,policy-versions}`, `tests/Feature/{Settings,Api/AppConfigEndpointTest.php,Admin/{Settings,Markets,Policy}}`.
Flutter: `packages/platform_core/lib/src/{config/{app_gate,installed_version}.dart,ui/blocking_notice.dart}`, `customer_app` and `driver_app`: `lib/{app/{app.dart,l10n},core/providers.dart,features/{app_config,language,splash}}`, `test/{helpers.dart,features/app_config}`. Docs: ADR-55..59, API 8.4, INTEGRATIONS, this file.

### Commands and results (2026-10-19)

| Command | Result |
|---|---|
| `php artisan migrate` (local MySQL + testing DB) | OK (3 new migrations) |
| `vendor/bin/pint --test` | passed |
| `vendor/bin/phpstan analyse` (level 6) | passed, 0 errors |
| `php artisan test --parallel` | passed - 291 tests, 2542 assertions (was 214 / 1570) |
| `npm run build` / `npx vitest run` | built OK / 12 passed (admin JS untouched) |
| `scripts/browser-smoke.sh` | passed (login form, Chrome headless) |
| `platform_core`: `dart format --output=none --set-exit-if-changed .`, `flutter analyze`, `flutter test` | clean / no issues / 54 passed (new: version parsing/compare, gate rule, `BlockingNotice`) |
| `customer_app`: same three commands | clean / no issues / 38 passed |
| `driver_app`: same three commands | clean / no issues / 39 passed |

Tests added: backend 77 (`SettingKeyTest`, `SettingsServiceTest`, `ConfigMovedToSettingsTest`, `SettingsAdminTest`, `CountryAdminTest`, `CurrencyAdminTest`, `PolicyVersionAdminTest`, `AppConfigEndpointTest`); `RateLimitersTest` and `LocaleNegotiationTest` now use `RefreshDatabase` because every API request reads the (cached) rate-limit settings. Flutter: repository mapping against the exact server payload, cache fallback, per-app rule, gate widget tests (maintenance, forced update, up-to-date, not forced, check again, fail-open), server default language, money formatter.

### Known gaps / notes

- Dual approval for policy publishing (Appendix B "optional") is not implemented; publishing needs `policies.publish` plus a reason.
- No export on Countries/Currencies/Policy versions (tiny reference data); there is no `markets.view` or `policies.view` permission in Appendix A, so the module permission (`markets.manage`, `policies.manage`) also gates viewing.
- The forced-update screen has no store link (no store URL setting exists, and no dead buttons); it offers "Check again". Soft (non-forced) updates are not shown in the app.
- Category-level overrides of these settings come with P1.4 (`CategoryPolicyResolver`); most dispatch/offer/OTP keys are stored and served but consumed by later milestones (P1.3 OTP, P2.x booking/dispatch).
- Policy texts are not served by the API yet (the config lists current ids/versions); P1.3 consent screen needs `GET /policies/{type}` (or the website, P5.5).
- The Flutter client does not send `If-None-Match` (the shared `ApiClient` treats 304 as an error); it relies on the cached last-good copy. Server ETag works for any HTTP client.
- `settings.manage` can set the rate limits to 1/min; the lower bounds are intentionally permissive but never 0.

### Next steps

P1.3 - identity API (OTP, users, profiles, devices, consents). It consumes `otp_*` settings, `GET /config/app` policy versions (`user_consents.policy_version_id` -> `policy_versions.id`), `SettingsService::int/bool`, and the rate-limit settings for `otp-request` / `otp-verify`.

---

## P1.1 — Admin authentication, roles/permissions, admin users, audit log UI · ✅ 2026-10-19

Branch `milestone/p1.1-admin-identity-access` (from `main` @ `b1b7685`), fast-forwarded into `main`.

### What was built

- **Identity:** `admins` (uuid, soft deletes, `AdminStatus`, `lock_version`, encrypted MFA secret + hashed recovery codes, nullable persisted `locale`), `admin` session guard + `admins` password broker, `Admin` model on guard `admin`, `Role` (Spatie subclass: `is_system`, ar/en `label`, `lock_version`), morph aliases `admin`/`role`, `Gate::before` for Super Admin (ADR-46).
- **Permissions:** `AdminPermission` (Appendix A, already complete), new `AdminRole` enum (12 SRS roles + Appendix B defaults), idempotent `PermissionSeeder` (enum -> Spatie, guard `admin`, removes stale) and non-destructive `RoleSeeder`, both in `DatabaseSeeder` (ADR-50). `php artisan admin:create` (interactive, no default credentials, ADR-51).
- **Auth screens** (auth layout, `<x-admin.form>` AJAX + Precognition): login (generic error, per email+IP lockout, `Throttled` events), TOTP challenge (TOTP or one-time recovery code, replay-proof), forgot/reset password, logout; "My account" page: profile + language, password change, MFA enrolment with SVG QR + manual key + recovery codes (shown once), regenerate/disable with password, own active sessions with revoke / revoke-others (ADR-47/48). MFA enforced for the roles in `ADMIN_MFA_REQUIRED_ROLES`.
- **Audit/Login activity:** `admin_login_events` (append-only) + `admin_sessions`; login/logout/failed logins and MFA/password/session changes are audited (new `AuditAction` cases).
- **Modules (Admin Module Contract, ADR-53):** Admin users (CRUD by invitation email, roles, suspend/reactivate with reason, MFA reset with reason, soft delete, activity tab, ADR-49 safety rules), Roles (CRUD + grouped permission matrix with select-all per group, system roles protected), Audit logs (read-only, filters per Appendix C, show page with before/after diff viewer, secrets masked), Login activity (read-only, filters). New components `x-admin.permission-matrix`, `x-admin.diff-viewer`; icons key/pause/play/lock-closed/clipboard.
- **Dashboard placeholder** (`admin.dashboard`): the admin's roles/permission count/MFA state, quick links to permitted modules, and "available after ..." empty states for areas they may see; no numbers. `/admin` goes to it, else to the first permitted menu item, else an empty page. Sidebar entries (Appendix D "System" group + Dashboard) are permission-aware.
- **Carry-over from P0.3:** `Tests\Concerns\InteractsWithAdmin` now creates real `Admin` records with direct Spatie permissions and guests are redirected to the login page (module tests untouched; `TestAdmin` deleted); `AllowLocalGuestAccess` and `ADMIN_LOCAL_GUEST_ACCESS` removed everywhere; permissions seeded from the enum on guard `admin`; `admin` in the morph map; the chosen locale is persisted on the admin; module routes live in `routes/admin/*.php`.
- **Fixes found on the way:** middleware priority list used the wrong class so `SetLocale`/`Idempotency` ran after route `can:` checks (ADR-48); `CountryFactory` iso2 could collide with the seeded `EG` (flaky `AdminKitTest`); admin JS handles 401 (session ended) with a toast and a redirect to the login page.
- **Real-browser check:** `scripts/browser-smoke.sh` / `make browser-smoke` (headless Chrome over CDP, no new dependency, ADR-54).

### Files touched

Backend: `app/{Models,Enums/Admin,DTOs/Admin,Services/Admin,Repositories/{Contracts,Eloquent}/Admin,Http/{Controllers/Admin/{Auth,Security,Admins,Roles,Audit},Middleware/Admin,Requests/Admin/*},Policies,DataTables/Admin/{Admins,Roles,Audit},Notifications/Admin,Support/{Auth,Admin/Lookups},Console/Commands/CreateAdmin.php}`,
`app/Providers/{App,Repository}ServiceProvider.php`, `bootstrap/app.php`, `config/{auth,admin,admin_menu,permission}.php`, `routes/admin.php` + `routes/admin/{auth,security,admins,roles,audit-logs}.php`,
`database/{migrations/2026_10_19_120000_create_admins_table.php,factories/AdminFactory.php,seeders/{Permission,Role,Database}Seeder.php}`, `lang/{ar,en}/{admin,api,enums}.php`,
`resources/views/{admin/{auth,security,admins,roles,audit-logs,login-activity,dashboard},components/admin/{permission-matrix,diff-viewer,layouts,topbar}}`, `resources/js/admin/core/{ajax-form,datatables}.js`, `composer.json/lock` (+ `bacon/bacon-qr-code`), `.env.example`, `phpunit.xml`,
`tests/{Concerns,Support,Feature/Admin/**,Browser}`. Root: `scripts/browser-smoke.sh`, `Makefile`. Docs: ADR-46..54, API 8.3, SETUP, INTEGRATIONS, this file.

### Commands and results (2026-10-19)

| Command | Result |
|---|---|
| `composer require bacon/bacon-qr-code` | OK (3.1), no security advisories |
| `php artisan migrate:fresh --seed` (local MySQL) | OK (1 new migration, Permission/Role seeders) |
| `vendor/bin/pint --test` | passed |
| `vendor/bin/phpstan analyse` (level 6) | passed, 0 errors |
| `php artisan test --parallel` | passed - 214 tests, 1570 assertions (was 126 / 654) |
| `npm run build` | built OK |
| `npx vitest run` | 12 passed (1 new: 401 handling) |
| `scripts/browser-smoke.sh` (Chrome 155 headless) | 14/14 checks passed: empty submit sends nothing; server (Precognition) errors block the real submit; wrong password = validation call then submit with one generic error; valid login lands on `/admin/dashboard`; logout |

Tests added (88): `Feature/Admin/Auth/{AdminLoginTest,AdminMfaTest,AdminPasswordResetTest}`, `Feature/Admin/Security/AdminSecurityTest`, `Feature/Admin/Admins/AdminUsersAdminTest`, `Feature/Admin/Roles/RolesAdminTest`, `Feature/Admin/Audit/AuditLogsAdminTest`, `Feature/Admin/{AdminRouteAuthorizationGuardTest,AdminAccessSeedingTest,AdminDashboardTest,CreateAdminCommandTest}`.

### Known gaps / notes

- `ADMIN_MFA_REQUIRED_ROLES`, lockout seconds and rate limits moved to typed settings in P1.2 (config values are the defaults); the password policy length is still config.
- "Allowed IP controls" (SRS §16.16) are not implemented; not part of the P1.1 prompt.
- Invitation / reset emails are sent synchronously through the configured mailer (`log` locally); production needs a real mailer. The notification inbox (bell) arrives with P3.6.
- Changing an admin's email in the panel does not re-verify it; roles of a logged-in admin take effect on their next request.
- Login events / sessions have no retention pruning yet (P6.2). Audit rows for failed logins use `actor_type = system`.
- The browser smoke check is not in `scripts/check.sh` (needs Chrome + MySQL); only the login form is covered end to end in a browser; other forms are covered by Vitest and PHPUnit.
- The unrelated `docs/DECISIONS.md` ADR-39 text still describes `AllowLocalGuestAccess`; ADR-46/52 supersede it.

### Next steps

P1.2 - typed settings (include `admin_mfa_required_roles`, rate-limit and lockout values), markets, policy versions, `GET /api/v1/config/app`.

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
