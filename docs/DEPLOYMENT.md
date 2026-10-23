# Deployment

**Status: written, never executed.** No server, TLS certificate, supervisor unit, nginx file or deploy script below was run against a real machine;
treat every block as a reviewed starting point and rehearse on staging first. Operations after go-live: `docs/OPERATIONS.md`. Credentials: `docs/RELEASE_REPORT.md` section 6.

## 1. Server requirements

| Component | Requirement |
| --- | --- |
| OS | Linux (Ubuntu 22.04/24.04 LTS assumed), UTC system clock |
| PHP | 8.3 FPM with `bcmath intl mbstring pdo_mysql redis (phpredis) gd or imagick sodium zip pcntl curl xml opcache` |
| MySQL | 8.0, `utf8mb4` / `utf8mb4_unicode_ci`, InnoDB; a dedicated user with rights on its schema only (no `SUPER`, so no triggers) |
| Redis | 7.x, persistence (AOF) on; cache, queue, sessions, locks and Reverb scaling all use it. Password required if not on a private network |
| Node | 20+ **only on the build machine** (`npm ci && npm run build`); the server needs no Node |
| Web | nginx (or any proxy that supports WebSocket upgrade), TLS certificate, HTTP/2 |
| Process manager | supervisor (units in section 5) or systemd equivalents |
| Storage | S3-compatible bucket for the private disk (section 7); local disk for `storage/logs` |
| Antivirus | ClamAV daemon (clamd) reachable at `CLAMAV_HOST:CLAMAV_PORT` |
| Sizing | Unmeasured (`docs/PERFORMANCE.md` section 0). Start with 2 vCPU / 4 GB for app + workers and a separate managed MySQL and Redis; tune from real metrics |

Install production dependencies with `composer install --no-dev --optimize-autoloader --no-interaction` (Laravel Boost registers a dev-only route).

## 2. Layout and nginx with TLS

```
/var/www/delivery-platform/
  releases/20261101-1200/   # one directory per release (git archive / CI artifact)
  shared/.env  shared/storage/
  current -> releases/20261101-1200
```

Each release symlinks `backend/.env` and `backend/storage` to `shared/`. Document root is `current/backend/public`.

```nginx
# /etc/nginx/sites-available/delivery-platform
server { listen 80; listen [::]:80; server_name api.example.com ws.example.com;
         location /.well-known/acme-challenge/ { root /var/www/acme; }
         location / { return 301 https://$host$request_uri; } }

server {
    listen 443 ssl; listen [::]:443 ssl; http2 on;
    server_name api.example.com;
    ssl_certificate     /etc/letsencrypt/live/api.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_session_timeout 1d; ssl_session_cache shared:SSL:10m; ssl_session_tickets off;

    root /var/www/delivery-platform/current/backend/public;
    index index.php;
    client_max_body_size 12m;            # provider documents, chat and proof pictures; keep >= the largest *_MAX_KB setting
    server_tokens off;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60s;
    }
    location ~ /\.(?!well-known) { deny all; }
    location ~* ^/build/ { expires 1y; add_header Cache-Control "public, immutable"; }
    # HSTS, CSP and the other security headers come from the application (SecurityHeaders middleware): do not duplicate them here.
}

# Realtime (Reverb) on its own host name, TLS terminated here.
server {
    listen 443 ssl; http2 on;
    server_name ws.example.com;
    ssl_certificate     /etc/letsencrypt/live/ws.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ws.example.com/privkey.pem;
    location / {
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade; proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host; proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 120s;          # idle WebSocket; Reverb pings keep it alive
        proxy_pass http://127.0.0.1:8080;
    }
}
```

Certificates: `certbot certonly --webroot -w /var/www/acme -d api.example.com -d ws.example.com` plus the renewal timer; test with `certbot renew --dry-run`.
Reverb must use `REVERB_HOST=ws.example.com`, `REVERB_PORT=443`, `REVERB_SCHEME=https` (what clients connect to) and `REVERB_SERVER_HOST=127.0.0.1`, `REVERB_SERVER_PORT=8080` (what it listens on). Behind a proxy set `TRUSTED_PROXIES`.

### Admin on a separate path or subdomain

The admin panel lives under `ADMIN_PATH` (default `admin`). Two options:

