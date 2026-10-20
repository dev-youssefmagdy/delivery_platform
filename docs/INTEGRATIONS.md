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
| Realtime | Laravel Reverb 1.12 (framework broadcaster) | P0.2 (installed) / P2.2 (channels + events) | Reverb on `localhost:8080` (`php artisan reverb:start`) | Reverb server behind TLS | `BROADCAST_CONNECTION`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`, `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT`, `REVERB_SCALING_ENABLED`, `VITE_REVERB_*` | configured locally (`BROADCAST_CONNECTION=reverb`); tests use `null`; no channels/events yet |
| Payments (card / wallet) | `PaymentGateway` | P3.2 | `fake` (sandbox-style, local/testing only) | TBD (market/merchant eligibility) | `PAYMENT_DRIVER`, `PAYMENT_API_KEY`, `PAYMENT_WEBHOOK_SECRET`, `PAYMENT_MERCHANT_ID` | not configured yet |
| Push (FCM / APNs) | `PushNotifier` | P3.6 | `log` | Firebase Cloud Messaging (APNs via FCM) | `PUSH_DRIVER`, `FIREBASE_PROJECT_ID`, `FIREBASE_CREDENTIALS` (path to service-account JSON outside the repo) | not configured yet |
| Email | Laravel Mail | P1.1 (admin invitations / password reset), P3.6 (platform) | `log` mailer | TBD (SMTP / SES / Postmark) | `MAIL_*` (already in `.env.example`) | `MAIL_MAILER=log`; production needs a real mailer before admins can be invited |
| Admin TOTP MFA | library only (`pragmarx/google2fa`, `bacon/bacon-qr-code` SVG QR, no external service) | P1.1 | same | same | `ADMIN_MFA_ISSUER`; `ADMIN_MFA_REQUIRED_ROLES`, `ADMIN_LOGIN_LOCKOUT_SECONDS`, `RATE_LIMIT_*` are only defaults of the typed settings (P1.2, ADR-55) | configured |
| Object storage | Laravel filesystem (S3-compatible) | P2.1 | `local` private disk | S3-compatible bucket | `FILESYSTEM_DISK`, `AWS_*` (already in `.env.example`) | not configured yet |
| Phone relay / masking | `PhoneRelay` | P3.7 (optional, P2 scope) | `log` | TBD | `PHONE_RELAY_DRIVER`, `PHONE_RELAY_API_KEY` | not configured yet |
| File scanning | `FileScanner` | P2.1 | `fake` (always clean, local/testing only) | TBD (ClamAV / cloud AV) | `FILE_SCANNER_DRIVER`, `CLAMAV_HOST` | not configured yet |
| Admin map tiles (Leaflet) | none (browser fetches tiles) | P0.3 | OpenStreetMap tiles (dev only; respect OSM tile policy) | TBD tile provider or self-hosted tiles | `ADMIN_MAP_TILE_URL`, `ADMIN_MAP_ATTRIBUTION`, `ADMIN_MAP_DEFAULT_LAT/LNG/ZOOM` | configured (OSM default) |
| Crash reporting / analytics | — | P6.4 | none | TBD | — | not configured yet |

## Flutter build-time configuration (P0.4)

| `--dart-define` | Default | Purpose |
|---|---|---|
| `API_BASE_URL` | `http://10.0.2.2:8000/api/v1` | REST base (also derives the `/broadcasting/auth` origin) |
| `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` | `10.0.2.2` / `8080` / `http` | Reverb websocket endpoint |
| `REVERB_KEY` | empty | Public Reverb app key (never the secret) |
| `MAP_TILE_URL` | OSM tile URL | flutter_map tile template; production needs a licensed provider (flutter_map prints a policy warning for the OSM default) |
| `MAP_ATTRIBUTION` | `© OpenStreetMap contributors` | attribution text shown on every map; change it with the tile provider |
| `FLAVOR` | `dev` | `dev`, `staging` or `prod` |

## Credential checklist for production

- [ ] SMS Misr account + approved sender token for the launch market (`SMSMISR_*`); run one live OTP end to end before go-live
- [ ] Maps API key with geocoding/directions enabled (and tile provider for flutter_map)
- [ ] Payment gateway merchant account, API keys, webhook secret, sandbox for staging
- [ ] Firebase project, service-account JSON (stored outside the repo), APNs key uploaded to Firebase
- [ ] S3-compatible bucket with server-side encryption for the private disk
- [ ] Mail provider credentials and SPF/DKIM for the sending domain
- [ ] Reverb app id/key/secret and TLS termination for WSS

Never commit credentials. `.env`, service-account JSON, keystores, `*.p12`, provisioning profiles are ignored by the root `.gitignore`.
