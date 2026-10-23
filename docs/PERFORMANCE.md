# Performance, data lifecycle and queue tuning (P6.2)

## 0. Measurements: none exist yet

**No load test, benchmark or large-volume EXPLAIN has been run.** The performance database could not be created in the
development environment (the application MySQL user cannot create databases), and by project-owner instruction no heavy test runs
in this milestone. Every statement below is a design decision made by reading the queries and the schema; every number written
here is a configured value, not a result. The "Measurements" table in section 6 is empty on purpose.

How to take measurements (in order):

1. Create `delivery_platform_perf`, point a second app instance at it (`DB_DATABASE=delivery_platform_perf`), `php artisan migrate`,
   run the normal seeders and create at least one booking.
2. `php artisan perf:seed` (defaults: 200,000 bookings, 2,000,000 location samples; `--bookings`, `--locations`). It refuses any database
   whose name does not end in `_perf`. Customers and providers are whatever the seeders created; repeat the demo seeder to reach
   about 5,000 providers.
3. Run the EXPLAINs of section 2 (`EXPLAIN ANALYZE` on MySQL 8) and paste the plans and times into section 6.
4. Start workers and the scheduler as in `docs/OPERATIONS.md`, then run the k6 scripts in `loadtest/` (see its README), 1 VU first.
5. Record, per script: arrival rate or VUs, p50/p95/p99, error rate, DB CPU, Redis CPU, queue latency (`queue:monitor`), Reverb
   connections and memory. Record the machine size and the commit hash. Compare with SRS 23.1; open an issue for any miss.

## 1. Index review (by reading; additive migration `2026_11_18_090000_add_performance_and_retention_indexes`)

The hot queries were read in the repositories and checked against `SHOW CREATE TABLE`. Most paths were already covered by P2.x/P3.x
indexes (offers by booking and status, offers by provider and status, dispatch recipients by provider and time, ledger lines by account
and id, inbox by recipient/app/created_at, payments by booking, last location spatial index, tickets and incidents by booking).

Added:

| Index | Why |
| --- | --- |
| `bookings (created_at)`, `(status, created_at)`, `(payment_status, created_at)`, `(booking_type, created_at)` | The admin booking listing orders by `created_at desc` and filters by one of these without a customer or city; before, only `customer_id` / `city_id` prefixed indexes existed, so an unfiltered or status-only listing sorted the whole table |
| `booking_offers (status, created_at)` | Offers monitor in admin |
| `booking_share_links (expires_at)`, `admin_sessions (last_active_at)`, `gateway_webhook_events (signature_valid, received_at)`, `notification_deliveries (created_at)`, `coupon_attempts (created_at)` | Retention jobs delete by age and must not scan |

Checked with `EXPLAIN` on the small development database only to prove the optimizer can and does use the new indexes (backward index
scan on `bookings_created_at_index`, range on `payments_status_created_at_index`-style composites); this says nothing about
cost at volume.

Known and accepted: free-text filters (`code like %x%`, phone and name search) cannot use an index. Phone search is by exact number or by the
last 4+ digits and goes through the users table; code search is a contains match on a 16 character column. If these show up in measurements the
fix is a trailing-wildcard search (prefix) or a reversed-phone column, not another B-tree index. JSON filters on `service_details` / `agreed_scope`
(cargo type, vehicle class) are full scans of the filtered set; add generated columns only if measured.

### Queries to EXPLAIN at volume

```sql
-- admin listing, default and per filter
EXPLAIN ANALYZE SELECT id FROM bookings ORDER BY created_at DESC LIMIT 25;
EXPLAIN ANALYZE SELECT id FROM bookings WHERE status = 'completed' AND created_at >= '2026-01-01' ORDER BY created_at DESC LIMIT 25;
EXPLAIN ANALYZE SELECT id FROM bookings WHERE payment_status = 'paid' ORDER BY created_at DESC LIMIT 25;
EXPLAIN ANALYZE SELECT id FROM bookings WHERE city_id = 1 AND status = 'completed' ORDER BY created_at DESC LIMIT 25;
-- provider available requests (join through dispatch recipients, ordered by notification time)
-- customer offers list, provider offers: by (booking_id, status) and (provider_profile_id, status)
-- eligible providers (dispatch): spatial index on provider_last_locations.point, then status / vehicle filters
-- ledger balance of an account: SELECT ... FROM journal_lines WHERE ledger_account_id = ? ORDER BY id DESC LIMIT 1
-- notification inbox: WHERE recipient_type = ? AND recipient_id = ? AND app = ? ORDER BY created_at DESC
-- dashboard aggregates: metrics_daily_* by metric_date (they read pre-aggregated rows, not bookings)
```

Dispatch uses the spatial index on `provider_last_locations` (one row per provider, about the number of providers), not on the history table;
the history table `provider_locations` is write-heavy and read only for a booking's trail and anomaly checks. Do not add indexes to it
without a measured read.

