# External Integrations

> All external services are reached only through contracts in `app/Integrations/<Area>/Contracts` (ADR-12, SRS §22).
> `fake`/`log` drivers are allowed only when `APP_ENV` is `local` or `testing`; the service provider refuses to boot otherwise.
> **Status (P0.2):** `IntegrationServiceProvider` and `config/integrations.php` exist with the boot guard; the contract→driver map
> is empty (no adapters yet). Driver env vars `SMS_DRIVER`, `MAPS_DRIVER`, `PAYMENT_DRIVER`, `PUSH_DRIVER`, `PHONE_RELAY_DRIVER`,
> `FILE_SCANNER_DRIVER` are in `.env.example` (empty = not configured). Reverb is installed and configured.
> Env var names are proposals; the milestone that implements an adapter finalizes them here and in `backend/.env.example`.

| Area | Contract | Milestone | Dev driver | Production provider | Env vars (proposed) | Status |
|---|---|---|---|---|---|---|
| SMS / OTP | `SmsGateway` (`App\Integrations\Sms`) | P1.3 | `log` (writes the OTP to log channel `otp_dev` only; refused outside local/testing) | `smsmisr` (SMS Misr, Egypt): real HTTP call implemented from the vendor docs, **untested without credentials** (verify sender token, unicode flag, error codes on a staging account) | `SMS_DRIVER` (`smsmisr`/`log`), `SMSMISR_USERNAME`, `SMSMISR_PASSWORD`, `SMSMISR_SENDER`, `SMSMISR_ENVIRONMENT` (1 live, 2 sandbox); test OTP: `OTP_TEST_ENABLED`, `OTP_TEST_CODE`, `OTP_TEST_PHONES` (local/testing only, boot-guarded); `AUTH_TOKEN_TTL_DAYS` | `log` in local/testing; unset = not configured: OTP requests answer `503 otp_delivery_failed`, never fake success |
| Maps: geocode, reverse, search, route/ETA | `MapsProvider` (`App\Integrations\Maps`) | P1.5 | `fake` (offline, deterministic; refused outside local/testing) | `osrm_nominatim` (Nominatim + OSRM, configurable base URLs, 1 req/s spacing, identifying User-Agent) or `google` (Geocoding + Places New Text Search + Routes API v2). Both implemented from vendor docs and **tested only against faked HTTP** (no credentials/network here): verify on staging before go-live. Results are cached (`CachingMapsProvider`, TTLs in `config/maps.php`), failures never cached | `MAPS_DRIVER` (`osrm_nominatim`/`google`/`fake`), `MAPS_API_KEY` + `MAPS_GOOGLE_REGION` (google), `MAPS_NOMINATIM_URL`, `MAPS_OSRM_URL`, `MAPS_USER_AGENT`, `MAPS_CONTACT_EMAIL`, `MAPS_NOMINATIM_MIN_INTERVAL_MS`, `MAPS_COUNTRY_CODES`, `MAPS_TIMEOUT_SECONDS`, `MAPS_CACHE_*_TTL`; Flutter `MAP_TILE_URL`, `MAP_ATTRIBUTION` | unset driver = not configured: endpoints answer `503 maps_unavailable`, never fake success. Public OSM/OSRM servers are for development only (usage policies); production needs self-hosted instances or Google |
| Realtime | Laravel Reverb 1.12 (framework broadcaster) | P0.2 (installed) / P2.2 (channels + events) | Reverb on `localhost:8080` (`php artisan reverb:start`) | Reverb server behind TLS | `BROADCAST_CONNECTION`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`, `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT`, `REVERB_SCALING_ENABLED`, `VITE_REVERB_*` | configured locally (`BROADCAST_CONNECTION=reverb`); tests use `null` (the channel tests switch to the Reverb driver for real signing). Channels, authorization and the P2.2 events exist (ADR-87); the driver app connects to `private-provider.{uuid}` |
| Provider geo index | `ProviderGeoIndex` (`App\Services\Location\Index`) | P2.2 | `database` (`PROVIDER_GEO_INDEX=database`: MySQL spatial, tests and local without Redis) | `redis` (Redis 7 `GEOADD` / `GEOSEARCH` per city + a TTL hash per provider; needs the phpredis client) | `PROVIDER_GEO_INDEX` (`redis`/`database`), `REDIS_*`, `REDIS_PREFIX`; `LOCATION_INTERVAL_IDLE` / `_TO_PICKUP` / `_ACTIVE` (seconds), `LOCATION_MAX_BATCH`, `LOCATION_MAX_SAMPLE_AGE`, `LOCATION_CLOCK_SKEW`, `LOCATION_CITY_RECHECK_METERS`, `LOCATION_RETENTION_DAYS`, `LOCATION_BOOKING_RETENTION_DAYS` | tested against a real local Redis (database 15) and the DB twin with the same assertions; skipped automatically when no Redis answers |
| Payments (card / wallet) | `PaymentGateway` (`App\Integrations\Payments`) | P3.2 | `sandbox` (local/testing only, refused at boot elsewhere: simulates async signed webhooks, never marks paid synchronously) | `paymob` (Paymob Accept, Egypt; implemented from the vendor documentation, **not tested against the live service**: see "Payments (P3.2)" below) | `PAYMENT_DRIVER`, `PAYMENT_RETURN_URL`, `PAYMOB_*`, `PAYMENT_SANDBOX_*` | not configured by default: online methods stay hidden, cash works |
| Push (FCM / APNs) | `PushNotifier` (`App\Integrations\Push`) | P3.6 | `log` (local/testing only, boot-guarded) | Firebase Cloud Messaging HTTP v1 (APNs via FCM). Implemented from the Firebase documentation, **not tested against a live project** | `PUSH_DRIVER` (`fcm`/`log`), `FIREBASE_PROJECT_ID`, `FIREBASE_CREDENTIALS` (absolute path to the service-account JSON, outside the repo) | unset = not configured: push deliveries are `skipped`, inbox/SMS/email work |
| Email | Laravel Mail | P1.1, P3.6 (notification emails via `EmailChannelSender`) | `log` / `array` mailer (refused outside local/testing by the boot guard, `integrations.email`) | TBD (SMTP / SES / Postmark) | `MAIL_*` | a real mailer is required in staging/production |
| Admin TOTP MFA | library only (`pragmarx/google2fa`, `bacon/bacon-qr-code` SVG QR, no external service) | P1.1 | same | same | `ADMIN_MFA_ISSUER`; `ADMIN_MFA_REQUIRED_ROLES`, `ADMIN_LOGIN_LOCKOUT_SECONDS`, `RATE_LIMIT_*` are only defaults of the typed settings (P1.2, ADR-55) | configured |
| Object storage | Laravel filesystem (S3-compatible) | P2.1 | `private` local disk (`storage/app/private-files`, `PRIVATE_FILES_ROOT` overrides the root) | S3-compatible bucket with server-side encryption; point `PROVIDER_DOCUMENTS_DISK` at an S3 disk | `PROVIDER_DOCUMENTS_DISK`, `PRIVATE_FILES_ROOT`, `FILESYSTEM_DISK`, `AWS_*`; demo data: `PROVIDER_SEED_DEMO` | documents and vehicle photos use it (ADR-80) |
| Phone relay / masking | `PhoneRelay` | P3.7 (optional, P2 scope) | `log` | TBD | `PHONE_RELAY_DRIVER`, `PHONE_RELAY_API_KEY` | not configured yet |
| File scanning | `FileScanner` (`App\Integrations\Files`) | P2.1 | `noop` (stores `skipped`, never `clean`; refused outside local/testing) | `clamav` (clamd INSTREAM over TCP, implemented from the clamd documentation, reply parsing unit-tested, **not tested against a live clamd**: verify on staging). No driver configured = files are stored `not_scanned` and the review screen says so; an unreachable engine refuses the upload (`503 file_scanner_unavailable`) | `FILE_SCANNER_DRIVER` (`noop`/`clamav`), `CLAMAV_HOST`, `CLAMAV_PORT`, `CLAMAV_TIMEOUT_SECONDS` | noop in local/testing; production needs ClamAV |
| Admin map tiles (Leaflet) | none (browser fetches tiles) | P0.3 | OpenStreetMap tiles (dev only; respect OSM tile policy) | TBD tile provider or self-hosted tiles | `ADMIN_MAP_TILE_URL`, `ADMIN_MAP_ATTRIBUTION`, `ADMIN_MAP_DEFAULT_LAT/LNG/ZOOM` | configured (OSM default) |
| Crash reporting / analytics | — | P6.4 | none | TBD | — | not configured yet |

## Payments (P3.2)

`PAYMENT_DRIVER` selects the one active gateway: `paymob` (production) or `sandbox` (local/testing; the application refuses to boot with it anywhere else). Empty = not configured: card/wallet payments, saved methods and tips are unavailable and the apps hide them; cash keeps working. `BOOKING_PAYMENT_METHODS` still lists the base methods (default `cash`); `card` and `mobile_wallet` become selectable automatically once a gateway is configured.

| Variable | Default | Purpose |
|---|---|---|
| `PAYMENT_DRIVER` | empty | `paymob` or `sandbox` |
| `PAYMENT_RETURN_URL` | empty | Where the gateway sends the customer after the hosted page. The apps never treat the return as proof of payment |
| `PAYMENT_WEBHOOK_TOLERANCE_SECONDS` | 300 | Replay window for gateways that sign a timestamp (the sandbox does) |
| `PAYMENT_RECONCILE_WINDOW_HOURS` / `PAYMENT_RECONCILE_GRACE_MINUTES` | 48 / 10 | Look-back of `payments:reconcile` and the age before a payment is compared |
| `PAYMENT_SANDBOX_SECRET` | derived from `APP_KEY` | Signing secret of the sandbox webhooks |
| `PAYMENT_SANDBOX_DELAY_SECONDS` | 2 | Delay before the sandbox's webhook job runs (needs a queue worker; `critical` queue) |
| `PAYMENT_SANDBOX_OUTCOME` / `PAYMENT_SANDBOX_REFUND_OUTCOME` | `succeed` | `succeed`, `fail`, `requires_action` (then succeeds) or `silent` (no webhook: exercises timeouts and reconciliation); refunds: `succeed`, `fail`, `silent` |
| `PAYMOB_BASE_URL` | `https://accept.paymob.com` | API host |
| `PAYMOB_API_KEY` | empty | Account API key (auth token for transaction inquiries) |
| `PAYMOB_SECRET_KEY` / `PAYMOB_PUBLIC_KEY` | empty | Intention API keys (create payment, refund / checkout page) |
| `PAYMOB_HMAC_SECRET` | empty | Verifies callbacks (HMAC-SHA512) |
| `PAYMOB_CARD_INTEGRATION_ID` / `PAYMOB_WALLET_INTEGRATION_ID` | empty | Integration (payment method) ids created in the Paymob dashboard |
| `PAYMOB_NOTIFICATION_URL` | empty | Public HTTPS URL of `POST /api/v1/payment-gateways/paymob/webhook`, also registered as the transaction processed callback in the dashboard |

