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

## P2.5 — Trip lifecycle, cancellation v1, ratings, share link, reports

Status: **no P2.5 test was written or run** (user instruction). A rolled-back tinker script exercised the happy path (acknowledge, arrived with and without GPS, wrong PIN x5 and lock, correct PIN, start, complete, ratings both ways, duplicate rating, report, cancel preview and commit, fee-changed conflict, share view and revocation, provider cancel with re-dispatch, ownership 404) and a second one the cancellation-policy service (create, overlap, deactivate, delete, invalid rule); neither replaces the tests below.

Likely to break in existing tests and why:
- `tests/Feature/Location/LocationIngestTest.php` asserts `BookingLocationUpdated` on the booking channel. The class was deleted: the event is now `TripLocationUpdated` on `private-customer.{uuid}`; `LocationIngestService` has a new constructor dependency (`BookingRepositoryInterface`).
- Tests that build `BookingTransitionService` or `BookingSnapshotBuilder` by hand (new constructor args: `ShareLinkRepositoryInterface`, `CancellationPolicyResolver`), and any test that asserts the exact `policy_snapshot` keys (new `cancellation`) or the exact `allowed_actions` list (now also `cancel`, `share`, `rate`, `report`).
- `tests/Feature/Offers/*` that read `GET /provider/bookings/{booking}` (resource now has `allowed_actions`, `trip`, `cancellation_terms`; the customer phone is `null` unless `direct_phone_exposure` is on) and the channel-authorization tests (a dispatched provider can no longer join `booking.{uuid}` once another provider is assigned).
- `tests/Feature/Booking/*` that call `DispatchRecipientRepositoryInterface::clearForBooking` or `BookingEnteredSearch` with positional args; anything counting settings in the catalog (3 new keys: `ride_pin_max_attempts`, `rating_window_hours`, `rating_min_sample`) or enum translation completeness.
- `tests/Feature/Customers/*` for anonymisation and export (they now also scrub `arrival_evidence`, rating comments, report texts, share links, and export `ratings_and_reports`).

Helpers to add: `Tests\Concerns\BuildsTrips` with `assignedBooking(BookingStatus $status = ProviderSelected, array $policy = [])` (booking + approved provider + vehicle + accepted offer row + `agreed_fare_minor` 5000 + PIN `1234` stored with `Hash::make` and `Crypt::encryptString`, snapshot with `safety.pin_required = true`, a `cancellation` snapshot and `dispatch.auto_redispatch`), `actingAsProviderFor(Booking)`, `policySnapshotWith(array $cancellationRules)`, `CancellationPolicy::factory()` (+ `database/factories/CancellationPolicyFactory.php`).

### tests/Feature/Trip/ProviderTripLifecycleTest.php

- `test_full_happy_path_from_selected_to_completed`: acknowledge -> arrived (with GPS at the pickup) -> verify-start-pin (correct) -> start -> complete; each answers 200 with the booking, statuses `provider_heading_to_pickup`, `provider_arrived`, `in_progress`, `completed`; `booking_status_events` has contiguous sequences with actor `provider`; `heading_at`, `arrived_at`, `started_at`, `completed_at` set; `final_fare_minor == agreed_fare_minor`; cash booking ends `completed` with `payment_status = cash_due`; a non-cash booking ends `completed_pending_payment` with `pending`; provider `completed_jobs` +1; share links of the booking are revoked.
- `test_allowed_actions_follow_the_status`: for each status the provider resource lists exactly the expected actions (start only after the PIN is verified or when not required; `verify_start_pin` disappears when verified or locked; `rate_customer` after completion while the setting is on and not yet rated).
- `test_start_without_pin_is_422_booking_pin_required` and `test_start_without_pickup_proof_is_422_when_required` (`pickup_proof_required`; passes once a `booking_proofs` row of type `pickup_pin` exists).
- `test_start_not_required_when_category_has_no_pin`: snapshot `pin_required = false` -> start works from `provider_arrived`, `verify-start-pin` answers 422 `trip_pin_not_required`.
- `test_complete_requires_delivery_proofs_from_the_snapshot`: `delivery_proof_types = ['photo']` -> 422 `booking_proof_required` until a photo proof row exists.
- `test_complete_refuses_booking_without_agreed_fare`: `agreed_fare_minor = null` -> 422 `booking_fare_not_agreed`.
- `test_other_provider_gets_404_on_every_action`: acknowledge, arrived, verify-start-pin, start, complete, cancel, rating, reports with a second approved provider's token -> 404 for each; the booking is unchanged; a customer token on provider routes is 403.
- `test_actions_out_of_order_are_409`: start from `provider_selected`, arrived from `provider_selected`, complete from `provider_arrived` -> 409 `booking_transition_invalid`; a booking in another state is never changed.
- `test_repeating_an_action_is_a_replay_not_an_error`: acknowledge twice with different keys -> second is 200 with `meta.replayed = true` and exactly one status event; same key replays the stored response (`Idempotent-Replayed`); same key with a different body -> 422 `idempotency_key_reused`; missing key -> 422 `idempotency_key_required` on all six writes.
- `test_arrival_evidence_verified_within_radius_and_unverified_otherwise`: fix at the pickup -> `arrival_evidence.verified = true`, `distance_to_pickup_m` small; fix 5 km away, stale `recorded_at`, no fix and no last position -> stored with `verified = false`, the arrival still succeeds; no fix but a fresh `provider_last_locations` row -> `source = last_known`.
- `test_free_wait_timer_starts_at_arrival`: `free_wait_ends_at = arrived_at + policy_snapshot.cancellation.free_wait_seconds` (0 seconds when the snapshot has none).
- `test_active_endpoint_returns_current_job_or_null`: `GET /provider/bookings/active` returns the assigned booking, `null` after completion.
- `test_status_events_reach_customer_and_provider_channels`: `Event::fake` + assertDispatched for `TripStatusChanged` with channels `customer.{uuid}` and `provider.{uuid}` and `BookingStatusChanged` on `booking.{uuid}` for every transition, same sequence; after a provider cancel with re-dispatch the old provider also receives it.
- `test_provider_phone_is_masked_unless_direct_exposure_enabled`: `customer.phone` null and `phone_masked` set (setting off); real phone and `contact_mode = direct` when on.

### tests/Feature/Trip/TripPinTest.php

- `test_correct_pin_marks_verified_and_records_proof`: `pin_verified_at` set, one `booking_proofs` row (`pickup_pin`, actor provider); a second call with the PIN is a replay (no second proof); the customer's `trip_pin` is `null` afterwards and `trip.pin.verified = true`.
- `test_wrong_pin_counts_and_reports_attempts_remaining`: 422 `trip_pin_invalid` with `meta.attempts_remaining` 4, 3, 2, 1; `pin_attempts` persisted (the failed request must not roll the counter back).
- `test_attempt_limit_locks_start_and_flags_support`: 5th wrong entry -> 409 `trip_pin_locked`; `pin_locked_at`, `support_flagged_at`, `support_flag_reason = pin_attempts_exceeded`; a `pin_locked` note event; `TripSupportFlagged` dispatched to customer and provider channels; the correct PIN afterwards is still refused (409) and `start` stays 422; limit follows the `ride_pin_max_attempts` setting (set 2).
- `test_pin_only_before_arrival_is_409_trip_pin_not_ready`: from `provider_heading_to_pickup`.
- `test_pin_comparison_is_constant_time`: source grep that `TripPinService` uses `hash_equals` and never `===` on the PIN.
- `test_pin_is_visible_to_customer_only_while_waiting`: `trip_pin` present for `provider_selected`, `provider_heading_to_pickup`, `provider_arrived`; absent in `in_progress`, when verified and for another customer (404).
- Concurrency (`tests/Concurrency`): 8 parallel wrong PINs with limit 5 -> `pin_attempts` ends at 5, exactly one `pin_locked` note, no deadlock.

### tests/Feature/Trip/CancellationTest.php

- `test_customer_preview_writes_nothing`: `POST /bookings/{b}/cancel {reason_code, preview:true}` without an Idempotency-Key -> 200, `committed = false`, `data = null`, `meta.cancellation` complete; booking, events and offers unchanged.
- `test_preview_and_commit_agree`: for every stage and rule shape (fixed, percent, percent with cap, none, free grace, free wait, arrival unverified, driver asked to cancel) the committed `cancellation_fee_minor` and `cancellation_decision` equal the preview; committing without `accepted_fee_minor` or with another amount when a fee is due -> 409 `cancellation_fee_changed` with the fresh decision in `meta.cancellation` and the booking untouched; fee 0 needs no acknowledgement.
- `test_fee_decision_table`: `CancellationFeeCalculator` unit test (below) plus feature checks that searching is always free and a customer inside `free_grace_seconds` after `provider_selected_at` is free in every stage.
- `test_customer_can_cancel_from_draft_to_arrived_but_not_after_start`: 200 for draft, searching, offers_received (active offers become `expired`), selected, heading, arrived; 409 `booking_not_cancellable` for `in_progress`, `completed`, `expired_no_provider`; another customer's booking is 404; the customer can cancel while the account is restricted.
- `test_cancel_reason_rules`: a code of the provider list on the customer endpoint -> 422; a code excluded by the policy snapshot -> 422 `cancellation_reason_not_allowed`; `other` without a note -> 422 `cancellation_note_required`; note longer than 255 -> 422.
- `test_customer_cancel_is_idempotent`: same key replays; a second cancel with a new key on an already cancelled booking replays the stored decision (`meta.replayed`).
- `test_cancel_records_actor_reason_and_notifies_provider`: `cancelled_by_type = customer`, `cancellation_reason_code`, `cancellation_decision` JSON, status event metadata; `TripStatusChanged` to the assigned provider; share links revoked.
- `test_provider_cancel_with_auto_redispatch_returns_to_searching`: booking `searching_offers` with `provider_id`, `selected_offer_id`, `agreed_*`, `provider_selected_at`, `pin_hash`, `pin_encrypted`, `pin_attempts`, `pin_verified_at`, `pin_locked_at`, `heading_at`, `arrived_at`, `arrival_evidence`, `free_wait_ends_at`, `support_flag*` all cleared/zeroed, new `search_expires_at`, `request_version` unchanged; the old offer keeps status `accepted_by_customer`, `winner_marker = null`, `close_reason = provider_cancelled`, versions intact; `BookingEnteredSearch` has `restart` and `keepProviderIds = [provider]`; the cancelling provider's recipient row is `declined` and it is not dispatched again nor listed in `requests/available`; a second provider can win the booking afterwards (new PIN, new agreed fare) and the unique winner index does not block it.
- `test_provider_cancel_without_redispatch_ends_cancelled_by_provider`: snapshot `auto_redispatch = false` -> `cancelled_by_provider`, `provider_id` kept, fee decision stored, share links revoked.
- `test_provider_cancel_rules`: only from selected/heading/arrived (409 `booking_not_cancellable` from `in_progress`); customer-list reason -> 422; `customer_no_show` before arrival or before `free_wait_ends_at` -> 422 `no_show_too_early` with `meta.grace_ends_at`, accepted after; justified reasons carry no fee, `vehicle_problem` follows the provider rule; `preview=true` writes nothing and needs no key.
- `test_provider_cancel_reaches_customer_and_old_provider_channels`.
- Concurrency: provider cancel racing a customer accept/cancel on the same booking -> exactly one outcome, no half state (offer winner marker and booking assignment always agree).