## 2. DataTables and column selection

Listings select only what they show, or exclude the heavy column: the gateway webhook events listing no longer loads the raw `payload` JSON
(`GatewayWebhookEventRepository::LISTING_COLUMNS`), the bookings listing and the customers listing select explicit columns / output an `only([...])` allowlist. Not narrowed (they select `table.*`, with column-listed eager loads): support tickets,
safety incidents (narrative text), audit logs (before/after JSON), notification deliveries (body) and content listings (body). They page at 10 to 100 rows, so the cost is bounded, but they are the next candidates if a
listing measures slow; each needs its DataTable columns and export checked first because strict models throw on a missing attribute. Strict models (below) turn a missing column into an exception in development, so a future narrowing mistake shows
immediately.

N+1: `Model::shouldBeStrict(! production)` was already on (`AppServiceProvider::boot`), which includes `preventLazyLoading`, so any lazy
load in development, tests or CI throws. Listing repositories eager-load their relations with column lists (`with('payment:id,uuid')` style).
No new N+1 was found by reading; none can be ruled out for screens that were not exercised in a browser.

## 3. Caching

| What | How | Invalidation |
| --- | --- | --- |
| Public app config (`GET /config/app`) | `AppConfigCache`, 10 min (P1.x) | `forget()` from every writer (settings, countries, currencies, policies, flags, app versions) |
| Public catalog: service families, categories without a position, vehicle categories | `CatalogCache`: the finished **JSON text** per locale (and filter) in the cache store, 5 min safety TTL | a version number in the key; saving or deleting `ServiceFamily`, `ServiceCategory`, `ServiceCategoryVersion`, `VehicleCategory` or `ZoneServiceCategory`, and the category/vehicle pivot sync, bumps it |
| Content (website/API reads) | `content.cache_ttl_seconds` (60 s), bumped on publish (P5.x) | publish / unpublish |
| Sitemap | `website:sitemap`, 5 min | TTL |
| Maps geocode / route | provider cache TTLs (config/maps.php) | TTL |

Why JSON text: `cache.serializable_classes` is `false` (a hardening choice: no object injection from the cache), so Eloquent models and the
`stdClass` of empty translation maps cannot be stored; text can, and the response is built without a query on a hit.

**Never cached:** authorization (policies, permissions, token abilities), wallet or ledger balances, anything per user, anything that
decides who may do what or how much money moves. Position-based category listings (with `lat`/`lng`) are not cached either: they depend on zones and live hours.

## 4. Retention and archive

| Data | Job | Rule | Legal hold / safety |
| --- | --- | --- | --- |
| Location samples | `locations:prune` 03:30 | 30 days without a booking, 90 days with one (ADR-85) | samples of a booking with a held incident or an open ticket are kept (P6.2) |
| Chat | `chat:purge-expired` 03:45 | text and images blanked 180 days after the booking is final (chat decision ADR in the support module) | skipped while a ticket on the booking is open; skipped for bookings with a held incident (P6.2) |
| Share links | `retention:run` 04:30 | deleted 7 days after expiry or revocation | bookings with a held incident or open ticket |
| Idempotency keys | `model:prune` hourly | `ttl_hours` (24) | none needed (replay window) |
| OTP challenges | `model:prune` hourly | 30 days | none |
| Admin exports (rows and files) | `model:prune` hourly | 7 days; the signed download link is stateless and expires on its own (24 h) | none |
| Inbox notifications | `model:prune` 05:00 | `notifications.retention_days` (180) | none |
| Notification deliveries (log) | `retention:run` | 180 days, never rows still queued or sending | none |
| Rejected webhook rows (bad signature) | `retention:run` | deleted after 30 days | none (no business data) |
| Accepted and processed webhook rows | `retention:run` | raw `payload` set to NULL after 90 days (never-processed rows keep it: an admin reprocess replays it); the row (hash, ids, outcome) stays for payment audit | payload kept for payments of held bookings |
| Admin login events | `retention:run` | 365 days | none |
| Admin sessions | `retention:run` | idle more than 30 days | none |
| Coupon attempts | `retention:run` | 180 days | none |
| Safety evidence | `safety:maintain` | `safety.retention_days` (730) after resolution | `legal_hold` keeps the case whole (ADR-171) |
| Laravel `sessions` table, `failed_jobs`, job batches | session lottery; `queue:prune-failed` (30 d) and `queue:prune-batches` daily | | |

`retention:run [--dry-run] [--only=step]` is idempotent (every pass deletes by age, in primary-key chunks of 2,000, at most 50 chunks per step per
run, so a backlog is worked off over several nights without long locks) and safe to run concurrently. **Never deleted by any job:** `journal_entries`,
`journal_lines`, `ledger_accounts`, payments, refunds, payout rows, bookings, ratings, audit logs, consents, deletion requests.

