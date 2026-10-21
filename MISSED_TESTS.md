# Missed tests

Tests that should exist for work done under the rule "do not write or run tests" (user instruction, effective during P2.4).
One section per milestone. Nothing in this file has been run. Common helpers: `Tests\Concerns\BuildsDispatch` (`cairo()`,
`approvedProviderFor()`, `onlineProviderFor()`), `Tests\Concerns\BuildsBookings` (`bookingWorld()`, `actingAsCustomer()`,
`idempotency()`), `Booking::factory()`, `ProviderProfile::factory()->approved()`, `Vehicle::factory()`.

## P2.4 — Offers, counter-offers and atomic selection

Status of existing tests: **no P2.4 test was written or run.** The tests of earlier milestones were not run either, so the
effect of these changes on them is unknown. Likely candidates to break and why:
`tests/Feature/Booking/BookingExpiryTest.php` and `BookingRequestChangeTest.php` (they bind anonymous `BookingOfferRepositoryInterface`
classes; the interface itself is unchanged, so they should still pass), any test that builds `AvailabilityService` by hand (new
constructor argument `ProviderOfferInvalidator`; resolved from the container everywhere in the code base), booking resource
shape tests (`BookingResource` has the new keys `vehicle`, `agreed_fare`, `agreed_scope`, `trip_pin`; `provider` is still null before selection),
and any test of `allowed_actions` in `offers_received` (now contains `select_offer`). Also run `vendor/bin/pint --test` (new files were not
formatted; `RepositoryServiceProvider` import order, unused imports) and `vendor/bin/phpstan analyse` (level 6; `OfferEvent::toCustomer`
uses `new static` on a class with a final constructor, `OfferSubmissionService` unused `ProviderProfile` import checks, array shapes of `meta`).

Setup common to the feature tests below: `['category' => $category, 'city' => $city] = $this->cairo();` a booking made through
`POST /api/v1/bookings` (or `Booking::factory()->forCategory($category)` with `policy_snapshot` containing
`dispatch.offer_ttl_seconds`, `dispatch.search_ttl_seconds`, `offers.{max_per_provider,max_revisions,counter_offer_min_pct,counter_offer_max_pct,negotiation_enabled}`
and `search_expires_at` in the future); providers from `onlineProviderFor($category)` (online, fresh position, usable vehicle, set as current
vehicle) each with a `booking_dispatch_recipients` row for the booking (`DispatchRecipient::create([... 'notified_at' => now()])`);
`Event::fake([BookingOfferReceived::class, BookingOfferUpdated::class, BookingOfferExpired::class, BookingProviderSelected::class, BookingStatusChanged::class])`;
`Bus::fake([DispatchBookingWave::class, ExpireBookingSearch::class, ExpireOffer::class])` (queue is `sync` in tests, so delayed jobs must be faked).

### tests/Feature/Offers/OfferSubmissionTest.php

