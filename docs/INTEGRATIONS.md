# External Integrations

> All external services are reached only through contracts in `app/Integrations/<Area>/Contracts` (ADR-12, SRS §22).
> `fake`/`log` drivers are allowed only when `APP_ENV` is `local` or `testing`; the service provider refuses to boot otherwise.
> **Status (P0.2):** `IntegrationServiceProvider` and `config/integrations.php` exist with the boot guard; the contract→driver map
> is empty (no adapters yet). Driver env vars `SMS_DRIVER`, `MAPS_DRIVER`, `PAYMENT_DRIVER`, `PUSH_DRIVER`, `PHONE_RELAY_DRIVER`,
> `FILE_SCANNER_DRIVER` are in `.env.example` (empty = not configured). Reverb is installed and configured.
> Env var names are proposals; the milestone that implements an adapter finalizes them here and in `backend/.env.example`.

| Area | Contract | Milestone | Dev driver | Production provider | Env vars (proposed) | Status |
|---|---|---|---|---|---|---|
| SMS / OTP | `SmsGateway` | P1.3 | `log` (OTP written to log, local/testing only) | TBD (launch-market SMS provider) | `SMS_DRIVER`, `SMS_API_KEY`, `SMS_SENDER_ID` | not configured yet |
| Maps: geocode, reverse, route/ETA | `MapsProvider` | P1.5 | `fake` (straight-line distance) | TBD (Google Maps / Mapbox / OSRM) | `MAPS_DRIVER`, `MAPS_API_KEY`; Flutter tile URL `MAP_TILE_URL` | not configured yet |
| Realtime | Laravel Reverb 1.12 (framework broadcaster) | P0.2 (installed) / P2.2 (channels + events) | Reverb on `localhost:8080` (`php artisan reverb:start`) | Reverb server behind TLS | `BROADCAST_CONNECTION`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`, `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT`, `REVERB_SCALING_ENABLED`, `VITE_REVERB_*` | configured locally (`BROADCAST_CONNECTION=reverb`); tests use `null`; no channels/events yet |
| Payments (card / wallet) | `PaymentGateway` | P3.2 | `fake` (sandbox-style, local/testing only) | TBD (market/merchant eligibility) | `PAYMENT_DRIVER`, `PAYMENT_API_KEY`, `PAYMENT_WEBHOOK_SECRET`, `PAYMENT_MERCHANT_ID` | not configured yet |
| Push (FCM / APNs) | `PushNotifier` | P3.6 | `log` | Firebase Cloud Messaging (APNs via FCM) | `PUSH_DRIVER`, `FIREBASE_PROJECT_ID`, `FIREBASE_CREDENTIALS` (path to service-account JSON outside the repo) | not configured yet |
| Email | Laravel Mail | P3.6 | `log` mailer | TBD (SMTP / SES / Postmark) | `MAIL_*` (already in `.env.example`) | `MAIL_MAILER=log` |
| Object storage | Laravel filesystem (S3-compatible) | P2.1 | `local` private disk | S3-compatible bucket | `FILESYSTEM_DISK`, `AWS_*` (already in `.env.example`) | not configured yet |
| Phone relay / masking | `PhoneRelay` | P3.7 (optional, P2 scope) | `log` | TBD | `PHONE_RELAY_DRIVER`, `PHONE_RELAY_API_KEY` | not configured yet |
| File scanning | `FileScanner` | P2.1 | `fake` (always clean, local/testing only) | TBD (ClamAV / cloud AV) | `FILE_SCANNER_DRIVER`, `CLAMAV_HOST` | not configured yet |
| Admin map tiles (Leaflet) | none (browser fetches tiles) | P0.3 | OpenStreetMap tiles (dev only; respect OSM tile policy) | TBD tile provider or self-hosted tiles | `ADMIN_MAP_TILE_URL`, `ADMIN_MAP_ATTRIBUTION`, `ADMIN_MAP_DEFAULT_LAT/LNG/ZOOM` | configured (OSM default) |
| Crash reporting / analytics | — | P6.4 | none | TBD | — | not configured yet |

## Credential checklist for production

- [ ] SMS provider account + sender id approved for the launch market
- [ ] Maps API key with geocoding/directions enabled (and tile provider for flutter_map)
- [ ] Payment gateway merchant account, API keys, webhook secret, sandbox for staging
- [ ] Firebase project, service-account JSON (stored outside the repo), APNs key uploaded to Firebase
- [ ] S3-compatible bucket with server-side encryption for the private disk
- [ ] Mail provider credentials and SPF/DKIM for the sending domain
- [ ] Reverb app id/key/secret and TLS termination for WSS

Never commit credentials. `.env`, service-account JSON, keystores, `*.p12`, provisioning profiles are ignored by the root `.gitignore`.