### tests/Unit/Booking/CancellationFeeCalculatorTest.php

Table test with `Booking::factory()->make()` and snapshots: stages x actors x rules (none, fixed, percent half-up e.g. 1250 bp of 3333, cap, `value` 10000), `free_grace` boundary (`==` is free, +1 s is not), `free_wait` boundary, unverified arrival -> heading rule with basis `arrival_unverified`, justified provider reasons, `driver_asked_to_cancel`, no policy in the snapshot -> defaults (no fee), unsupported stage (`in_progress`, `completed`) -> `booking_not_cancellable`; `CancellationDecision::toArray()` shape; `CancellationPolicyResolver::resolve` precedence (zone > category > market default > built-in defaults, inactive / expired / future ignored).

### tests/Feature/Admin/Booking/CancellationPolicyAdminTest.php

Mirror of `FarePolicyAdminTest`: permission matrix (`cancellation_policies.manage` only; 403 otherwise), listing DataTable with every filter (name, services, zones, enabled, in effect on) and the filters card collapsed by default, create via Precognition (422 inline errors for bad percent, fixed amount of 0, searching stage, duplicate rule per actor+stage, empty reasons, reasons of the wrong side) then real post; overlap -> 422 `cancellation_policy_overlap` on create, update and activate; edit keeps scope; stale `lock_version` -> 409; deactivate and delete need a reason; delete refused with bookings (`cancellation_policy_in_use`); audit rows with before/after; new bookings snapshot the policy in force (`policy_snapshot.cancellation.id` = policy id, `bookings.cancellation_policy_id`) and editing the policy later does not change an existing booking's decision; sidebar entry only for the permission.

### tests/Feature/Trip/ShareLinkTest.php

- `test_customer_creates_share_link_for_assigned_booking`: 201 with `url` and `expires_at = now + share_link_ttl_minutes`; only the SHA-256 is stored (`token_hash` != token; token not in DB); a second call revokes the first; 409 `share_not_available` for searching, completed, cancelled; another customer's booking 404; `DELETE` revokes all.
- `test_public_page_minimises_data`: `GET /t/{token}` 200, `noindex` meta and `X-Robots-Tag`, `Cache-Control: no-store`, `Referrer-Policy: no-referrer`; body contains provider first name, vehicle, status; does NOT contain the customer's name or phone, addresses, fare, offer amounts, provider surname or phone, coordinates beyond 5 decimals.
- `test_position_only_when_fresh`: position from `provider_last_locations` shown when younger than 180 s, hidden when older; `/t/{token}/data` JSON shape.
- `test_expired_revoked_unknown_and_finished_links_look_the_same`: 404 with the same view for each (after `travel()` past the expiry, after cancel/complete, random 43-char token, malformed token -> route 404).
- `test_share_routes_are_throttled`: 61st request in a minute -> 429.

### tests/Feature/Trip/RatingTest.php

- `test_customer_rates_provider_once_after_completion`: 201 with tags and comment; the provider's `rating_avg` and `rating_count` recomputed; duplicate -> 409 `rating_already_submitted` (also under a forced race: two inserts, unique index `booking_ratings_one_per_side`); not completed -> 422 `rating_not_allowed`; after `rating_window_hours` -> 422 `rating_window_closed`; stars 0 / 6 -> 422; a tag of the customer list on a provider rating -> 422; comment over 1000 -> 422; another customer's booking 404.
- `test_provider_rates_customer_only_when_enabled`: setting off -> 422 `rating_disabled`; on -> 201 and `users.rating_avg` updated; one per booking.
- `test_aggregate_formula_and_provisional_flag`: `RatingAggregationService::average()` (e.g. stars 5,4,4 -> `4.33`; 5,5,4 -> `4.67`; half-up at the third decimal; none -> `0.00`); hidden / under_review ratings excluded after `recompute`; `ProviderIdentity::rating()['provisional']` true below `rating_min_sample`.
- `test_rate_action_in_allowed_actions`: `rate` present for a completed unrated booking inside the window, absent after rating, after the window and for non-completed bookings.

### tests/Feature/Trip/BookingReportTest.php

- 201 with a stored `booking_reports` row (`status = open`) for customer and provider; 422 `report_not_available` for a booking that never had a provider; daily limit (5) -> 422 `report_limit_reached`; description under 5 or over 2000 characters -> 422; unknown category -> 422; other customer's booking 404; no rating needed first.

### tests/Feature/Trip/CustomerBookingResourceTest.php

`GET /bookings/{b}` after each status: `trip` block, `provider_location_visible` only for assigned statuses, `cancellation` after a cancel, `final_fare` and `receipt` after completion (duration, planned distance, payment), `rating_window_ends_at`, `allowed_actions` list per status (cancel until arrival, share while assigned, rate/report after), timeline hides provider/operations reasons.

### tests/Feature/Realtime/TripChannelsTest.php

- `booking.location_updated` is dispatched as `TripLocationUpdated` on `customer.{uuid}` only (never `booking.*`) while the provider has an assigned booking, and not at all after completion or when the provider has no booking.
- `RealtimeChannelPolicy::joinBooking`: before selection a dispatched provider can join; after another provider is selected it cannot; the selected provider and the customer can.
- `booking.support_flagged` goes to both party channels with the booking sequence.

### tests/Unit

- `tests/Unit/Enums/CancellationReasonTest.php`: `forActor` lists are disjoint except `other`; every case of the new enums (`CancellationStage`, `CancellationFeeType`, `CancellationFeeRecipient`, `CancellationReason`, `SupportFlagReason`, `ReportCategory`, `ReportStatus`, `ModerationStatus`, `RatingTag`, `ProviderBookingAction`, `CancellationPolicyState`, `BookingAction` new cases) has `ar` and `en` translations; `CancellationStage::of` maps every `BookingStatus`.
- `tests/Unit/Booking/BookingTransitionServiceTest.php` (extend the exhaustive matrix test): the provider-cancel edge `provider_selected|heading|arrived -> searching_offers` needs the assigned provider (403 otherwise) and `auto_redispatch`; `searching_offers` from `offers_received` leaves the assignment columns untouched; `in_progress -> cancelled_by_customer` stays invalid; the source-grep test that only the transition service writes `bookings.status` still passes (nothing new writes it).
- `tests/Unit/Models/BookingProofTest.php`: append-only (update and delete throw).
- `tests/Unit/Support/Idempotency/PreviewBypassTest.php`: `idempotent:unless_preview` skips the key requirement only when `preview` is true; other unsafe calls still need the key.

### Console / seeders / misc

- Settings catalog test: the three new keys have type, bounds, default, labels in `ar` and `en`, and appear in Settings > Safety / Booking forms.
- Personal data: anonymisation nulls `arrival_evidence`, customer rating comments, report descriptions (`[removed]`) and deletes share links; the export contains `agreed_fare_minor`, `final_fare_minor`, `cancellation` and `ratings_and_reports`.
- `website` routes: `route('website.trip-share', ['token' => ...])` generates the URL returned by the share endpoint.

### Manual / browser

- With Reverb running: customer app subscribed to `private-customer.{uuid}` sees `booking.status_changed` for acknowledge / arrived / start / complete / provider cancel and `booking.location_updated` while the driver moves; a second dispatched provider subscribed to `private-booking.{uuid}` receives no location or offer data after the selection.
- Open a real share link in a phone browser: renders RTL in Arabic, refreshes status every 15 s, becomes the "not available" page after the trip ends; `curl -I` shows the hardening headers.
- Admin > Pricing > Cancellation policies in the browser: collapsed filters card, Precognition errors inline before submit, repeater adds/removes rules, Arabic RTL layout.

---

## P2.6 — Customer app ride flow (nothing below was written or run; Flutter tests are in `customer_app/test/...`)

Common setup: `ProviderScope` overrides for `bookingRepositoryProvider` (mocktail `MockBookingRepository`), `customerEventsProvider` (a fake hub with controllable streams; `CustomerEvents` has a private constructor, so add a `@visibleForTesting` factory or override `bookingTrackerProvider` inputs), `appConfigProvider`, `meProvider`, `mapTileProviderProvider` (no network), locale `ar` and `en` pumps, fixtures in `test/features/booking/booking_fixtures.dart` (booking JSON for each status, offers of both kinds).

### Unit
- `test/features/booking/domain/fare_input_test.dart`: `parseFareMinor` accepts `120`, `120.5`, `١٢٠٫٥٠`, `120,5`; rejects `abc`, `1.234` (exponent 2), empty, negative; exponent 0; `fareInputText` round trips; `checkFare` returns below/above/ok/invalid/empty.
- `test/features/booking/domain/polyline_test.dart`: decodes the Google reference string `_p~iF~ps|U_ulLnnqC_mqNvxq@` to the 3 documented points; truncated input returns the decoded prefix without throwing.
- `test/features/booking/data/booking_dto_test.dart`: `BookingDto.booking` maps every status, `allowed_actions` with unknown values to `BookingAction.unknown`, null provider/vehicle before selection, `trip_pin`, `reference_estimate` available false -> null guidance, timeline notes vs statuses; unknown enum strings never throw; missing pickup throws `invalid_response`. `offer` maps both kinds and rating `provisional`. `cancellationPreview` reads `fee`, `free`, `message`.
- `test/features/booking/data/booking_repository_test.dart` (mock `ApiClient`): `create` posts the documented body with the Idempotency-Key and `booking_type=on_demand`; `previewCancel` sends `preview: true` and no key; `cancel` sends `accepted_fee_minor` only when given and a key; `cancel` maps 409 `cancellation_fee_changed` to `CancellationFeeChanged` with the fresh fee; `list` sends `status` list, `service_category_id`, `from`/`to` as `Y-m-d`, `in_flight`; `active` requests `per_page=1&in_flight=1`; share/rate/report send keys.
- `test/features/booking/presentation/booking_actions_test.dart`: the same key is reused for an accept retried after a network error and dropped after success; 409 `booking_already_assigned`, `offer_expired` and 422 drop the key, refresh tracker and offers and rethrow; `changeFare` drops the key on 409; cancel intents with a different accepted fee use a different key.
- `test/features/booking/presentation/booking_draft_test.dart`: same payload keeps the key across a failed submit; an edited fare creates a new key; errors keep input; `submitting` blocks a double submit; success invalidates the active booking.
- `test/features/booking/presentation/booking_tracker_test.dart`: initial fetch; a `booking.status_changed` event refetches; `booking.location_updated` updates the fix without a refetch; events of other bookings are ignored; reconnect refetches; polling cadence (fake timers: 5 s without realtime, 20 s with); a failed refresh keeps the last snapshot; first-load failure becomes `AsyncError`; `apply` replaces the snapshot and clears the fix when the location is no longer visible.
- `test/features/booking/presentation/offers_test.dart`: `sortOffers` by price, ETA (nulls last) and rating; `OffersNotifier` refetches on `booking.offer_received|updated|expired`, `provider_selected` and on reconnect.
- `test/features/trips/trips_notifier_test.dart`: filter change reloads from page 1; `loadMore` appends and stops at the last page; a failed `loadMore` sets `loadMoreFailed` and keeps the items.
- `test/app/router_test.dart` (extend): `/booking/new` and `/booking/{id}` redirect to splash when signed out and to the stage route for a half-registered account; `Routes.booking/bookingCancel/bookingReport` build the expected paths. Update existing expectations that assume the old Home.