1. **Unguessable path**: `ADMIN_PATH=ops-9f3k...`; same host. Obscurity is not a control, so also use option 2 or the IP allowlist.
2. **Subdomain with network restriction**: a second `server { server_name admin.example.com; ... }` block with the same `root` and PHP handler, plus `allow <office/VPN CIDR>; deny all;`
   (or a VPN / zero-trust proxy), and have the main host return 404 for the admin path (`location ^~ /ops-9f3k { return 404; }`). Add `Admin > Settings > Security > Admin IP allowlist` as the application layer
   (it must contain your own address; recovery steps are in `docs/INTEGRATIONS.md`, "Security hardening"). Set `SESSION_DOMAIN` only if admin and website must share cookies (they should not), and keep `SESSION_SECURE_COOKIE=true`.
   The admin CSP is derived from the path, so changing `ADMIN_PATH` needs `php artisan config:cache` again.

## 3. Environment per environment

Template: `backend/.env.production.example`. Differences:

| Variable | local | staging | production |
| --- | --- | --- | --- |
| `APP_ENV` | `local` | `staging` | `production` |
| `APP_DEBUG` | true allowed | **false** | **false: the application refuses web requests when true in production** |
| `APP_URL` | `http://localhost:8000` | `https://staging-api...` | `https://api...` |
| `SMS_DRIVER`, `MAPS_DRIVER`, `PAYMENT_DRIVER`, `PUSH_DRIVER`, `FILE_SCANNER_DRIVER` | `log` / `fake` / `sandbox` / `log` / `noop` | real drivers with vendor sandbox credentials (`SMSMISR_ENVIRONMENT=2`, Paymob test keys) | real drivers, live credentials. `fake`, `log`, `sandbox` and `noop` are refused outside local/testing by the service provider |
| `OTP_TEST_ENABLED`, `ADMIN_DEMO_ENABLED`, `PROVIDER_SEED_DEMO` | as needed | false | false (never seed demo providers) |
| `TRUSTED_PROXIES` | empty | proxy IP/CIDR | proxy IP/CIDR (`*` only if the app is reachable exclusively through the proxy) |
| `CORS_ALLOWED_ORIGINS` | empty | the staging website origin if a browser client needs `/api/*`; mobile apps need none | explicit origins only, no `*` |
| `CSP_MODE` | `enforce` | `report-only` | `report-only` for the first release, watch browser consoles on every admin and website page, then `enforce` |
| `CSP_EXTRA_IMG_SRC` | empty | CDN / bucket origin serving CMS images | same |
| `QUEUE_CONNECTION` / `QUEUE_LONG_CONNECTION` | `sync` or `redis` / empty | `redis` / `redis_long` | `redis` / **`redis_long`** (exports, daily metrics, push campaigns need the 960 s `retry_after`) |
| `LOG_STACK` | `single` | `json` or `stderr_json` | `json` or `stderr_json`; plus `SENTRY_DSN` |
| `SESSION_SECURE_COOKIE` | false | true | true |

After editing `.env`: `php artisan config:cache`. Secrets live only in the server environment or a secret manager, never in git and never in the database.

## 4. Scheduler (cron)

On exactly one node: `* * * * * www-data cd /var/www/delivery-platform/current/backend && php artisan schedule:run >> /dev/null 2>&1`
(with several app nodes use `schedule:run --isolated` or a single scheduler node). `bookings:expire-searches` and `offers:expire` loop every 5 seconds inside the minute. The health page needs the heartbeat from this cron.

## 5. Supervisor

Same commands as `docs/OPERATIONS.md` (queue per table row, `numprocs` as listed). Complete units:

```ini
; /etc/supervisor/conf.d/delivery-platform.conf   (WorkingDir via full artisan path; use the `current` symlink)
[program:dp-queue-critical]
command=php /var/www/delivery-platform/current/backend/artisan queue:work redis --queue=critical --sleep=1 --tries=3 --timeout=60 --max-time=3600
numprocs=2
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
stopasgroup=true
stopwaitsecs=70
user=www-data

[program:dp-queue-general]
command=php /var/www/delivery-platform/current/backend/artisan queue:work redis --queue=default,notifications --sleep=1 --timeout=60 --max-time=3600
numprocs=2
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
stopasgroup=true
stopwaitsecs=70
user=www-data

; Long jobs: needs QUEUE_LONG_CONNECTION=redis_long in .env. stopwaitsecs must exceed the 900 s timeout.
[program:dp-queue-long]
command=php /var/www/delivery-platform/current/backend/artisan queue:work redis_long --queue=notifications,low --timeout=900 --max-time=3600
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
stopwaitsecs=960
user=www-data

[program:dp-reverb]
command=php /var/www/delivery-platform/current/backend/artisan reverb:start --host=127.0.0.1 --port=8080
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
stopwaitsecs=30
minfds=65535
user=www-data
```

