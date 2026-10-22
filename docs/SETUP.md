# Local Setup

Verified versions on the reference machine (2026-10-13): PHP 8.3.35, Composer 2.9.5, Node 20.20.2 / npm 10.8.2,
MySQL 8.0.46, Redis 7.0.15, Flutter 3.47.1 / Dart 3.13.1.

## 1. Prerequisites

- PHP 8.3 with extensions: `bcmath`, `intl`, `mbstring`, `pdo_mysql`, `redis` (phpredis), `gd` or `imagick`, `sodium`, `zip`, `pcntl`.
- Composer 2.x, Node 20+, MySQL 8.0, Redis 7.
- Flutter 3.47 stable (Dart 3.13). Android SDK / Xcode for device builds.

## 2. Databases (MySQL 8)

Run as a MySQL admin (on Ubuntu: `sudo mysql`, or `mysql -uroot -p`). Replace `CHANGE_ME` with a strong local password.

```sql
CREATE DATABASE IF NOT EXISTS delivery_platform         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS delivery_platform_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'delivery_platform'@'localhost' IDENTIFIED BY 'CHANGE_ME';
CREATE USER IF NOT EXISTS 'delivery_platform'@'127.0.0.1' IDENTIFIED BY 'CHANGE_ME';

GRANT ALL PRIVILEGES ON delivery_platform.*                    TO 'delivery_platform'@'localhost', 'delivery_platform'@'127.0.0.1';
GRANT ALL PRIVILEGES ON delivery_platform_testing.*            TO 'delivery_platform'@'localhost', 'delivery_platform'@'127.0.0.1';
-- Parallel tests (ParaTest) create delivery_platform_testing_test_1, _2, …
GRANT ALL PRIVILEGES ON `delivery_platform_testing\_%`.*       TO 'delivery_platform'@'localhost', 'delivery_platform'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Redis needs no setup: DB 0 (default), DB 1 (cache), DB 15 (tests).

## 3. Backend

```bash
cd backend
composer install
cp .env.example .env            # then set DB_PASSWORD
php artisan key:generate
php artisan migrate
npm install
npm run build                   # or: npm run dev
php artisan serve               # http://localhost:8000  (or: composer dev)
```

First admin (there are no default credentials):

```bash
php artisan db:seed                 # reference data + admin permissions + the 12 system roles (idempotent)
php artisan admin:create            # asks for name, email and password (interactive only); role super_admin by default
```

Sign in at `/admin/login`. Roles in `ADMIN_MFA_REQUIRED_ROLES` (Super Admin by default) must enrol an authenticator app at the
first sign-in. With `MAIL_MAILER=log`, invitation and password-reset links are written to `storage/logs/laravel.log`.

Provider documents (P2.1): uploads go to the private disk (`storage/app/private-files`, created on first upload) and need a file
scanner driver: `FILE_SCANNER_DRIVER=noop` in `.env` for local work (files are stored as "not scanned"), `clamav` with `CLAMAV_HOST` /
`CLAMAV_PORT` anywhere real. `db:seed` also creates the default document types (Egypt requirements, to be reviewed for local
compliance) and, outside production, four clearly named demo providers (`PROVIDER_SEED_DEMO=false` turns that off).

Checks:

```bash
composer check                  # pint --test + phpstan + php artisan test --parallel
composer lint:fix               # auto-format with Pint
```

Tests use `delivery_platform_testing` and Redis DB 15 (`phpunit.xml`); credentials come from `.env`.

### Workers and scheduler (needed from P2.2 onward)

```bash
php artisan queue:work redis --queue=critical,default,notifications,low
php artisan schedule:work       # local; production: cron `* * * * * php /path/artisan schedule:run`
php artisan reverb:start        # realtime, from P2.2
```

From P2.3 the scheduler also runs `bookings:expire-searches` every 5 seconds (ends search windows that ran out and opens the
search of scheduled bookings that are due); `schedule:run` keeps looping for the whole minute while a sub-minute task exists, so a
plain per-minute cron is enough. **Dispatch waves and search expiry are delayed queue jobs: run them on a real queue
(`QUEUE_CONNECTION=redis`). The `sync` driver ignores delays, so a booking created with it would chain all waves immediately
(the PHPUnit suite fakes these jobs for that reason).**

Production process management (supervisor/systemd), TLS and reverse proxy are documented in P6.4.

## 4. Flutter apps

```bash
cd customer_app                 # or driver_app
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1 --dart-define=FLAVOR=dev
dart format --output=none --set-exit-if-changed . && flutter analyze && flutter test
```

`packages/platform_core` is the shared package (path dependency of both apps); run the same three checks there. Build-time
configuration (all optional, defaults target the Android emulator): `API_BASE_URL`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_KEY`,
`REVERB_SCHEME`, `MAP_TILE_URL`, `FLAVOR` (`dev|staging|prod`). Use `10.0.2.2` for the host machine from the Android emulator.