**Paymob merchant requirements (go-live checklist).** A registered Egyptian merchant account with the card and mobile-wallet integrations activated (wallet payments need Paymob's wallet integration and, depending on the contract, the customer's wallet number), the production API/secret/public keys and HMAC secret, the notification URL registered for both the transaction and the card-token callbacks, a refund permission on the account, and a settlement report access for the finance team. Assumptions that must be verified on a Paymob test account before going live (nothing here was run against Paymob): the unified checkout (`/v1/intention/`) request and response fields, the `card_tokens` parameter for saved cards, the callback field order of the HMAC (`amount_cents ... success`, documented in the driver), the transaction inquiry by `merchant_order_id`, the refund endpoint and its asynchronous confirmation callback, and that the callback `order.merchant_order_id` echoes our payment uuid (`special_reference`). The driver treats Paymob as having no signed timestamp: replay protection is the unique event id plus a transaction inquiry that must agree before a payment is marked paid.

Other facts: the sandbox is the reference for the neutral contract (`App\Integrations\Payments\Contracts\PaymentGateway`); a new gateway = a driver class plus an entry in `IntegrationServiceProvider`. Webhooks of any gateway other than the configured one are refused (404). Secrets are only read from the environment and are never logged, stored or returned.

## Flutter build-time configuration (P0.4)

