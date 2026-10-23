# Load tests (k6)

Scripts for the four hot paths of the platform (SRS 23.1): location ingest, booking create plus offer polling, available requests and
offer creation with optional accept. **They have not been run.** No result in this repository comes from them; `docs/PERFORMANCE.md`
lists what to record and where.

## Prerequisites

1. A separate database `delivery_platform_perf` (never the dev or a production database) and an app instance pointed at it
   (`DB_DATABASE=delivery_platform_perf`), with Redis, the queue workers and the scheduler running exactly as in production
   (`docs/OPERATIONS.md`), `APP_ENV=production`-like settings (`APP_DEBUG=false`, config and route cache).
2. Volume: `php artisan perf:seed` (refuses any database not ending in `_perf`).
3. Rate limits: the limiters are per user; to push more traffic than one user could send, spread over many tokens, do not turn the
   limiters off (that would measure a configuration production will not have).
4. Tokens: one Sanctum token per virtual user, written as a JSON array to `customer_tokens.json` / `provider_tokens.json`
   (`php artisan tinker`: `$user->createToken('perf', ['customer'])->plainTextToken`, abilities `customer` or `provider`).
   Providers must be approved, online, with a vehicle. Keep these files out of git (they are ignored by `loadtest/.gitignore`).
5. `booking_payload.json`: a valid `POST /bookings` body for your seeded city and category.

## Run

```bash
k6 run -e BASE_URL=https://perf.example.test/api/v1 -e PROVIDERS=500 -e DURATION=10m loadtest/location_ingest.js
k6 run -e BOOKINGS_PER_MINUTE=60 -e DURATION=10m loadtest/booking_flow.js
k6 run -e PROVIDERS=100 -e ACCEPT=1 loadtest/offers_accept.js
```

`P95_MS` changes the threshold in each script; the defaults are placeholders until the SRS 23.1 figures are copied in.
Payload field names for offers and the `/provider/offers` response shape were taken from the route list, not from a run: adjust the
two lines marked in `offers_accept.js` if the first dry run (1 VU) returns 422.

Always do a 1-VU dry run first and read the responses, then ramp up.
