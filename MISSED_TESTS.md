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