`supervisorctl reread && supervisorctl update`. Raise the file-descriptor limit for Reverb (`LimitNOFILE` / `minfds`). Reverb scale-out: `REVERB_SCALING_ENABLED=true` (`docs/PERFORMANCE.md` section 5).

## 6. Database and Redis

Create the schema and user as in `docs/SETUP.md` section 2 (without the testing grants). Use TLS to a managed MySQL if it is off-host. Enable automated backups on day one (`docs/OPERATIONS.md`).

## 7. Private storage on S3-compatible object storage

Provider documents, vehicle photos, chat attachments, payout evidence, proof pictures and admin exports use private disks (`FILESYSTEM_DISK`, `PROVIDER_DOCUMENTS_DISK`, `ADMIN_EXPORTS_DISK`).
Set `s3` for each, `AWS_*` credentials, `AWS_USE_PATH_STYLE_ENDPOINT=true` and `AWS_ENDPOINT` for non-AWS providers (MinIO, Spaces, Wasabi). The bucket must be **private** (block public access), with
server-side encryption, versioning and a lifecycle rule for old versions. The application only serves these files through authorised routes or short-lived signed URLs. CMS images that must be
public belong on a separate public bucket/CDN whose origin is added to `CSP_EXTRA_IMG_SRC`. Verify the disk configuration names in `config/filesystems.php` before first use; uploads to S3 were never exercised.

## 8. Zero-downtime deploy

Run on the build/CI machine, then on each app server. `X` is the new release directory.

```bash
# build (CI machine or server)
cd X/backend
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build            # needs Node 20; or build in CI and ship public/build
ln -sfn ../../shared/.env .env ; rm -rf storage && ln -sfn ../../shared/storage storage

# one node only, before switching traffic
php artisan migrate --force                       # must be backward compatible (see below)
php artisan admin:sync-role-permissions           # EVERY deploy: grants permissions added in code to stored roles (--dry-run to preview)
php artisan db:seed --class=PermissionSeeder --force   # when the release adds permissions (P5.7+); idempotent
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache

# every node
ln -sfn X /var/www/delivery-platform/current
sudo systemctl reload php8.3-fpm                  # clear OPcache
php artisan queue:restart                         # workers exit after the current job; supervisor restarts them
sudo supervisorctl restart dp-reverb              # only when the release changes broadcasting
curl -fsS https://api.example.com/api/v1/health   # smoke
```

Then open Admin > Integration health (scheduler heartbeat, queue, drivers). Keep the previous 3 releases for rollback. Maintenance mode (`php artisan down --render=...`) is only for migrations that cannot be made compatible.

### Migrations: compatibility and rollback

* Expand then contract: a release only adds nullable columns / tables / indexes; dropping or renaming happens one release later, after no running code reads the old shape (old workers run until `queue:restart` completes).
* Never edit a migration that has run in production. Large-table indexes (`journal_lines`, `provider_locations`, `notification_deliveries`) lock or load the table: run them off-peak.
* **Rollback = switch the symlink back and `queue:restart`.** Do not run `migrate:rollback` in production: down methods are not guaranteed to be data-safe. Restore from backup only for data loss. Migrations were run only on the development database, never against production-sized data.
* A new release that adds permissions needs `admin:sync-role-permissions` again after rollback-forward; it never overrides what an administrator removed.

## 9. Mobile apps

| Flavor | `--dart-define` | Notes |
| --- | --- | --- |
| `dev` | `FLAVOR=dev`, `API_BASE_URL=http://10.0.2.2:8000/api/v1` (emulator) | cleartext allowed for local only |
| `staging` | `FLAVOR=staging`, `API_BASE_URL=https://staging-api.example.com/api/v1`, `REVERB_HOST=staging-ws.example.com`, `REVERB_PORT=443`, `REVERB_SCHEME=https`, `REVERB_KEY=<staging key>` | |
| `prod` | `FLAVOR=prod`, `API_BASE_URL=https://api.example.com/api/v1`, `REVERB_HOST=ws.example.com`, `REVERB_PORT=443`, `REVERB_SCHEME=https`, `REVERB_KEY=<prod key>`, `MAP_TILE_URL=<licensed tile URL>` | |