- `test_provider_accepts_customer_fare_and_booking_moves_to_offers_received`: POST `/provider/bookings/{b}/offers/accept-customer-fare` with Idempotency-Key -> 201, offer kind `accept_customer_fare`, status `submitted`, version 1 with `amount_minor = proposed_fare_minor`, booking `offers_received`, `BookingOfferReceived` dispatched to `customer.{uuid}`, `ExpireOffer` queued with delay to `expires_at`, `earnings_preview.available === false`.
- `test_counter_offer_creates_version_one_and_counter_offered_status`: POST `/offers` amount inside bounds -> 201, status `counter_offered`, `scope` and `note` stored; `fare_hints` array present.
- `test_provider_not_dispatched_cannot_offer`: provider without recipient row -> 403 `offer_not_dispatched`, no offer row.
- `test_ineligible_provider_cannot_offer`: provider whose position is stale / suspended -> 422 `provider_not_eligible` with `meta.reasons`.
- `test_offer_needs_a_usable_current_vehicle`: `current_vehicle_id` null -> 422 `offer_vehicle_missing` (or `provider_not_eligible`).
- `test_counter_bounds_are_enforced_from_the_booking_snapshot`: proposed 5000, min 80 %, max 150 %: 3999 and 7501 -> 422 `offer_amount_out_of_bounds` (`meta.min = 4000`, `meta.max = 7500`); 4000 and 7500 accepted. Changing the category's pct afterwards does not change a running booking (snapshot, I4).
- `test_counter_amount_rules`: 0 -> 422 validation; same as proposed fare -> 422 `offer_amount_same_as_fare`; wrong currency -> 422 `offer_currency_mismatch`.
- `test_counter_offers_can_be_disabled_per_category`: `policy_snapshot.offers.negotiation_enabled = false` -> counter 422 `counter_offers_disabled`, accept-customer-fare still 201.
- `test_offer_limit_per_provider`: `max_per_provider = 1`, provider withdraws then offers again -> 422 `offer_limit_reached`; with 2 the second offer works.
- `test_revision_creates_a_new_version_never_edits_the_amount`: first 5500, then 6000 -> same offer id, `revision_count = 1`, two `booking_offer_versions` rows (5500 and 6000), `current_version_id` = version 2, `BookingOfferUpdated` event, expiry renewed.
- `test_revision_limit`: `max_revisions = 2`: third revision -> 422 `offer_revisions_exceeded`; `max_revisions = 0`: first revision -> 422.
- `test_identical_resubmission_is_a_noop_and_same_idempotency_key_replays`: same key and body twice -> second `meta.replayed = true`, one version row; same key with different amount -> 409 `offer_idempotency_conflict`.
- `test_offer_is_refused_when_booking_assigned_or_search_over`: booking `provider_selected` -> 409 `booking_already_assigned` (`meta.booking_status`); `search_expires_at` in the past -> 409 `booking_search_expired`; booking cancelled -> 409 `booking_not_open_for_offers`.
- `test_offer_validity_is_capped_by_the_search_window`: `offer_ttl_seconds = 600`, search ends in 60 s -> `expires_at == search_expires_at`.
- `test_offer_rate_limit`: 21 submissions in a minute -> 429 (limiter `offers`).
- `test_skip_records_declined_without_penalty`: POST `/skip` -> recipient `response = declined`, provider counters unchanged; with an active offer -> 409 `offer_withdraw_first`; not dispatched -> 403.
- `test_fare_hints_never_change_the_price`: reference estimate low 4000 / high 6000, offer 3500 -> `fare_hints` contains `below_reference_estimate`, stored amount is 3500; with two other open offers 4500 and 5000, offer 4000 -> `below_recent_offers`.

### tests/Feature/Offers/OfferVersionImmutabilityTest.php

- `test_a_version_cannot_be_updated_or_deleted`: `BookingOfferVersion::update()` and `delete()` throw `LogicException`.
- `test_offer_row_never_holds_an_amount`: `booking_offers` has no amount column (schema check); the amount read through `currentVersion`.
- `test_unique_version_numbers_and_one_active_offer_per_provider`: inserting a second version with the same number, or a second active offer (`active_marker = 1`) for the same booking and provider, raises a QueryException; a closed offer (`active_marker NULL`) allows a new one.

### tests/Feature/Offers/OfferWithdrawTest.php

- `test_provider_withdraws_an_active_offer`: status `withdrawn_by_provider`, `active_marker` NULL, `BookingOfferExpired` to customer and provider channels, booking back to `searching_offers` when it was the only offer and the window is open (status event `offers_exhausted`).
- `test_withdraw_keeps_booking_in_offers_received_when_other_offers_remain`.
- `test_cannot_withdraw_a_selected_closed_or_foreign_offer`: accepted offer -> 409 `offer_not_withdrawable`; expired offer -> 409; another provider's offer -> 403 `offer_not_yours`; unknown uuid -> 404.

### tests/Feature/Offers/OfferSelectionTest.php