### Widget (RTL and LTR for each)
- `offer_tile_test.dart`: both kinds (counter-offer chip vs accepts-your-fare), price via `MoneyFormatter`, ETA/distance/rating/plate lines, provisional rating wording, per-offer countdown text updates with fake time and turns to "expired" with Accept disabled, busy state shows a loader and disables both buttons.
- `searching_view_test.dart`: search countdown and its end state; loading / empty (waiting) / error with retry / offline / session-expired states of the offer list; no-offers state after the window ended shows edit-fare and cancel and the "does not guarantee" text; sort chips only with 2+ offers; accept shows the confirm sheet, then calls accept once; 409 `booking_already_assigned` shows the message and refreshes; reject calls the repository.
- `edit_fare_sheet_test.dart`: warning text differs with active offers; invalid/below-min/above-max inline errors; success closes and shows the confirmation; 409 `booking_not_editable` message inline.
- `booking_form_screen_test.dart`: incomplete state when pickup/destination/category is missing; estimate loading/error(retry)/unavailable guidance; guidance card factors and notice; recommended fare prefilled once and never overwrites typing; suggestion chips set the fare; bounds helper text; below/above/empty fare errors; required dynamic-form field blocks submit; passengers stepper limits; submit disabled while submitting; a server error (`fare_below_minimum`, `outside_service_area`, offline) shows ARB text and keeps all input; success replaces the route with `/booking/{id}`.
- `live_trip_view_test.dart`: provider and vehicle card, agreed fare, PIN card only when `trip_pin` is set (always LTR digits), locked-PIN notice, free-wait countdown at `provider_arrived`, timeline steps from `trip` timestamps, stale banner after 45 s (`stale-location-banner`), waiting text without a fix, "updated N s ago", buttons only for actions listed in `allowed_actions` (share/cancel/report absent when not listed), call and chat buttons never shown, support-flagged notice, safety sheet content (support phone, emergency contact, SOS-later notice, nothing-configured text).
- `cancel_booking_screen_test.dart`: reason required, note required for `other`; continue calls preview with no key; free vs fee text; confirm sends `accepted_fee_minor` and a key; 409 `cancellation_fee_changed` shows the new fee and the message and requires another confirmation; `booking_not_cancellable` shows the message and refreshes; keep-my-request pops.
- `rating_card_test.dart` and `report_screen_test.dart`: stars required, tags switch positive/negative sets with the stars, comment optional, 409 `rating_already_submitted` message, rate card hidden without `rate` in `allowed_actions`; report needs category and 10+ characters, success pops with confirmation, 422 `report_limit_reached` message.
- `closed_view_test.dart`: completed shows the receipt (agreed and final fare, cash payment, `cash_due` text, duration, distance), cancelled shows the fee or "no fee", `expired_no_provider` shows try-again and edit-price (rebook prefilled via `rebookFrom`) and the no-guarantee text, rebook sets endpoints, category and fare then opens the form.
- `trips_screen_test.dart`: loading, empty, filtered-empty (with clear action), error, offline, session-expired; list tile shows service, code, status chip, fare; infinite scroll loads page 2; filter sheet sends statuses/service/dates; tap opens `/booking/{id}`.
- `home_screen_test.dart`: bottom navigation shows exactly Home, My Trips, Account and no Wallet/Offers while `features` is empty; the tab switches keep state; request button disabled with each hint (pickup, destination, service, active booking) and enabled when ready; the active-booking banner appears for an in-flight booking and opens it.
- `app_config_repository_test.dart` (extend): `features` map parsed, `featureEnabled` false for missing keys.
- `booking_map_test.dart`: provider marker present only when `provider` is set; polyline layer only with 2+ points.

### Goldens (RTL and LTR, ar and en)
`home`, `offers list` (one accepts-fare, one counter-offer, one near expiry), `live trip` (PIN card, provider card, stale banner), `receipt`, `cancel fee card`.

### Integration (`customer_app/integration_test/ride_flow_test.dart`, not written)
Against a local backend with the test OTP: sign in, set pickup/destination, choose a category, submit a fare, a seeded provider sends an offer (`php artisan qa:` helpers or the provider API), the offer appears, accept, the live trip screen shows provider and PIN, cancel preview shows the fee, commit. Document: `php artisan serve`, `php artisan reverb:start`, `queue:work`, seed the fare policy and an eligible provider, then `flutter test integration_test/ride_flow_test.dart --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1 --dart-define=REVERB_KEY=... `.

### Manual
- Arabic RTL walk-through on a device: all new screens mirror correctly (map controls, timeline rail, chips); text scale 1.3 does not clip offer cards; TalkBack reads offer price, expiry and the PIN.
- Airplane mode during the search: offline banner, polling resumes on reconnect; killing the socket keeps offers fresh through polling.
- Two devices: accept the same offer from the second customer account is impossible (404); a provider cancel returns the customer to the offers view.
- Share sheet delivers a working `/t/{token}` link; "stop sharing" makes it 404.

---

## P2.7 — Driver app ride flow (nothing below was written or run; Flutter tests are in `driver_app/test/...`)

Common setup: `ProviderScope` overrides for `jobRepositoryProvider` (mocktail `MockJobRepository`), `providerRealtimeEventsProvider` (a `RealtimeEventBus` the test pushes into), `realtimeControllerProvider` (fixed status), `availabilityProvider`, `locationServiceProvider` (fake `current()`), `sharedPreferencesProvider` (`SharedPreferences.setMockInitialValues`), `appConfigProvider`, locales `ar` and `en`; fixtures in `test/features/jobs/job_fixtures.dart` (request card JSON, offer JSON per state, job JSON per status with `allowed_actions`).

### Unit
- `test/features/jobs/domain/amount_input_test.dart`: `parseAmountMinor` accepts `120`, `120.5`, `120.`; rejects empty, `1.234` (exponent 2), `abc`; exponent 0; `amountInputText` round trips; `checkAmount` returns empty/belowMin/aboveMax/sameAsFare/ok.
- `test/features/jobs/data/job_dto_test.dart`: `RequestCard` from the masked card (no address fields, counter bounds, `my_offer`); feed with `meta.eligible=false` and typed reasons; `MyOffer` states and unknown strings never throw; `earnings_preview.available=false` -> null; `Job` maps trip pin (required/verified/locked/attempts_remaining), terms, customer masked/direct phone, unknown actions -> `JobAction.unknown`; missing pickup throws `invalid_response`; cancellation preview `fee`, `free`, `message`.
- `test/features/jobs/data/job_repository_test.dart` (mock `ApiClient`): accept sends the key and `eta_minutes` only when set; counter body `{amount_minor, currency}`; `previewCancel` sends `preview: true` and no key; `cancel` sends the key and reads `committed/redispatched`; `activeJob` returns null for `data: null`; transition paths (`verify-start-pin`, `arrived` evidence body).
- `test/features/jobs/presentation/job_actions_test.dart`: same intent after a network error reuses the key; success clears it; 409/422 final clears it; `idempotency_request_in_progress` keeps it; two quick taps share one call; counter with another amount uses another key; `arrived` reuses the same evidence body on retry and does not fail without GPS; lost codes invalidate `requestsProvider` and `myOffersProvider`; a transition applies the job to the tracker and refreshes availability; `trip_pin_invalid` refreshes the tracker.
- `test/features/jobs/presentation/providers_test.dart`: `RequestsNotifier` initial fetch (no alert), a new id after refresh plays the alert only per preferences, `booking.requested` and reconnect refetch, polling cadence (fake timers 6 s / 25 s), a failed refresh keeps the list, first-load failure -> `AsyncError`, `drop` removes a card; `MyOffersNotifier` refetch on offer events; `ActiveJobNotifier` null when signed out, restores the job on `refresh`, a status event for a released booking yields null; `JobTracker` 404 -> error, `apply` updates the active job (null when finished); `jobHistoryProvider` keeps only selected offers, deduplicated by booking; `sortMyOffers` order.
- `test/app/router_test.dart` (extend): `/job/:id`, `/job/:id/cancel`, `/job/:id/report`, `/application` redirect to splash when signed out and stay for a signed-in stage; `Routes.job` paths.

### Widget (RTL and LTR for each)
- `request_card_tile_test.dart`: shows masked area, distance, fare, chips; countdown text updates with fake time and turns to "expired" with all actions disabled; accept calls the repository once on a double tap; counter button hidden when `counter.allowed` is false; with a pending offer shows "your offer", revise and withdraw instead of accept/skip; lost error shows the lost message and refreshes the feed; skip drops the card.
- `counter_offer_sheet_test.dart`: keypad builds the amount; backspace; decimal only when exponent > 0; below min / above max / same-as-fare show inline text and disable send; bounds text from the card; success pops the offer; with `earnings_preview.available` shows gross/commission/net and a done button, otherwise no preview; server `offer_amount_out_of_bounds` shows the error and keeps the amount; lost code closes the sheet.
- `requests_tab_test.dart`: loading / empty / error with retry / offline / session expired; not-online notice with the action; not-eligible notice with reasons; pull to refresh.
- `offers_tab_test.dart`: pending first then closed; each state chip and reason text; withdraw calls the repository; selected shows open job; empty and error states.
- `history_tab_test.dart`: rows show code/status/amount from the job; row error falls back to the offer amount; empty state; tap opens `/job/{id}`.
- `job_view_test.dart`: buttons only for `allowed_actions` (acknowledge, arrived, pin, start, complete, cancel, report absent when not listed); arrived sends once on double tap; PIN button disabled with the locked label when `pin.locked`; complete asks confirmation with the cash amount; free-wait countdown at `provider_arrived`; support-flagged notice; call button only with a direct phone, masked note otherwise; navigate button targets pickup then destination (mock `url_launcher` platform); finished status shows the summary; conflict error refreshes the tracker.
- `pin_sheet_test.dart`: digits fill the display (LTR), submit needs 4 digits, `trip_pin_invalid` shows attempts left and clears the entry, `trip_pin_locked` shows the locked text and a close button only, success pops true, busy state blocks input.
- `cancel_job_screen_test.dart`: reasons from the terms (fallback list when empty); choosing a reason loads the preview (free text vs fee with the formatter); `other` needs a note before the preview; confirm sheet then commit; re-dispatch outcome returns home with the message; 409 refreshes the job; not-cancellable state.
- `report_job_screen_test.dart`: needs category and 10+ characters; error keeps input; success state.
- `completion_summary_test.dart`: amount, payment method, cash-to-collect text, net preview only when present; rating card (stars required, tags, once) and thanks state; cancelled status variant; back-home button.
- `driver_shell_test.dart`: four tabs, badge count of requests, active-job banner and its open button, a newly seen active job is pushed once, resume lifecycle refreshes active job and lists, no earnings tab exists.

### Integration (`integration_test`, local backend with test OTP)
- go online -> request arrives -> counter-offer -> customer selects -> acknowledge -> arrived -> PIN -> start -> complete -> summary and rating; plus: kill and reopen the app mid-trip restores the job from `/provider/bookings/active`; provider cancel with re-dispatch drops the job; a second driver losing the race sees the lost message.

### Manual
- With Reverb running: a new request makes the sound/vibration per preference; the connection indicator follows a network cut and the lists refetch after reconnect; Android foreground notification and tracking interval change to_pickup/active/idle across the transitions; the maps hand-off opens Google Maps with the pickup then the destination.