Code generation (committed output): `dart run build_runner build --delete-conflicting-outputs` and `flutter gen-l10n`.
Android application id: Gradle property `appId` (e.g. `appId=com.acme.customer` in `~/.gradle/gradle.properties`; default `com.deliveryplatform.customer|driver`);
iOS bundle id: edit `ios/Flutter/AppConfig.xcconfig`. Minimum OS: Android 7.0 (API 24), iOS 15.0. Android licenses must be
accepted (`flutter doctor --android-licenses`) before `flutter build apk`.

## 5. Whole-repo gate

```bash
make check                      # = scripts/check.sh all (backend + every Flutter app/package)
make check-backend | make check-flutter
make browser-smoke              # real-browser check of the admin login form (needs Chrome + Node >= 20.10; not in the gate)
```

## 6. Laravel Boost (AI tooling)

`laravel/boost` is installed (dev). Guidelines: `backend/CLAUDE.md` / `backend/AGENTS.md`; MCP server config `backend/.mcp.json`
(`php artisan boost:mcp`); skills in `backend/.claude/skills`. Refresh after dependency changes with `php artisan boost:update`.

## 7. Troubleshooting

- `Access denied for user` in tests → the user lacks the `delivery_platform_testing\_%` grant (needed by `--parallel`).
- `Unable to locate file in Vite manifest` → run `npm run build`.
- Stale config in tests → `php artisan config:clear` (the `composer test` script does it).

## Offers and negotiation (P2.4)

The scheduler also runs `offers:expire` every 5 seconds; each offer additionally gets a delayed `ExpireOffer` job on the `critical` queue, so the same real queue worker as for dispatch waves is required (`QUEUE_CONNECTION=redis`, never `sync` outside tests). Customers receive offers on `private-customer.{uuid}`, so the Reverb / WebSocket server must be running for live cards; the apps still read `GET /bookings/{booking}/offers` after a sequence gap. Optional env: `BOOKING_OFFER_SHOW_PLATE` (default true), `BOOKING_OFFER_SWEEP_BATCH` (default 200).

Concurrency proof by hand (needs MySQL and a booking in `offers_received` with two or more offers, made through the API or the seeder): `php artisan qa:race-accept {booking-uuid} --workers=8` starts 8 real processes that each choose one of the offers at the same time and prints PASS when exactly one won; add `--repeat-key` to make every worker send the same request (all must agree). `php artisan qa:race-offer {booking-uuid} {provider-uuid} --workers=8 [--repeat-key]` hammers one provider's counter-offers and checks the offer and revision limits. Both refuse to run outside `local` / `testing`.

Trip lifecycle (P2.5): the arrival radius that counts an arrival as GPS-verified is `BOOKING_ARRIVAL_RADIUS_M` (default 300). PIN attempts, the rating window, the minimum rating sample and the share-link lifetime are platform settings (Settings > Safety / Booking). Cancellation fees come from Admin > Pricing > Cancellation policies; with none configured the defaults (120 s free, no fees) apply. The public share page `/t/{token}` is served by the website routes and needs no extra service; live position on it comes from the same location uploads as dispatch.

## Notifications and push (P3.6)

Workers: add the `notifications` queue (`--queue=critical,default,notifications,low`). Locally set `PUSH_DRIVER=log` (pushes are written to the log, nothing is delivered) and `php artisan db:seed --class=NotificationTemplateSeeder` for the default ar/en templates. To try real push: create a Firebase project per app, download `google-services.json` (Android) and `GoogleService-Info.plist` (iOS) into `customer_app` / `driver_app` (`android/app/`, `ios/Runner/`; git-ignored), set `PUSH_DRIVER=fcm`, `FIREBASE_PROJECT_ID`, `FIREBASE_CREDENTIALS`, and run on a device (emulators without Play services get no token). After pulling Flutter changes run `flutter pub get` in `packages/platform_core` and both apps.