| `--dart-define` | Default | Purpose |
|---|---|---|
| `API_BASE_URL` | `http://10.0.2.2:8000/api/v1` | REST base (also derives the `/broadcasting/auth` origin) |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | `10.0.2.2` / `8080` / `http` | Reverb websocket endpoint |
| `REVERB_KEY` | empty | Public Reverb app key (never the secret) |
| `MAP_TILE_URL` | OSM tile URL | flutter_map tile template; production needs a licensed provider (flutter_map prints a policy warning for the OSM default) |
| `MAP_ATTRIBUTION` | `© OpenStreetMap contributors` | attribution text shown on every map; change it with the tile provider |
| `FLAVOR` | `dev` | `dev`, `staging` or `prod` |

## Booking configuration (P2.3)

No external service is added by P2.3. New deployment-level variables (all optional, defaults in `backend/config/booking.php`):
`BOOKING_PAYMENT_METHODS` (comma list of methods customers may pick; `cash` until P3.2 enables a gateway),
`BOOKING_HARD_MAX_FARE_MINOR` (ceiling when no service or zone maximum exists), `BOOKING_SWEEP_BATCH`. Route estimates reuse the
maps adapter (`MAPS_DRIVER`); a maps outage answers `503 maps_unavailable` and creates no booking. Customer notifications for an
expired search are a log-only hook (`BookingNotifier`, nothing is sent) until the notification platform (P3.6).