---

## P2.8 — Admin bookings, offers monitor, live operations v1

Status: **no P2.8 test was written or run** (user instruction). A rolled-back tinker scenario exercised extend / re-dispatch / cancel (waive) / re-cancel (409) / out-of-graph force (422) / PIN lift / forced start with PIN bypass / audit rows / timeline events / view rendering, and read-only runs covered every new query and DataTable JSON; none of that replaces the tests below. No browser smoke and no vitest run was done either.

Likely to break in existing tests and why:
- Tests that build `BookingPolicy` abilities with a `User` only: `view` now takes `Authorizable` (admin permission or owner); `viewAny`, `viewPii`, `cancelByOperations`, `manage`, `forceTransition`, `liftPinLock` are new. The admin authorization-guard test (every admin route has `can:` or `admin.access:self`) now also scans `routes/admin/bookings.php`; the route-list snapshot test, if any, changes.
- `tests/Feature/Location/LocationIngestTest.php`: ingest now dispatches `ProviderPositionRecorded` (a listener publishes the operations feed); with `Event::fake()` unfiltered, assertions on dispatched events may need `assertDispatched` instead of `assertNothingDispatched`.
- Enum translation completeness tests: new enums `BookingOverrideAction`, `OperationsCancellationFee`, `OfferAnomaly` (keys in `lang/{ar,en}/enums.php`), `OfferStatus` and `DispatchResponse` now implement `HasColor`. Lang-parity test: new keys in `admin.php` (`bookings`, `offers`, `operations`, `realtime`, `ratings`, `customers.ratings`, `providers.jobs|earnings`, `js.realtime|operations`) and `api.php` errors (`override_*`).
- Admin menu tests: four new items in the `operations` group; `AdminMenu` now splits `active` on `|`.
- Tests that construct `OfferRepositoryInterface`, `BookingRepositoryInterface`, `RatingRepositoryInterface`, `BookingReportRepositoryInterface` or `DispatchRecipientRepositoryInterface` fakes (new methods), the physical-utility grep test over admin views (new views use logical utilities only), `tests/Feature/Admin/Customers/*` for the Ratings tab (placeholder replaced by a DataTable) and the Providers show test for the Jobs/Earnings tabs.
- `tests/Feature/Provider/*` or API route tests that assume `GET /provider/bookings` does not exist (it is now the paginated history; `bookings/active` and `bookings/{booking}` keep working because the static segment is registered first).

Helpers to add: `Tests\Concerns\ActsAsAdmin` with `adminWith(AdminPermission ...$permissions)` (Spatie role with exactly those permissions, `actingAs($admin, 'admin')`), `Tests\Concerns\BuildsOperations` with `searchingBooking(array $attrs = [])`, `assignedBooking(BookingStatus $status, array $policy = [])` (approved provider + vehicle + accepted offer, PIN `1234` encrypted), `lockedPinBooking()`, `offerWithAmount(Booking $booking, int $minor)` (offer + version, `reference_estimate` with `low_minor`/`high_minor`), `BookingOffer::factory()`.

### tests/Feature/Admin/Booking/BookingListingTest.php