- `test_customer_selects_an_offer_atomically`: POST `/bookings/{b}/offers/{o}/accept` -> 200; offer `accepted_by_customer` with `winner_marker = 1`; booking `provider_selected`, `provider_id`, `selected_offer_id`, `agreed_fare_minor` = version amount, `agreed_offer_version_id`, `agreed_scope`, `provider_selected_at`, `pin_hash` and `pin_encrypted` set; response has `provider`, `vehicle`, `agreed_fare`, `trip_pin` (4 digits); a status event `offer_accepted` with the offer version in metadata; all other active offers `lost_booking_assigned`; events: `BookingProviderSelected` to customer and winner channels, `BookingOfferExpired` to every loser's provider channel and the customer.
- `test_status_is_written_only_through_the_transition_service`: selection works while `BookingStatusGate` is closed outside the service (no LogicException), and the existing source-grep test still passes.
- `test_accept_expired_offer_returns_409`: offer `expires_at` in the past but status still `submitted` (sweep not run) -> 409 `offer_expired`, `meta.booking.status` still `offers_received`, nothing assigned; offer already `expired` -> 409 `offer_expired`.
- `test_accept_after_another_offer_was_accepted_returns_409`: accept A (200), then accept B -> 409 `booking_already_assigned` with `meta.booking` fresh payload showing the winner; booking still has A.
- `test_accept_closed_offers`: withdrawn / rejected / invalidated offer -> 409 `offer_not_active`.
- `test_replay_of_the_winning_offer_is_not_an_error`: accept A twice (same and different Idempotency-Key) -> 200 both, `meta.replayed` true on the second, one status event.
- `test_selection_rechecks_provider_eligibility`: after the offer was made the provider's position goes stale (or document expires without the offline hook) -> 409 `offer_provider_unavailable`, offer `invalidated_provider_unavailable`, booking unchanged (or back to `searching_offers` when it was the last offer).
- `test_customer_cannot_choose_someone_elses_booking_or_a_foreign_offer`: other customer -> 404; offer of another booking in the URL -> 404; provider token -> 403.
- `test_restricted_account_cannot_select` (`account.active:strict`) -> 403 `account_restricted`.
- `test_accept_requires_an_idempotency_key` -> 422 `idempotency_key_required`.
- `test_reject_offer`: status `rejected_by_customer`, provider channel gets `booking.offer_updated`, booking back to `searching_offers` when it was the last offer and the window is open, stays `offers_received` otherwise; rejecting a closed offer -> 409 `offer_not_active`; after assignment -> 409 `booking_already_assigned`.
- `test_winner_marker_makes_a_second_winner_impossible_at_database_level`: inserting a second offer of the same booking with `winner_marker = 1` raises a QueryException; `OfferRepository::accept()` returns false for the second.
- `test_trip_pin_visibility`: `trip_pin` present in `provider_selected`, `provider_heading_to_pickup`, `provider_arrived`, null before selection and after `in_progress`.

### tests/Feature/Offers/OfferExpiryTest.php

- `test_scheduler_sweep_expires_offers_without_any_client`: offer `expires_at` 1 s ago, run `php artisan offers:expire` (Artisan::call) -> status `expired`, `active_marker` NULL, events to customer and provider; no HTTP request involved; running it again changes nothing (idempotent, event count unchanged).
- `test_expire_offer_job_closes_only_when_due`: `ExpireOffer` handled before `expires_at` -> no change; after -> expired; after a revision (new `expires_at`) the old job is a no-op.
- `test_last_expired_offer_returns_booking_to_searching`: booking `offers_received`, one offer expires, window open -> `searching_offers` (status event `offers_exhausted`, no new `BookingEnteredSearch` / dispatch restart); with another active offer it stays `offers_received`; with the search window over it is left for `bookings:expire-searches`.
- `test_search_expiry_closes_all_offers_as_expired`: `BookingExpiryService::expire()` -> every active offer `expired`, booking `expired_no_provider`.
- `test_scheduler_registers_offers_expire_every_five_seconds`: `Schedule` contains `offers:expire` with expression `everyFiveSeconds` and `withoutOverlapping`.
- `test_offer_expiry_does_not_touch_an_accepted_offer`: accepted offer with past `expires_at` is untouched.

