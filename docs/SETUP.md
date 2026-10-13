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

Production process management (supervisor/systemd), TLS and reverse proxy are documented in P6.4.

## 4. Flutter apps

```bash
cd customer_app                 # or driver_app
flutter pub get
flutter run                     # API base URL configuration arrives in P0.4 (--dart-define)
dart format --output=none --set-exit-if-changed . && flutter analyze && flutter test
```

`packages/platform_core` (shared package) is created in P0.4.

## 5. Whole-repo gate

```bash
make check                      # = scripts/check.sh all (backend + every Flutter app/package)
make check-backend | make check-flutter
```

## 6. Laravel Boost (AI tooling)

`laravel/boost` is installed (dev). Guidelines: `backend/CLAUDE.md` / `backend/AGENTS.md`; MCP server config `backend/.mcp.json`
(`php artisan boost:mcp`); skills in `backend/.claude/skills`. Refresh after dependency changes with `php artisan boost:update`.

## 7. Troubleshooting

- `Access denied for user` in tests → the user lacks the `delivery_platform_testing\_%` grant (needed by `--parallel`).
- `Unable to locate file in Vite manifest` → run `npm run build`.
- Stale config in tests → `php artisan config:clear` (the `composer test` script does it).