- `test_guest_is_redirected_and_admin_without_permission_gets_403`: `/admin/bookings`, `/admin/bookings/{uuid}`, every tab URL, `no-offer-queue`, `operations/map`, `operations/map/snapshot`, `/admin/offers` -> guest 302 to login; admin without `bookings.view` (or `offers.view`) 403 for the page, the JSON/DataTables call and each tab; support role (with `bookings.view`) 200.
- `test_datatable_respects_every_filter`: one test per filter (code partial, multi statuses, service category ids, booking types, city ids, zone ids, payment statuses, payment methods, created range inclusive of the end day, customer phone full number and last-4 suffix, provider phone, `has_incident` true/false via support flag and via a report, a 3-digit phone matches nothing); the filters card is collapsed by default (HTML contains `x-data="filtersCard"` and the table id) and the first draw honours `?filters[...]`.
- `test_listing_never_leaks_contact_data`: JSON has masked phones only (`PhoneNumber::mask`), no addresses, no `service_details`, no `pin_*` keys; the response keys equal the `only()` allowlist; ordering by a non-allowlisted column is ignored; `order[0][column]=fare` sorts by `proposed_fare_minor`.
- `test_row_renders_status_flag_and_agreed_fare`: support-flagged booking shows the "needs support" badge; fare column shows the agreed fare once a provider was chosen, the proposed one before.
- `test_customer_bookings_tab_links_to_booking_page` and `test_provider_jobs_tab_lists_only_that_providers_bookings` (masked customer, view link; other providers' bookings absent; 403 for admin without `drivers.view`), `test_customer_ratings_tab_lists_received_ratings_only` (ratings the customer gave are absent; `ratee_type = customer`), `test_provider_earnings_tab_sums_completed_jobs_per_currency` (only `completed` and `completed_pending_payment`, `final_fare_minor`, no commission figure, placeholder text for P3.4 present).

### tests/Feature/Admin/Booking/BookingShowTest.php

- `test_show_page_renders_summary_and_tabs`: booking in several statuses renders; tab URLs return fragments (timeline, snapshots, safety, ratings) and DataTables (offers, dispatch, audit) with the right JSON.
- `test_phones_masked_and_addresses_need_view_pii`: without `bookings.view_pii` no address text in the page, the map data-point is rounded to 2 decimals and the masked-route notice shows; with it the exact address and 6-decimal point show and exactly one `viewed` audit row (`meta.pii = true`) is written per open; arrival evidence appears in the snapshots tab only with the permission.
- `test_pin_is_never_exposed`: no response of the show page or tabs contains the PIN, `pin_hash` or the encrypted value (page, safety tab, snapshots JSON viewer); safety tab shows required / verified / locked / attempts and the max from the setting.
- `test_offers_tab_lists_every_version`: offer with 3 versions shows v1..v3 with amounts and notes, status badges, anomaly badge, close reason; versions cannot be changed (no route accepts a write).
- `test_dispatch_tab_lists_recipients_by_wave`; `test_timeline_labels_annotations_and_status_changes` (request_changed, pin_locked, pin_lock_lifted, search_extended_by_operations, redispatch_requested_by_operations show their translated title; status changes show badge, "from" status and actor; reasons shown);
- `test_ratings_tab_shows_both_sides_and_moderation_status`; `test_audit_tab_lists_override_with_reason_and_actor`.
- `test_available_override_buttons_follow_status_and_permission`: searching booking with `bookings.manage` only shows extend and re-dispatch, not cancel/force/PIN; an arrived booking with a lock and `bookings.pin_override` shows "Lift PIN lock"; terminal statuses show none; admin with view-only sees none.

### tests/Feature/Admin/Booking/BookingOverrideTest.php

Common for each action (data provider over the five actions): no permission -> 403 (route and FormRequest); missing or too short `reason` -> 422 on the precognitive request (header `Precognition: true`) and on the real one, and nothing changes; valid precognitive request -> 204 without persisting; success writes exactly one `admin_audit_logs` row (`action = overridden`, `auditable_type = booking`, `reason`, `meta.override`, before/after snapshots without phone, address or PIN); `BookingNotifier::operationsOverride` is called once with the action (spy) and logs one line per party; an `OperationsFeedEvent` booking_changed is dispatched; response is the AdminFormResponse (`close_modal`, `reload` or `reload_table` per `context`).
- `test_cancel_by_operations_waive_fee_ends_booking_and_closes_offers`: searching and assigned bookings -> `cancelled_by_operations`, active offers `invalidated`/closed with reason `booking_cancelled`, accepted offer and every offer version untouched, `cancelled_by_type = admin`, `cancellation_reason = reason`, `cancellation_reason_code = operations_override`, `cancellation_decision.basis = waived_by_operations`, `cancellation_fee_minor = 0`, one status event with actor `admin`.
- `test_cancel_apply_policy_uses_the_booking_snapshot`: arrived booking with a percent rule -> fee equals `CancellationFeeCalculator` for the customer actor, decision actor `admin`, basis `operations_applied_policy`; in-progress booking with `apply_policy` -> 422 `override_fee_policy_unavailable`, with `waive` -> cancelled with null fee.
- `test_cancel_not_possible_from_final_or_completed_statuses`: `completed`, `cancelled_*`, `expired_no_provider`, `disputed` -> 409 `override_not_available`, status unchanged, no audit row.
- `test_extend_search_moves_window_and_queues_jobs`: `search_expires_at` = max(old, now) + seconds, `lock_version` +1, status event `search_extended_by_operations` (no status change), `ExpireBookingSearch` delayed to the new time and `DispatchBookingWave` for `lastWave + 1` queued (Queue::fake); 29 and 1801 seconds -> 422 on the field; non-searching booking -> 409.
- `test_redispatch_now_restarts_dispatch_and_keeps_offers`: `BookingEnteredSearch` dispatched with `restart = true`, recipients cleared, active offers and versions untouched; window extended by the default when it would end within 30 s, untouched otherwise; event `redispatch_requested_by_operations`.
- `test_force_transition_cannot_leave_the_graph`: for every status x every `BookingStatus` target that is not an edge, and every edge outside the allow-list (`provider_selected`, the three cancellations), the request is 422 `override_target_invalid` (FormRequest `Rule::in`) and the service call throws the same; no row changes (data-driven from `BookingStatus::allowedTransitions()`).
- `test_force_transition_goes_through_the_transition_service`: arrived -> in progress sets `started_at`, increments `lock_version`, appends a status event with `metadata.forced = true` and actor `admin`; to `expired_no_provider` closes active offers; to `completed` sets `final_fare_minor` and the payment status; guard failures keep their codes (`booking_proof_required`, `booking_fare_not_agreed`); a test asserts no code path other than `BookingTransitionService` writes `bookings.status` (the model gate throws otherwise).
- `test_force_start_without_pin_needs_pin_override_permission`: arrived + `pin_required` + unverified: with `bookings.assign_override` only -> 403 `override_forbidden`; with both permissions -> `in_progress`, `metadata.pin_bypassed = true`, audit summary `pin_bypassed`.
- `test_lift_pin_lock`: locked booking -> `pin_locked_at` null, `pin_attempts` 0, support flag cleared only when its reason is `pin_attempts_exceeded` (another reason stays), event `pin_lock_lifted`, `TripStatusChanged` same-status event to customer and provider channels with `pin_lock_lifted = true`; the provider can verify the PIN again afterwards (end-to-end through `TripPinService`); not locked or wrong status -> 409; needs `bookings.pin_override` (a `bookings.manage` admin gets 403); the PIN is never in the response, audit row or logs.
- `test_contact_customer_sends_inbox_message`: creates a `customer_messages` row visible at `GET /me/messages`, audit `message_sent` on the customer with the reason, anonymised customer -> 422 `customer_anonymised`, needs `bookings.manage`.
- `test_override_forms_are_served_per_ability`: `override-forms/{form}` returns the fragment (reason textarea required, `x-admin.form` with `data-ajax-form`) for each form, 403 without the ability, 404 for an unknown form; `context=queue` makes the action URL carry it.
- `test_overrides_race_with_customer_and_provider_actions` (belongs in the P2.9 concurrency suite): override and customer cancel / provider arrival at the same moment -> exactly one wins under the booking lock, the loser gets 409, never two status events for the same edge.

### tests/Feature/Admin/Booking/NoOfferQueueTest.php

- `test_queue_lists_searching_bookings_without_active_offers_past_the_threshold`: booking older than `no_offer_after_seconds` without active offers listed; younger one absent; older with an active (`submitted`/`counter_offered`) offer absent; with only expired/withdrawn offers listed; provider-assigned booking absent (`Carbon::setTestNow`).
- `test_queue_lists_recent_expired_no_provider_only`: `expired_no_provider` updated within the window listed, older absent; other final statuses absent.
- `test_row_actions_follow_status_and_permission`: searching rows have view, re-dispatch, extend, contact, cancel (modal URLs with `context=queue`); expired rows have view and contact only; `bookings.view` only admin sees view only; response contains waiting time and "N notified, M offers" counts.
- `test_queue_actions_reload_the_queue_table`: POST with `?context=queue` answers `reload_table = [no-offer-queue-table]` and no `reload`.

### tests/Feature/Admin/Operations/OperationsMapTest.php

- `test_snapshot_contains_online_fresh_providers_and_active_bookings_only`: online providers with a position newer than `map_position_fresh_seconds` included (busy flag true for assigned ones, category = vehicle category code), offline/stale excluded; bookings in searching + assigned statuses included, final ones excluded; `truncated = true` past `map_limit`.
- `test_snapshot_has_no_pii`: JSON contains only the documented keys; no name, phone, address, customer id or provider profile id (uuid only); coordinates rounded to 5 decimals; the page HTML embeds the same data only.
- `test_snapshot_requires_bookings_view` and the page 403 without it; guest 302.
- `test_operations_channel_authorization` (in `tests/Feature/Realtime/AdminOperationsChannelTest.php`): `POST /broadcasting/auth` with the admin session for `private-admin.operations` -> 200 for an admin with `bookings.view`, 403 for one without, 403 for a Sanctum mobile token (guard bound to `admin`), guest 403/401.
- `test_feed_publishes_hints_without_pii`: `BookingStatusChanged` -> `OperationsFeedEvent booking_changed {uuid, status, previous_status}` on `private-admin.operations`; `ProviderAvailabilityChanged` -> `provider_changed {uuid, online}`; location ingest of an online provider -> `provider_moved {uuid, lat, lng, busy}` at most once per `map_move_throttle_seconds` per provider (cache key), none for an offline provider; the sequence is strictly increasing across the three; `broadcastQueue` is critical and the events are after-commit.
- `test_realtime_config_only_when_configured`: layout JSON has `realtime` null without `REVERB_APP_KEY` or when `BROADCAST_CONNECTION` is not `reverb`, and no secret in any case.

### tests/Feature/Admin/Booking/OffersMonitorTest.php

- `test_guest_and_permission`: `offers.view` required for page and JSON; `offers.moderate` alone is not enough.
- `test_filters`: booking code, provider ids, statuses, kinds, amount range (major units to minor, inclusive), anomaly flag, created range, each narrowing the rows and the `stats` payload together.
- `test_anomaly_flag`: reference `low 4000 / high 8000`, offer 1999 -> `very_low`, 2000 -> none (boundary at ratio 0.5), 16000 -> none, 16001 -> `very_high`; booking without reference estimate -> never flagged; the current version amount is used (not an older version); flags are not stored (no column) and an anomalous offer can still be selected by the customer.
- `test_stats`: submitted / selected / conversion percent / flagged / response rate for a seeded set (including bookings without offers in range), `0.0%` on empty data, stats follow the filters, and the JSON of every draw carries `stats` (the page tiles show the same numbers on first load).
- `test_monitor_is_read_only`: no write route exists under `/admin/offers`; offer versions stay append-only (model refuses update/delete).

### tests/Feature/Api/Provider/JobHistoryTest.php

- `test_returns_own_jobs_newest_first_paginated`: `GET /api/v1/provider/bookings` -> only bookings with `provider_id = me`, order `created_at desc, id desc`, `per_page` 1..50 clamped (0 -> default 20, 100 -> 50), pagination meta; items match `ProviderJobSummaryResource` (no `phone`, no address keys, `customer` has only `name`).
- `test_other_providers_and_customers_cannot_see_it`: a second provider sees only theirs; a customer token -> 403; guest -> 401; suspended provider can still read (status-safe route).
- `test_route_order`: `GET /provider/bookings/active` and `/provider/bookings/{uuid}` still resolve to their controllers.
- `test_booking_returned_to_searching_leaves_the_history` (documented behaviour of ADR-114).

### tests/Unit

- `OfferStatus` / `DispatchResponse` colours cover every case; `BookingOverrideAction::permission()` mapping (cancel -> `bookings.cancel`, extend and re-dispatch -> `bookings.manage`, force -> `bookings.assign_override`, lift -> `bookings.pin_override`); `OfferStats::conversionPercent()` / `responsePercent()` rounding and zero division; `BookingAdminFilterData` / `OfferFilterData` round trips through `toArray()` and `fromRequest()` (unknown enum values dropped, `forProvider` / `forBooking` not part of the URL form); `BookingOverrideService::forceTargets()` equals `allowedTransitions()` intersected with the allow-list for every status; `OperationsFeedPublisher::providerMoved` throttle (cache fake).

### JS (vitest, `resources/js/admin/core/*.test.js`)

- `realtime.test.js` (stub `laravel-echo` and `pusher-js`): disabled without a key (status `disabled`, no import); state map (connected / connecting / unavailable / failed); `subscription_error` -> `failed`; events call listeners with `gap = false`, a sequence jump of 2 or more -> `gap = true`; `lastEventAt` updates; `initRealtimeStatus` fills label, dot class and time and starts the connection once.
- `live-refresh.test.js`: remembered choice from `localStorage` (and works when storage throws), timer reload only while `document.hidden` is false, `booking_changed` and gap debounce one reload after 1.5 s, switching off clears timer and listener.
- `operations-map.test.js` (stub Leaflet and markercluster): snapshot creates markers and counters, a second snapshot removes vanished items, `provider_moved` moves a known provider and ignores an unknown one, `provider_changed offline` removes, `booking_changed` with a final status removes and with an unknown active one schedules a refetch, gap schedules a refetch, tooltips contain id/category/status and never anything else, layer toggles add/remove the cluster groups.
- `datatables.test.js` addition: `stats` in the draw JSON fills `[data-stats-for="id"] [data-stat]` elements.

### Manual / browser

- Arabic RTL walk-through of bookings list, show page (tabs, modals, banners), no-offer queue, offers monitor, live map (controls, tiles, filters card collapsed, layer toggles, tooltips) at 1280 px and phone width; no horizontal page scroll.
- With Reverb running: open the map and the bookings list with Live refresh on; create a booking from the customer app, select an offer, move the driver app through the trip: markers and rows update within ~2 s, the indicator shows "Live" and the time of the last event; stop Reverb: indicator turns to "Reconnecting…", the map keeps refreshing by polling every 30 s and the list by the timer; restart Reverb: it recovers and refetches after a gap.
- Run every override on a real booking with both apps open: the customer and driver apps react (cancelled, window extended, PIN pad unlocks, forced status) and the log shows one "notification stub" line per party.
- Clustering with a few hundred seeded providers (`demo` seeder or a script) stays responsive; hovering shows only the id/category.


## P3.1

### tests/Unit/Finance (setup: Money/EGP, CommissionTerms 10% percentage, PriceBreakdownCalculator + SettlementPostingBuilder, LedgerService on the test DB)
- `test_example_card_no_coupon`: fare 100 -> Dr gateway 100 / Cr provider 90, revenue 10; balanced.
- `test_example_card_platform_coupon`: Dr gateway 80, promo 20 / Cr provider 90, revenue 10.
- `test_example_cash_no_coupon`: Dr provider 10 / Cr revenue 10; wallet -10.
- `test_example_cash_platform_coupon`: Dr promo 20 / Cr revenue 10, provider 10; driver nets 90.
- `test_example_cash_provider_coupon`: Dr provider 10 / Cr revenue 10; driver nets 70.
- `test_example_tip_by_card`: Dr gateway 15 / Cr provider 15; commission-free; with commission_on_tips the split uses tip commission.
- `test_example_payout_reserve_then_paid`: reserve Dr provider 200 / Cr payout_clearing 200, paid Dr clearing / Cr gateway; second reserve on empty wallet with nonNegativeAccountIds refused.
- `test_post_rejects_unbalanced_empty_nonpositive_and_currency_mismatch`.
- `test_post_is_idempotent_and_key_reuse_with_other_lines_is_409`; concurrent same-key posts produce one entry (Concurrency suite).
- `test_negative_balance_rules`: customer wallet refused below 0, provider wallet allowed, credit_limit respected, account already negative may receive money.
- `test_reversal`: opposite lines, balances restored, second reversal returns the first, reversal of a reversal refused, audit row written.
- `test_lock_order_is_ascending_ids` (query log) and a two-process deadlock test posting A->B and B->A.
- `test_entries_are_immutable`: model update/delete throw.
- `test_money_allocate_has_no_lost_minor_unit` and discount funding split sums exactly; hybrid min/max/cap-at-base; half-up rounding.
- `test_commission_resolution_precedence`: priority, specificity, newest start, id, currency filter, inactive/expired/scheduled ignored, default setting fallback.
- `test_commission_snapshot_immutable`: editing/disabling a rule or adding a version after selection does not change the booking snapshot or breakdown; snapshot cleared when the booking returns to searching and rebound at the next selection.
- `test_financial_position`: cash debt equals -wallet balance, openItems counts non-zero wallets, CashDebtLimitExceeded in eligibility above the limit setting.

### tests/Feature
- `Booking/CashSettlementTest`: provider completion stores price_breakdown, posts one entry, sets settled_at; replay posts nothing; operator force-complete does the same; card booking stores breakdown only.
- `Console/ReconcileLedgerTest`: ok report; tampered balance gives mismatch, critical log, non-zero exit; unbalanced entry and currency mismatch detected.
- `Admin/Finance/*`: guest redirect, 403 without permission (page, JSON, datatable), each filter of commission rules / accounts / entries, precognitive 422/204 for commission rule forms, audit row on create/update/deactivate, deactivate without reason rejected, version added only when terms change, stale lock_version 409, wallet tabs need finance.view or wallets.view, reconciliation run needs finance.reconcile and is audited.

## P3.2

Setup common to the payment tests: `config(['integrations.payments.driver' => 'sandbox'])`, `Queue::fake()` (the sandbox's webhook job is then delivered by the test through `SandboxPaymentGateway::signedRequest()` + `PaymentWebhookService::receive()`), a customer with `CustomerProfile`, an approved provider, a card booking in `completed_pending_payment` with a stored price breakdown (`BookingSettlementService::onCompleted`), 10000 EGP-minor fare and 10% commission. Admin tests use `InteractsWithAdmin`.

### tests/Unit/Payments
- `PaymentStatusTest::test_transition_graph`: every edge of `allowedTransitions()` (paid only from pending / requires_action / authorized / failed / reconciliation_required; refunded only from paid / partially_refunded; cash_collected only from cash_due; refunded and cash_collected final); `isOpen`, `awaitingGateway`, `isCollected`.
- `RefundPostingBuilderTest`: full refund reverses every line of the settlement entry; partial refund with and without coupon lines: gateway line is exactly the refund, entry balances, no minor unit lost (property-style over many amounts, including 1 minor unit and odd splits); error when the gateway line is missing.
- `SandboxPaymentGatewayTest`: `verifyWebhook` accepts its own signed request; rejects missing / malformed header, tampered body, wrong secret (`InvalidWebhookSignatureException`); rejects a timestamp older / newer than the tolerance (`WebhookReplayException`); `createIntent` never returns `paid`; `queryStatus` / `listTransactions` reflect `applyBankSide`; `capture` unsupported.
- `PaymobPaymentGatewayTest` (HTTP faked): HMAC verification with the documented field order for TRANSACTION and TOKEN callbacks, bad HMAC rejected, event kind mapping (success / pending / failed / refunded / voided), event id stable across redeliveries; `createIntent` request body (cents, integration id, special reference, notification url, card_tokens for a saved method) and `RequiresAction` result with the checkout URL; `queryStatus` mapping; `refund` request and processing result; credentials missing -> `PaymentGatewayException` without secrets in the message; setup intent unsupported.
- `IntegrationGuardTest` addition: `payments=sandbox` refused in production / staging, allowed in local / testing.
- `PaymentMethodAvailabilityTest`: cash only without a gateway; card and mobile wallet added when a gateway is configured.

### tests/Feature/Payments
- `PaymentWebhookTest::test_invalid_signature_is_rejected_and_recorded`: 401, event stored with `signature_valid = false`, outcome `invalid_signature`, no payment touched.
- `PaymentWebhookTest::test_replayed_old_timestamp_is_rejected`: signed correctly but outside the window -> 400 `replay_window`, nothing applied.
- `PaymentWebhookTest::test_duplicate_event_is_applied_once`: same event delivered twice (and twice concurrently, `Concurrency` suite) -> second is `duplicate`, one ledger entry, one `webhook_applied` timeline row, `attempts` incremented.
- `PaymentWebhookTest::test_success_marks_paid_settles_and_completes_booking`: payment `paid`, `paid_at`, `settled_at`, balanced `booking_settlement_gateway` entry (Dr gateway clearing 10000 / Cr provider wallet 9000, platform revenue 1000), booking `completed` through `BookingTransitionService` (status event written), `bookings.payment_status = paid`, `payment.status_changed` dispatched to customer and provider.
- `PaymentWebhookTest::test_out_of_order_events`: failed then late success -> paid; success then stale failed (older and newer timestamp) -> ignored, status stays paid, `webhook_ignored` row; requires_action after failed ignored; requires_action only from pending.
- `PaymentWebhookTest::test_amount_or_currency_mismatch_is_never_applied`: outcome `amount_mismatch` (202), payment `reconciliation_required`, no ledger entry, booking untouched; processing it again after the payment is corrected by an operator works.
- `PaymentWebhookTest::test_unknown_reference_is_unmatched_and_retryable`: 202, event stored; after the payment exists the same event id is processed (not a duplicate); `DeliverSandboxWebhook` releases while unmatched.
- `PaymentWebhookTest::test_processing_failure_returns_500_and_is_retryable`: a throwing processor leaves the event `failed` with `processed_at` null; redelivery processes it.
- `PaymentWebhookTest::test_paymob_success_requires_confirming_query`: query disagrees -> outcome `failed`, payment unchanged; query agrees -> paid.
- `PaymentWebhookTest::test_endpoint_only_answers_for_the_configured_gateway`: other gateway name and unknown name 404; no auth needed; throttled by `payment-webhooks`.
- `FlutterCallbackTest::test_app_return_alone_never_marks_paid`: no endpoint accepts a client "paid" claim; `POST /payments/{payment}/refresh` with the gateway still pending leaves the payment pending and the booking `completed_pending_payment`; with the gateway reporting success it becomes paid by the query path (`query_applied`); another customer's payment is 404; gateway down -> 503.
- `PaymentIntentTest`: creates pending payment with amount = customer payable minus tip, never a client amount; `amount_minor` is prohibited on the trip endpoint; second request resumes the same attempt (200, no second row or gateway call); failed attempt allows attempt 2; `reconciliation_required` blocks a new attempt (`payment_needs_review`); cash booking, wrong status, other customer's booking, no gateway configured (`503 payments_not_configured`), wallet without phone; saved method of another customer refused; `Idempotency-Key` required and replay returns the stored response.
- `TipTest`: allowed only after `completed`, `tips_enabled`, category `tipping_enabled`, amount 1..max (`tip_amount_too_large` with `meta.max`), one per booking, never preselected (amount required); paid tip posts `tip` entry Dr gateway clearing / Cr provider wallet, commission-free; with `commission_on_tips` the split uses the tip commission; failed tip can be retried; refund of a tip reverses the tip entry.
- `CashPaymentTest`: completion of a cash booking opens one `cash_due` payment (replay opens none); provider `cash-collected` -> `cash_collected`, booking `payment_status` follows, no extra ledger entry, replay is 200 `replayed`; another provider 404; card booking and not-completed booking `cash_not_collectable`; audit row; operator force-complete opens the cash payment too.
- `PaymentMethodTest`: setup intent creates a pending method; verified `method.saved` webhook activates it (first becomes default, token encrypted, never in responses); list shows active only; delete forgets the token and promotes another default; another customer's method 404; account anonymisation purges methods; personal data export has brand and last4 only.
- `RefundTest::test_refund_ledger_balance`: full refund reverses the settlement entry exactly; partial refund balances; provider wallet and platform revenue move proportionally; payment becomes `partially_refunded` then `refunded`; `refunded_minor` equals the sum; `ledger:reconcile` ok afterwards; a refund webhook is idempotent (duplicate does nothing).
- `RefundTest::test_dual_approval_enforcement`: below the threshold the request executes at once; at or above it stays `pending_approval`; the requester cannot approve (403 `refund_self_approval`, also hidden by the policy); an admin without `refunds.approve` cannot; a second admin approves -> gateway call -> processing -> completed on webhook; reject needs a reason and posts nothing; approving twice is 409 `refund_not_pending`; threshold 0 means always dual.
- `RefundTest::test_amount_bounds_and_idempotency`: zero, negative, above refundable (including pending refunds) refused; same `idempotency_key` returns the same refund; only paid / partially refunded online payments; gateway error -> refund `failed` and nothing posted; audit rows for request, approve, reject with reasons.
- `RefundTest::test_refund_webhook_matching`: matched by refund reference, by the single processing refund, unmatched otherwise.
- `PaymentTimeoutTest`: a payment older than `payment_intent_timeout_minutes` whose gateway says succeeded becomes paid via `query_applied`; failed -> failed; still pending -> `reconciliation_required` with `timed_out`; gateway down leaves it for the next run; `payments:reconcile --sweep-only` output.
- `PaymentReconcileTest`: report finds `paid_locally_not_at_gateway`, `paid_at_gateway_not_locally`, `amount_mismatch`, `refund_mismatch`, `missing_at_gateway`, `unknown_transaction`; ok run stores `ok` with no findings; mismatch exit code 1 and critical log; nothing is changed by the run; payments younger than the grace period are skipped.
- `PaymentPositionTest`: open payments and refunds count for customer and provider (replaces the neutral seam); account deletion refused while payments are open.
- `BookingTransitionOnPaymentTest`: only `BookingTransitionService` writes the booking status on payment (assert through `BookingStatusGate`); disputed booking stays disputed when paid; cancelled / completed bookings untouched by late webhooks.
- `PaymentPostingTest`: gateway entry for platform / provider / partner funded discounts (stored breakdown with discount) balances; `payment-settlement:<uuid>` is idempotent.
- API contract tests: payment resource shape (no gateway reference, no token, `action` only on start / resume), `allowed_actions` `pay` / `tip` / `confirm_cash_collected`, booking creation accepts card only with a gateway configured.

### tests/Feature/Admin/Payments
- Guest redirected; admin without `payments.view` (and without refund permissions) gets 403 on pages, JSON and DataTables endpoints of payments, refunds, webhook events and reconciliation; `refunds.request` alone can open a payment and request a refund; `refunds.approve` alone can see and decide refunds; reprocess, sync and run need `finance.reconcile`.
- DataTables: every filter of payments (status, kind, method, gateway, search by uuid / reference / booking code, created range, amount range), refunds (status, dual approval, search), webhook events (outcome, needs attention, gateway, kind, signature, search, range) and reconciliation (result); booking and customer payment tabs are scoped; the filters card is collapsed by default (view assertion).
- Precognition: refund request returns 422 for zero, missing reason, amount above refundable, missing idempotency key and 204 for valid input without persisting; approve / reject / reprocess / sync without reason are rejected (422), with reason succeed; every action writes an audit row (`requested`, `approved`, `rejected`, `reprocessed`, `reconciled`) with the reason; reprocess refused for processed / invalid-signature events; reconciliation run is audited.
- Show pages render the timeline, refunds tab and webhook payload without the saved-method token; webhook payload never contains secrets; route-authorization guard test covers the new routes.
- Translations: every new key exists in `ar` and `en`; RTL: no physical utilities in the new views.

### tests/Feature/Console
- `ReconcilePaymentsTest` (see PaymentReconcileTest) and schedule entries (`payments:reconcile --sweep-only` every five minutes, hourly full run).

### Manual / live
- With a Paymob test account: card payment end to end (intent, hosted page, callback, inquiry agrees, paid, booking completed), wallet payment, saved card via TOKEN callback, partial and full refund, replayed callback, callback with a wrong HMAC, callback during a Paymob outage (inquiry fails -> 500 and retry); verify every assumption listed in docs/INTEGRATIONS.md and fix the driver where the live API differs.
- Local: `PAYMENT_DRIVER=sandbox` with a queue worker: start a payment in the customer app, watch `pending` -> `paid` within a few seconds, the booking turning `completed`, the admin payment timeline; run with `PAYMENT_SANDBOX_OUTCOME=fail`, `requires_action`, `silent` (then `php artisan payments:reconcile --sweep-only` after lowering the timeout setting); Arabic RTL walk-through of the four admin pages and the refund modal at phone width.

## P3.3 — Coupon engine and coupon manager

No test, pint, phpstan or gate was run for this milestone (user instruction). Everything below should exist. Paths are relative to `backend/`. Setup common to the feature tests: seeded catalog (`CatalogSeeder`), `BookingFactory`, a customer with a profile, a provider, the default currency EGP, `CouponFactory` (to write: active percentage coupon, version 1 created with it).

### tests/Unit/Coupon
- `CouponEligibilityServiceTest` (pure, built from `CouponDefinition` / `CouponContext` objects, no database), one test per rule, each asserting the `CouponReason` and a zero discount:
  - `test_status_and_window`: draft / paused / archived -> `not_active`; scheduled -> `not_started`; expired -> `expired`; exhausted -> `exhausted`; active before `starts_at` -> `not_started`; active at / after `ends_at` -> `expired` (end is exclusive); the time of the context (a scheduled trip), not the clock, decides.
  - `test_currency_mismatch` and `test_category_flag` (`categoryAllowsCoupons = false` -> `category_not_eligible`).
  - `test_scopes`: family, category, vehicle (intersection with the booking's candidate vehicles), city, zone each refuse a miss and accept a hit; an empty scope means unrestricted; a null city / zone with a city / zone scope is refused.
  - `test_exclusions`: an excluded category wins over a matching scope; excluded vehicles refuse only when every candidate vehicle is excluded.
  - `test_payment_method`: restricted list refuses other methods and a null method; empty list accepts all.
  - `test_segments`: `[new]` refuses a returning customer (`new_customers_only`), `[returning]` refuses a new one, both or none accept everyone.
  - `test_stacking`: `hasOtherDiscount` refuses a non-stackable coupon, accepts a stackable one.
  - `test_schedule`: windows in the coupon timezone (a UTC instant that is another weekday locally), inclusive start / exclusive end, an overnight window (22:00-02:00) accepts 01:00 of the next weekday and refuses 03:00.
  - `test_minimum_fare`: below the minimum -> `below_minimum_fare`, at the minimum eligible.
  - `test_no_discount`: a zero result -> `no_discount`.
  - `test_per_customer_limit`: `customerUses >= limit` -> `customer_limit_reached`; anonymous context ignores it.
  - `test_global_limit`: used + reserved at the limit -> `global_limit_reached`.
  - `test_budget`: remaining (budget - spent - reserved) below the discount -> `budget_exhausted`, equal to the discount -> eligible.
  - `test_rule_order`: the first failing rule wins (expired + wrong city = `expired`).
- `CouponTermsTest`: percentage rounds half-up once; max discount caps; discount never exceeds the fare; fixed above the fare is capped to the fare; below minimum fare is zero; invalid values (percentage > 10000 bp, split not adding to 10000) throw; `toSnapshot` / `fromSnapshot` round-trip; `splitFor` for platform / provider / partner / split.
- `CouponStatusTest`: `allowedTransitions` graph (archived is terminal, expired only to archived, draft cannot go to paused), `isRedeemable`, `isLive`.
- `PriceBreakdownCalculatorCouponTest`: platform-funded discount (provider unaffected, commission on the agreed fare, discount is not revenue), provider-funded (reduces `provider_net` and earning, not the commission base), partner-funded, split with an odd amount (the three parts always add up to the discount, no lost minor unit), 100% coupon with tax 0 -> payable 0, with tax > 0 -> payable = tax, discount capped at gross + fee.
- `SettlementPostingCouponTest`: gateway and cash postings for platform / provider / partner funded discounts and a 100% coupon balance (debit == credit) and skip zero lines.

### tests/Feature/Coupon
- `CouponReservationConcurrencyTest` (real MySQL, two or more processes / `Concurrency` facade or `pcntl_fork`): N parallel `reserve` calls on a coupon with `global_limit = k` produce exactly k reservations and `reserved_count == k`; with `per_customer_limit = 1` and one customer, parallel reserves for different bookings give exactly one reservation; with `budget_minor` for m discounts exactly m reservations succeed and `spent + reserved <= budget` always; the refused ones throw `CouponRejectedException` with `global_limit_reached` / `customer_limit_reached` / `budget_exhausted`; counters equal the sums of the reservation rows afterwards.
- `CouponReservationLifecycleTest`:
  - `test_reserve_holds_ceiling`: counters (`reserved_count`, `reserved_minor`), reservation `hold_minor`, version id, `reserved` attempt row.
  - `test_release_on_cancel_and_expiry`: customer cancel, provider cancel to cancellation, operations cancel and `expired_no_provider` through `BookingTransitionService` release the use and the held money, set `released_reason`; releasing twice is a no-op; a booking that goes back to searching keeps its reservation.
  - `test_commit_on_completion`: `onCompleted` commits: reservation `committed`, redemption with discount / platform / provider / partner amounts equal to the stored breakdown, `used_count` +1, `spent_minor` += discount, `reserved_*` released, funding entries `accrued` (zero amounts skipped), replay is idempotent.
  - `test_commit_with_zero_discount_releases`: agreed fare below the version's minimum fare -> reservation released with `no_discount`, no redemption.
  - `test_discount_never_exceeds_hold`: agreed fare higher than the bounds ceiling -> discount capped at `hold_minor`.
  - `test_reverse_on_full_refund`: full refund of the trip payment with `restore_on_refund` -> redemption `reversed`, reservation `reversed`, `used_count` and `spent_minor` restored, `reversed` funding entries; a partial refund changes nothing; `restore_on_refund = false` keeps the discount; reverse twice is a no-op; the refund ledger entry reverses the `promo_expense` line (ledger reconciles).
  - `test_reserved_coupon_is_not_double_counted_in_per_customer_limit`: reserved and committed count, released and reversed do not.
- `CouponVersionIsolationTest`: raising the discount of an active coupon adds version 2 and requires a reason; an existing reservation still prices with version 1 (`discountFor`), a new reservation uses version 2; a redemption keeps version 1 after the edit; editing only eligibility / limits / dates adds no version; a draft edit with unchanged terms adds none; two edits with a stale `lock_version` -> 409 `stale_version`.
- `BookingCouponTest` (API `POST /api/v1/bookings`):
  - `test_valid_code_reserves`: 201, `bookings.coupon_reservation_id` set, counters moved, `reserved` attempt row, booking enters searching.
  - `test_client_sent_discount_is_ignored`: `discount_minor`, `discount`, `total_minor`, `customer_payable_minor`, `funding_source` in the body change nothing; the stored breakdown at completion comes only from the server (assert with a body that tries a 100% discount and no code: payable unchanged).
  - `test_refused_codes`: unknown, expired, paused, wrong service / city / payment method, below minimum, over customer limit, global limit, budget -> 422 `coupon_<reason>`, `field = coupon_code`, `meta.reason_code`; the booking is NOT created and a `rejected` attempt row exists with the entered code.
  - `test_lost_race_is_logged`: the coupon is exhausted between `prepare` and `reserve` -> 422, no booking row, `reservation_failed` attempt.
  - `test_automatic_campaign_applied_without_code`: the largest eligible automatic discount wins (priority breaks ties), an `automatic` / `reserved` attempt row; an automatic campaign exhausted in the race does not block the booking (booking created without a coupon); no campaign eligible -> normal booking.
  - `test_idempotent_replay_reserves_once`.
  - `test_code_is_case_insensitive_and_trimmed`.
- `FreeBookingCompletionTest`: 100% platform-funded coupon, card booking, tax 0: completion by the provider ends `completed` (not `completed_pending_payment`), a trip payment with amount 0 and status `not_required`, `bookings.payment_status = not_required`, settled entry `booking_settlement_gateway` balanced (no gateway clearing line, `promo_expense` debit = discount, provider wallet = gross - commission, revenue = commission + fee), `settled_at` set, funding entries linked to the entry, `POST .../payment-intents` is not needed (and would answer `payment_nothing_to_pay`); with tax > 0 the booking stays `completed_pending_payment` and the intent amount is the tax; provider-funded 100% coupon credits the provider wallet only gross - commission - discount (can be negative); operator force-complete (`BookingOverrideService`) behaves the same; cash booking with a 100% coupon opens a `not_required` payment and settles the cash entry balanced; repeated calls are idempotent.
- `CouponPricingTest`: `BookingSettlementService::breakdownFor` with a reservation: platform-funded -> `discountPlatform` only, commission on the agreed fare, `platform_revenue` unchanged by the coupon; provider-funded -> `providerNet` lower, commission unchanged; split 50/50 and 33/33/34 add up; tips are priced with the same discount; after commit `discountFor` returns the recorded redemption amount.
- `CouponQuoteApiTest`: `POST /api/v1/coupons/validate` is 200 for eligible and ineligible coupons with `valid`, `reason_code`, `message`, the server-calculated discount, no funding or counters in the body; unknown code -> `not_found`; driver token 403; unauthenticated 401; throttled at 30/min; every call writes a `validate` attempt; an invalid payload is 422; `GET /api/v1/me/coupons` lists only active automatic campaigns the customer can still use and their own history (another customer's uses never appear); codes of non-automatic coupons are never listed.
- `CouponStatusSyncTest` (`coupons:sync-status`): scheduled -> active at `starts_at`; active / scheduled / paused -> expired at `ends_at`; active -> exhausted at the global limit and at the budget; exhausted -> active after a reversal or a raised cap, but not after the period ended; releases `reserved` reservations of cancelled / expired bookings; idempotent; runs every minute (`Schedule` assertion).
- `CouponAttemptLogTest`: every path writes one attempt (validate, booking rejected, booking reserved, automatic, reservation failed, admin test); the model refuses updates; `anonymiseForUser` unlinks the user and drops `code_entered` / `meta`.
- `CouponSeederTest`: six coupons exist, are idempotent on a second run, each has version 1 whose terms equal its columns; the expired one is not redeemable; the new-customer coupon refuses a customer with a completed booking.
- `CouponPrivacyTest`: the personal data export contains `coupon_usage` and `coupon_attempts` and `sections_pending` no longer lists `coupon_usage`; anonymisation unlinks attempts, keeps reservations / redemptions, and the audit meta counts them (update `CustomerDeletionAndExportTest`, which still expects `payments` in `sections_pending`).

### tests/Feature/Admin/Coupon
- Guest redirected; admin without `coupons.view` / `coupons.manage` gets 403 on pages, JSON and DataTables endpoints; `coupons.view` alone can read and run the eligibility test but not create / edit / publish / pause / resume / archive; `coupons.export` alone can export; the route-authorization guard test covers the new routes; the customer coupons tab needs `customers.view` and `coupons.view`.
- DataTables: every filter of the listing (code, statuses, discount types, funding sources, service family, active on date, validity range), the redemptions and attempts tabs (status / result, date range, scoped to the route coupon), the customer tab (scoped to the customer); ordering allow-list; export row content and the formula-injection prefix; the export is queued, audited (`exported`) and downloadable only by its owner.
- Precognition / validation: store and update return 422 for missing Arabic title, bad code, duplicate code (case-insensitive), percentage 0 / > 100, missing value for the chosen type, split not adding to 100, end before start, automatic campaign without a per-customer limit, unknown scope ids, bad schedule time; 204 for valid input without persisting; a valid store creates a draft with version 1, scope rows and an audit row; update with a changed money term on a live coupon without a reason is 422; pause / archive without a reason are 422 and write nothing; publish / resume / pause / archive write `status_changed` audit rows with the reason and respect the policy (publish only drafts, resume only paused, pause only active / scheduled, archive anything not archived, archived coupons are not editable).
- Test-eligibility tool: returns the decision, reason and per-funder amounts, logs an `admin_test` attempt, reserves nothing, refuses an unknown customer / category.
- Show page renders the preview text (locale ar / en), conditions list, versions, performance numbers (budget burn, funding split, daily list, rejection reasons) without errors; the edit form renders the saved scope options in the remote selects; the performance tab is correct with no data.
- Translations: every new key exists in `ar` and `en`; RTL: no physical utilities in the new views; `x-admin.filters.date` renders and round-trips `filters[active_on]`.

### Manual / live
- Browser: create a coupon with every section of the eligibility builder in Arabic and English, watch inline Precognition errors block the submit, publish, run the test tool for an eligible and an ineligible customer, pause with a reason, check the redemptions tab after a completed booking and the customer page tab.
- Two phones booking at once against a coupon with one use left: one gets the discount, the other a clear refusal and no booking (or, for an automatic campaign, a booking without the discount).
- Customer app (P3.8): code entry -> `POST /coupons/validate` -> booking with `coupon_code`; 100% coupon trip ends `completed` without a payment screen.


## P3.4 — Provider earnings, payouts, adjustments, cash reconciliation

Not written or run by user instruction. Everything below should exist before P3.4 is marked done. Setup shared by most tests: an approved provider with a wallet funded through `LedgerService` (`Dr gateway_clearing / Cr provider wallet`), a verified `ProviderPayoutMethod`, two admins holding `payouts.approve` (and one holding `payouts.review`), two holding `wallets.adjust` / `wallets.approve_adjustment`. Concurrency tests live in `tests/Concurrency` (Process pool, as in P2.9 / P3.1).

### tests/Concurrency
- `PayoutDoubleSpendTest` (file `tests/Concurrency/PayoutDoubleSpendTest.php`): wallet 1000.00, ten parallel `POST /provider/payouts` for 600.00 each with DIFFERENT Idempotency-Keys -> exactly one 201, nine 422 `payout_insufficient_balance`; exactly one `payout_requests` row and one `payout_reserve` entry; wallet balance 400.00; `payout_clearing` balance 600.00; `ledger:reconcile` clean.
- `PayoutSameKeyReplayTest`: ten parallel requests with the SAME key and body -> one row, one reserve entry, every response carries the same payout id; same key with another amount -> 422 `idempotency_key_reused`.
- `PayoutMarkPaidRaceTest`: five parallel `POST /admin/payout-requests/{id}/paid` on a processing payout -> exactly one 200, the others 409 `payout_state_conflict`; exactly one `payout_paid` entry; `payout_clearing` back to 0 and `gateway_clearing` moved by the net amount once.
- `PayoutRequestVersusAdjustmentTest`: a payout request racing an approved debit adjustment on the same wallet never leaves the wallet negative through the payout (the reservation is refused or the adjustment is, but the ledger never overdraws the reserved amount); balances equal the sum of lines.
- `CashSettlementRaceTest`: two parallel settlements of the same open reconciliation -> one 200, one 409 `cash_settlement_not_open`; the wallet is credited once.

### tests/Feature/Payout
- `PayoutRequestTest`: below min -> 422 `payout_amount_below_minimum` (`meta.min`); above max -> `payout_amount_above_maximum`; unverified method / another provider's method / unknown method -> 422 `payout_method_not_verified`; non-approved provider -> `payout_provider_not_approved`; success creates `requested` with `fee_minor` = fixed + round(percent) from the settings, `net = amount - fee`, `requires_dual_approval` exactly when `amount >= threshold` (threshold 0 -> always), a reserve entry (`Dr wallet / Cr payout_clearing`, balanced), wallet balance reduced by the amount, a `payout_events` row, an audit row `requested`; the client cannot send a fee or net (ignored); wallet below zero (cash debt) -> `payout_insufficient_balance`; Idempotency-Key header required (422 without it); replay returns the first payout.
- `PayoutDualApprovalTest`: below the threshold one `payouts.approve` admin approves (`approved`, `approved_by_id`); at or above it the first approval keeps `under_review` and sets `first_approved_by_id`; the same admin approving again -> 403 `payout_same_approver`; a second admin -> `approved`; an admin without `payouts.approve` -> 403 (route and service); an unverified (or removed) method at approval -> 422; approving a closed payout -> 409; audit rows for each step with the note.
- `PayoutRejectionTest`: reject without reason -> 422 (Precognition and real); reject with reason -> `rejected`, `release_entry_id` set, a reversal entry linked to the reserve (`reversal_of_id`), wallet restored, `payout_clearing` 0, `rejection_reason` shown to the provider through the API; a payout in `processing` cannot be rejected (409).
- `PayoutFailureReversalTest`: processing -> failed with reason -> reservation reversed (wallet restored to the pre-request balance, `payout_clearing` 0, no `payout_paid` entry), status `failed`, `failure_reason` stored; the provider can request again with the same money; failing a `requested` payout -> 409.
- `PayoutPaidTest`: processing -> paid needs `transfer_reference` (422 without, Precognition included); posts `payout_paid` (`Dr payout_clearing amount`, `Cr gateway_clearing net`, `Cr platform_revenue fee`, balanced, fee line absent when the fee is 0); wallet unchanged by paying (already reserved); `paid_at`, `paid_entry_id`, `transfer_reference` set; an evidence PDF is stored on the private disk, re-encoded images, a non-allowed MIME -> 422 and NO ledger entry; the receipt downloads only with `payouts.view`; a second call -> 409 and no second entry; a batch containing it completes when its last processing payout is settled.
- `PayoutReversedTest`: paid -> reversed (`payouts.approve`, mandatory reason) reverses the paid entry and the reservation (wallet back to the pre-request balance, all clearing accounts back to 0); a non-paid payout -> 409; `payouts.review` alone -> 403.
- `PayoutProviderCancelTest`: the provider cancels while requested / under_review (reservation reversed, `cancelled`), cannot cancel an approved one (409 `payout_not_cancellable`), cannot cancel another provider's payout (404).
- `PayoutBatchTest`: creating a batch takes approved payouts of ONE currency (mixed -> 422 `payout_batch_mixed_currency`; none -> `payout_batch_empty`), moves them to processing with `payout_batch_id`, totals the net amounts, needs a note (422) and `payouts.review` (403); export streams a CSV with the decrypted destination, needs `payouts.review`, is audited (`exported`, `contains_destination_details`), marks the batch `exported`, neutralises formula-looking cells; the file is not stored anywhere; a rejected payout cannot be taken into a batch.
- `PayoutMethodTest` (API + service): create stores encrypted details (raw column is not the plaintext; `details` hidden in JSON and absent from audit snapshots), returns type / label / last4 only, the first method becomes default, a second default switches; required fields per type are enforced (422 on the field); update drops `verified_at`; delete of a method with an open payout -> 409 `payout_method_in_use`; another provider's method id -> 404; verify / unverify need `payouts.review` and a reason; `ProviderDeletionService` purge empties the details and soft-deletes while payouts keep the masked snapshot.
- `ProviderEarningsApiTest`: summary for day / week / month (Saturday week start, market timezone boundaries), totals equal the sum of the stored price breakdowns, `by_category` groups by service category, the provider-funded coupon appears in `discount_provider_minor` and is inside `net_minor`, cash collected counts cash bookings only; the `wallet` block equals the ledger (`balance`, `reserved` = open payouts, `available`, `cash_debt`); another provider's bookings never appear; transactions paginate newest first with the exact breakdown fields and respect from / to; unauthenticated 401, customer token 403.
- `CustomerWalletApiTest`: `/me/wallet` lists only the caller's wallets with the cached balance; `/me/wallet/transactions` returns the caller's lines with signed change and balance after; another customer's lines never appear; provider token 403; the personal-data export contains `wallet` with the same lines.
- `PayoutPositionTest`: an open payout counts as an open financial item (provider deletion is refused with `provider_has_open_financial_items` until it closes).

### tests/Feature/Finance
- `AdjustmentMakerCheckerTest`: request needs `wallets.adjust`, a reason (422), a wallet account (a platform account -> 422 `adjustment_account_not_wallet`), amount > 0; the requester approving -> 403 `adjustment_self_approval` and NOTHING is posted; a different admin without `wallets.approve_adjustment` -> 403; a second admin with the permission approves -> exactly one `adjustment` entry (credit: `Cr wallet / Dr adjustments`, debit: reverse), wallet balance changed by the amount, `journal_entry_id` set, request `approved`, audit rows (requested, approved) with reasons; re-approve / approve a rejected request -> 409 `adjustment_not_pending`; reject needs a reason; the requester can cancel (not another admin); a debit that would overdraw a customer wallet -> 422 `ledger_negative_balance` and the request stays pending; a provider wallet debit may go negative; the same idempotency key returns the first request, with another body 422; no code path edits or deletes an existing `journal_entries` / `journal_lines` row.
- `CashReconciliationTest`: open computes `cash_jobs`, `cash_collected_minor` and `commission_due_minor` from settled cash bookings in the period only (card bookings and other providers excluded, period bounds inclusive in the market timezone); invalid period (end before start, > 366 days, future) -> 422; settle above the wallet's cash debt -> 422 `cash_settlement_exceeds_debt`; an exact settlement posts `cash_settlement` (`Dr gateway_clearing / Cr provider wallet`), the cash debt falls, `CashDebtLimitExceeded` stops applying when below the limit; settle needs `finance.reconcile`, a reference and a reason; a second settle -> 409; cancel needs a reason and posts nothing; idempotent open.
- `ProviderStatementTest`: for a provider with earnings, a payout, an adjustment and a cash settlement, the exported totals satisfy `opening + credits - debits = closing`, `closing` equals the sum of the signed lines in the period, and for an open-ended period `closing` equals `ledger_accounts.balance_minor`; the date range filters by `posted_at` and the opening balance equals the balance before `from`; needs `finance.view` AND `reports.export` (each alone -> 403); writes an `exported` audit row with the period and the balances; the file is a download, not stored.

### tests/Feature/Admin/Payout (and Finance)
- Guest redirected; an admin without the permission gets 403 on the pages, the JSON and the DataTables endpoints of payout requests, batches, adjustments, cash reconciliations and the provider statement; the route-authorization guard test covers the new routes; `payouts.view` alone cannot review / approve / reject / process / pay / fail / reverse / batch / export; `wallets.view` alone cannot request or approve.
- DataTables: every Appendix C filter of payout requests (status, provider, amount range, requested range, approver), batch filters (status, reference, created range), adjustment filters (status, direction, requester, search, created range), cash filters (status, provider, period); ordering allow-list; the batch page table is scoped to its batch; the provider payouts tab is scoped to the provider.
- Precognition / validation: adjustment store returns 422 for a missing wallet / direction / amount / reason / idempotency key and 204 for valid input without persisting; cash open validates the period; paid requires the reference; reject / fail / reverse / unverify / cancel / batch without a reason -> 422 and nothing changes.
- Show pages render without errors for every status (payout in each state shows only the allowed buttons), including the dual-approval notice and the evidence link; the provider Earnings and Payouts tabs render with and without data; the statement form is hidden without the permissions.
- Lookups: `finance-providers` works for `payouts.view` or `finance.view` without `drivers.view`; `wallets` needs `wallets.adjust`.
- Translations: every new key exists in `ar` and `en` (admin, enums, api); RTL: no physical utilities in the new views.

### tests/Unit
- `PayoutQuoteServiceTest`: fee = fixed + half-up percentage on integer minor units, capped at the amount, net = amount - fee, dual flag at the threshold boundary (amount == threshold -> dual; threshold 0 -> always).
- `PayoutPostingServiceTest`: reserve / pay / release / reversePaid lines are balanced and idempotent by key; pay with fee 0 has two lines, with a fee three.
- `PayoutStatusTest`: `holdsReservation`, `open`, `awaitingDecision`, `isCancellableByProvider` for every case.
- `ProviderBalanceServiceTest`: available = max(0, wallet balance); reserved = sum of open payouts only (paid / failed / cancelled excluded).

### Manual / live
- Browser: switch the settings threshold low, request a payout from the API, approve it with two different admins in two browsers, create a batch, export the transfer file, mark it paid with a receipt, see the provider wallet and the ledger entries; reject and fail one and see the money return; open the adjustment modal, watch inline Precognition errors block the submit, approve from a second admin; open a cash reconciliation and settle it.
- Driver app (P3.8): payout methods and the payout request screen against these endpoints, including the Idempotency-Key retry after a network drop.