**Archive strategy for bookings and ledger (design, not implemented, nothing to archive yet).** Bookings and journal rows are legal and financial records and
are kept online until measurements say otherwise. If volume demands it: (1) bookings in a final state older than 24 months (and past any dispute window and
legal hold) are copied to `bookings_archive` / `booking_*_archive` tables (same schema, no foreign keys, a separate schema or cold storage) and removed from the hot
tables by a job that copies, verifies counts and checksums, then deletes in one transaction per booking; customer and admin views read through a repository
that falls back to the archive by uuid; (2) the ledger is never moved row by row: journal entries are immutable and `ledger_accounts.balance_minor` plus
`journal_lines.balance_after_minor` make balances independent of history, so old months can be moved by RANGE partition (`posted_at`) to cold storage
only after a signed-off period close and a reconciliation run (`ledger:reconcile`) for that period; (3) `provider_locations` is the first table to partition
(monthly RANGE on `recorded_at`, drop partition instead of delete). MySQL cannot partition a table with a spatial index, so the history table's `point`
index would be dropped first (dispatch reads `provider_last_locations`, which keeps its own spatial index). Decide with measurements: if `locations:prune` cannot keep up
(DELETE chunks slower than the ingest rate) or the table exceeds the buffer pool, partition; until then deletes are fine.

## 5. Queues, workers, timeouts

Queues, in worker order: `critical` (dispatch waves, offer/search expiry, stale-provider sweep, sandbox webhook delivery), `default`, `notifications`,
`low` (exports, daily metrics). Recommended processes (`docs/OPERATIONS.md` has the supervisor units) -- starting points, to be tuned from section 6:

| Process | Command | Count (start) | Why |
| --- | --- | --- | --- |
| critical | `queue:work redis --queue=critical --sleep=1 --tries=3 --timeout=60 --max-time=3600` | 2 per app node | Offer and search expiry must run within seconds; a dedicated pool keeps it free of campaign bursts |
| general | `queue:work redis --queue=default,notifications --sleep=1 --timeout=60 --max-time=3600` | 2 | Notification deliveries (one attempt each, retried by the delivery log) |
| long | `queue:work redis_long --queue=notifications,low --timeout=900 --max-time=3600` | 1 | Exports (600 s), daily metrics (900 s), push campaigns (900 s) |

Rules that matter more than the counts:

* `retry_after` must be greater than the worker `--timeout` and than every job's own `$timeout`, otherwise a running job is released and runs twice.
  The 90 s `redis` connection was too short for the three long jobs (600 to 900 s); they now run on the new `redis_long` connection (`retry_after` 960 s)
  through `QUEUE_LONG_CONNECTION=redis_long` (unset, they stay on the default connection, which is right for dev and tests). `GenerateAdminExport`,
  `AggregateDailyMetrics` and `RunPushCampaign` read it. **Production must set `QUEUE_LONG_CONNECTION=redis_long`** and run the long worker with `--timeout=900`.
* Retries: critical jobs have `tries = 3` with `backoff = [2, 10]` seconds; payment sandbox webhook delivery 6 tries; notification deliveries 1 try (the delivery log schedules its own
  back-off); schedulers' jobs 1 try (the next tick retries). Handlers are idempotent (offer/expiry, settlement markers, idempotency keys), so a retry is safe.
* Failed-job alerting: every final failure logs one `CRITICAL` line `queue.job_failed` (job class, queue, attempts, exception class; never the payload, which can hold PII);
  `queue:monitor` runs every minute and logs `queue.backed_up` when a queue holds more than 200 jobs (one threshold for all; raise `notifications` after a measured campaign).
  Route the `critical` log level to the pager in P6.4. `queue:prune-failed` keeps 30 days.
* Deploys: `php artisan queue:restart` after each release (workers exit after the current job); `--max-time=3600` bounds memory growth.

Reverb (realtime) scaling notes: a single Reverb process holds all connections in memory and is fine for the first cities. To scale out set `REVERB_SCALING_ENABLED=true`
(Redis pub/sub fan-out between Reverb nodes), put the nodes behind a load balancer with sticky-free WebSocket support and long read timeouts (60 s+ idle), raise
the file descriptor limit (`ulimit -n 65535`) and `REVERB_MAX_REQUEST_SIZE` only if needed, and size by connections: every online provider and every customer with an open
booking holds one connection. Broadcasts from the app go through the queue (`critical` for offer events), so slow broadcasts show as queue latency, not as request latency. The apps already
recover from a missed event by reading the API after a sequence gap, so a Reverb restart or a node loss degrades to polling, it does not lose state. Watch: connections, memory per
node, Redis pub/sub throughput, and p95 of `broadcast` job time.

## 6. Measurements (empty until taken)

| Date | Commit | Environment | Scenario | Rate / VUs | p50 | p95 | p99 | Errors | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| | | | | | | | | | none taken |

Open decisions that need numbers: partitioning of `provider_locations`; whether `bookings` listing filters need more composite indexes; worker counts; the `queue:monitor` thresholds;
Reverb node count.