Release builds: `flutter build appbundle --release --dart-define=...` (Android) and `flutter build ipa --release --dart-define=...` (macOS only), in `customer_app` and `driver_app` separately.
Application id: Gradle property `appId`; iOS bundle id in `ios/Flutter/AppConfig.xcconfig` (`docs/SETUP.md` section 4). Public tile servers (OSM) are not for production use.

### Firebase and APNs

1. One Firebase project (or one per environment) with **two** apps per platform (customer, driver).
2. Download `google-services.json` into `<app>/android/app/` and `GoogleService-Info.plist` into `<app>/ios/Runner/` (both git-ignored; inject them from CI secrets at build time).
3. Upload the APNs authentication key (.p8, key id, team id) in Firebase > Project settings > Cloud Messaging; enable Push Notifications and Background Modes in Xcode.
4. Create a service account with the Firebase Cloud Messaging API role; put the JSON on the server **outside the repository** (`FIREBASE_CREDENTIALS=/etc/delivery-platform/firebase-service-account.json`, mode 0400, owner www-data); `PUSH_DRIVER=fcm`, `FIREBASE_PROJECT_ID`.
5. Test a real push to one Android and one iOS device from the admin notification tools. Push through FCM/APNs was never tested.

### Signing (outside git)

* Android: generate an upload keystore, keep it and its passwords in a secret store, reference them from `android/key.properties` (git-ignored) in CI. Enrol in Play App Signing. Never commit `*.jks` or `key.properties`.
* iOS: Apple Developer account, distribution certificate and provisioning profiles per app (customer, driver), stored as CI secrets or fastlane match; build on macOS only. **The Xcode projects were edited by hand and have never been built.**
* Store links: Admin > App versions (`store_url`) and the public website (`WEBSITE_*`, CMS) must point to the real listings.

## 10. CI

GitHub Actions (origin is GitHub): `.github/workflows/backend.yml`, `flutter.yml`, `scanners.yml`. Local mirror: `scripts/verify-all.sh` (summary in `verify-results/SUMMARY.md`). **None of these have run.**
Set the repository secret `GITHUB_TOKEN` (automatic) and, for private forks of gitleaks-action on organisations, `GITLEAKS_LICENSE`. Branch protection: require the three workflows on `main`.

macOS runner for iOS (not enabled, needs signing secrets and an Apple account):

```yaml
  ios:
    runs-on: macos-14
    steps:
      - uses: actions/checkout@v4
      - uses: subosito/flutter-action@v2
        with: { flutter-version: '3.47.1', channel: stable }
      - run: cd customer_app && flutter pub get && flutter build ios --release --no-codesign --dart-define=FLAVOR=staging --dart-define=API_BASE_URL=https://staging-api.example.com/api/v1
```

GitLab CI alternative (`.gitlab-ci.yml`, sketch; stage names mirror the jobs above):

```yaml
stages: [backend, scanners, flutter]
backend:
  stage: backend
  image: php:8.3-cli   # build an image that adds the extensions of section 1, Node 20 and Composer
  services: [{ name: "mysql:8.0", alias: mysql }, { name: "redis:7", alias: redis }]
  variables: { MYSQL_ROOT_PASSWORD: root, DB_HOST: mysql, DB_USERNAME: root, DB_PASSWORD: root, DB_DATABASE: delivery_platform_testing, REDIS_HOST: redis }
  script:
    - cd backend && composer install --no-interaction && cp .env.example .env && php artisan key:generate
    - vendor/bin/pint --test && vendor/bin/phpstan analyse --no-progress --memory-limit=1G
    - php artisan test --parallel
    - npm ci && npm run build && npm test
scanners:
  stage: scanners
  script: [php scripts/check-translations.php, php scripts/check-arb-parity.php]
flutter:
  stage: flutter
  image: ghcr.io/cirruslabs/flutter:3.47.1
  parallel: { matrix: [{ PROJECT: [packages/platform_core, customer_app, driver_app] }] }
  script: [cd $PROJECT, flutter pub get, dart format --output=none --set-exit-if-changed ., flutter analyze, flutter test]
```

## 11. Go-live order

1. Provision servers, MySQL, Redis, bucket, ClamAV, DNS, TLS. 2. Create `.env` (section 3). 3. First deploy (section 8) with `migrate --force` and `db:seed --force` (reference data, permissions, roles). 4. `admin:create`. 5. First-run and readiness checklists in `docs/RELEASE_REPORT.md`. 6. Backups enabled and one restore rehearsed. 7. Staging verification of every integration with sandbox credentials, then live credentials. 8. `CSP_MODE=report-only` for a week, then `enforce`.