### tests/Feature/Offers/ProviderUnavailableTest.php

- `test_going_offline_invalidates_the_providers_active_offers`: `POST /provider/availability/offline` -> offers `invalidated_provider_unavailable`, customer gets `booking.offer_expired`, booking back to `searching_offers` if last.
- `test_suspension_and_stale_sweep_invalidate_offers`: `ProviderStatusChanged` Approved -> Suspended and `AvailabilityService::sweepStale()` both close the offers of that provider only (other providers' offers stay active).
- `test_invalidation_covers_several_bookings`: provider with active offers on two bookings -> both closed, each booking settled separately.

### tests/Feature/Offers/RequestChangeInvalidationTest.php

- `test_request_change_invalidates_offers_with_status_invalidated_request_changed`: PATCH `/bookings/{b}/request` with offers present -> offers `invalidated_request_changed`, customer and providers get `booking.offer_expired`, `request_version` bumped, booking `searching_offers`, dispatch restarted; the old versions remain readable.
- `test_cancelled_booking_closes_offers_via_bridge`: `BookingOfferBridge::invalidateActive($booking, BookingCancelled)` -> `expired`.

### tests/Feature/Offers/StagedDisclosureTest.php

- `test_available_requests_card_is_masked`: `GET /provider/requests/available` -> card has `pickup_area.lat/lng` rounded to 2 decimals and an area name; JSON contains none of: pickup address `line`/`title`, exact lat/lng (6 decimals), `customer`, `phone`, customer name, `service_details` free text; has `distance_to_pickup_m`, `proposed_fare`, `counter_offer.min_minor/max_minor`, `expires_at`.
- `test_only_dispatched_open_requests_are_listed`: not dispatched, declined (`response = declined`), expired window, assigned booking -> not listed; listing sets `seen_at`.
- `test_ineligible_provider_gets_empty_list_with_reasons`: `meta.eligible = false`, `meta.eligibility` populated.
- `test_provider_cannot_read_full_booking_before_selection`: `GET /provider/bookings/{b}` -> 404 for dispatched provider, for the provider with an offer, and for a losing provider after selection.
- `test_selected_provider_gets_full_details`: after selection -> 200 with addresses, stops, `service_details`, `customer.name`, `customer.phone`, `agreed_fare`.
- `test_booking_channel_never_carries_offer_data`: all offer events are broadcast on `customer.*` or `provider.*` channels, never `booking.*` (inspect `broadcastOn()` of the four event classes).
- `test_customer_offer_cards_hide_provider_private_data`: `GET /bookings/{b}/offers` -> display name is first name + initial, no phone/email/document data; plate present or null per `BOOKING_OFFER_SHOW_PLATE`; expired offers are not listed even before the sweep; cheapest first.
- `test_provider_offers_list_statuses`: `GET /provider/offers` returns `state` pending / selected / rejected (declined and lost) / expired / invalidated / withdrawn.

### tests/Feature/Offers/NegotiationAuthorizationTest.php

- Customer token on provider routes and provider token on customer routes -> 403; unauthenticated -> 401; `idempotent` middleware: missing key -> 422 on the three create/choose calls; same key + different payload -> 422 `idempotency_key_reused`; same key + payload replays the stored response with `Idempotent-Replayed`.
- Realtime channel auth: a dispatched provider can join `booking.{uuid}` but not another provider's `provider.{uuid}`; the customer can join `customer.{own uuid}` only.

### tests/Concurrency/ (MySQL, real processes) — the mandatory parallel proof

Add a PHPUnit testsuite `Concurrency` to `phpunit.xml` (directory `tests/Concurrency`, excluded from the default run and from `--parallel`), requiring `DB_CONNECTION=mysql`, no `RefreshDatabase` wrapping transaction (data must be committed so child processes see it: use `DatabaseMigrations` or a dedicated seeded testing DB and truncate in `tearDown`), `QUEUE_CONNECTION=sync` with the delayed jobs faked via a test-only service provider or `Bus::fake` not available across processes (set `BROADCAST_CONNECTION=null`).

- `tests/Concurrency/AcceptRaceTest.php::test_parallel_accepts_of_different_offers_produce_exactly_one_winner`: one booking in `offers_received` with 5 offers from 5 eligible providers; spawn 8 to 16 processes with `Illuminate\Support\Facades\Process::pool` (or `Symfony\Component\Process\Process` array) running `php artisan qa:race-accept {booking} --worker={offer} --key={uuid}` (round robin over offers); assert exactly one worker output has `won: true`, every other has code `booking_already_assigned`; DB: one offer `accepted_by_customer`, four `lost_booking_assigned`, `bookings.provider_id` equals the winner's provider, one `booking_status_events` row to `provider_selected`, sequence numbers contiguous and unique. Repeat the whole scenario 10 times to shake out timing.
- `AcceptRaceTest::test_parallel_duplicate_taps_with_the_same_key_agree`: 8 processes, same offer, same Idempotency-Key -> exactly one `won: true`, the other seven `replayed: true`, one status event.
- `AcceptRaceTest::test_accept_races_with_expiry_sweep_and_provider_going_offline`: processes running `offers:expire`, `qa:race-accept` and a provider-offline call concurrently on an offer that is expiring: no deadlock (no `SQLSTATE 40001` escapes: if the database reports a deadlock the service must not leave a half state), the booking either has exactly one provider with an accepted offer or none with no accepted offer, never an accepted offer without `provider_id`.
- `tests/Concurrency/OfferRaceTest.php::test_parallel_counter_offers_respect_limits`: one dispatched provider, `max_per_provider = 1`, `max_revisions = 2`, 10 processes via `qa:race-offer --worker={amount} --key={uuid}` with different amounts -> exactly one `booking_offers` row for the provider, at most 3 version rows, the rest rejected with `offer_revisions_exceeded`; with the same key and amount in all processes -> exactly 1 version row and no error.
- `OfferRaceTest::test_two_providers_offer_while_customer_changes_the_request`: concurrent `PATCH /bookings/{b}/request` (as a process) and offer submissions: afterwards no active offer belongs to an older `request_version` generation (either the offer is `invalidated_request_changed` or it was submitted after the change and is valid).
- `tests/Concurrency/Concerns/RunsRaceProcesses.php`: helper to start N processes with a barrier (start all, then release together by touching a file they poll for) to maximise overlap; document `composer test:concurrency` / `php artisan test --testsuite=Concurrency`.

### tests/Unit

- `tests/Unit/Support/Booking/CounterOfferBoundsTest.php`: table test of `CounterOfferBounds::for()` (min rounds up, max rounds down, null pct = null bound, `negotiationEnabled` default true when the key is missing).
- `tests/Unit/Enums/OfferStatusTest.php`: `isActive()` only for submitted and counter_offered; `providerView()` mapping; `OfferInvalidationReason::offerStatus()` mapping (request_changed -> invalidated_request_changed, search_expired and booking_cancelled -> expired, provider_unavailable -> invalidated_provider_unavailable); every case has `enums.*` translations in `ar` and `en`.
- `tests/Unit/Enums/EnumTranslationsTest` (existing, if any) covers the new enums `OfferStatus`, `OfferKind`, `FareHint`, `BookingAction::SelectOffer`.

### Console / commands

- `tests/Feature/Console/QaRaceCommandsTest.php`: `qa:race-accept` and `qa:race-offer` refuse outside `local`/`testing` (`app()->detectEnvironment(fn () => 'production')`), report "Booking not found" for an unknown uuid, and (with a prepared booking and `--workers=2`) print `PASS`.

### Manual / browser

- Run `php artisan qa:race-accept {booking} --workers=8` and `qa:race-offer` against the local MySQL after creating a booking, two or more provider offers (API) and read the PASS line.
- With Reverb running, subscribe a client to `private-customer.{uuid}` and confirm `booking.offer_received` arrives when a provider offers, and that a dispatched provider subscribed to `private-booking.{uuid}` receives no offer events.