## Credential checklist for production

- [ ] SMS Misr account + approved sender token for the launch market (`SMSMISR_*`); run one live OTP end to end before go-live
- [ ] Maps API key with geocoding/directions enabled (and tile provider for flutter_map)
- [ ] Payment gateway merchant account (Paymob: see "Payments (P3.2)"): API/secret/public keys, HMAC secret, integration ids, notification URL; run a live card payment, a wallet payment, a refund and a replayed webhook on staging before go-live
- [ ] Firebase project, service-account JSON (stored outside the repo), APNs key uploaded to Firebase
- [ ] S3-compatible bucket with server-side encryption for the private disk
- [ ] Mail provider credentials and SPF/DKIM for the sending domain
- [ ] Reverb app id/key/secret and TLS termination for WSS

Never commit credentials. `.env`, service-account JSON, keystores, `*.p12`, provisioning profiles are ignored by the root `.gitignore`.

## Notifications and push (P3.6)

- Backend: `PUSH_DRIVER=fcm`, `FIREBASE_PROJECT_ID`, `FIREBASE_CREDENTIALS` (path of the service-account JSON, e.g. `/etc/secrets/firebase.json`, readable by the PHP user, never committed). Queue workers must consume the `notifications` queue (`php artisan queue:work redis --queue=critical,default,notifications,low`). Scheduler adds `notifications:retry-overdue`, `push-campaigns:dispatch-due` (every minute) and the daily inbox prune. `php artisan notifications:sync-templates` (also run by the seeder) creates missing default templates; existing ones are never overwritten.
- Flutter apps: put `google-services.json` in `<app>/android/app/` and `GoogleService-Info.plist` in `<app>/ios/Runner/` (both git-ignored); the Android build applies the Google Services plugin only when the file exists. Upload the APNs key to the Firebase project and enable the Push Notifications capability in Xcode. Without the files the apps build and run; settings shows "push not available". Both apps must use their own Firebase app ids (customer and driver). Android 13+ asks for notification permission after an in-app explanation.
- Go-live checks: send one push per app on a real device (foreground, background, terminated), tap each and confirm the deep link; verify the SMS sender token also allows non-OTP texts; verify DKIM/SPF for notification emails.
