#!/usr/bin/env bash
# Real-browser smoke check of the admin login form (headless Chrome, no extra dependency).
# Needs: google-chrome (or CHROME_BIN), Node >= 20.10, built assets (npm run build) and local MySQL.
# It points the app at the *testing* database through environment variables (a real environment variable always
# wins over .env), creates a temporary admin, serves the app on 127.0.0.1:${PORT:-8099} with PHP's built-in
# server, runs backend/tests/Browser/admin-login-smoke.mjs, then leaves the testing database empty again.
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT}/backend" || exit 1

PORT="${PORT:-8099}"
SERVER_PID=""

cleanup() {
    [[ -n "${SERVER_PID}" ]] && kill "${SERVER_PID}" 2>/dev/null
    rm -rf "${CACHE_DIR:-}"
}
trap cleanup EXIT

if [[ ! -f .env ]]; then echo "backend/.env is missing (see docs/SETUP.md)"; exit 1; fi
if [[ ! -d public/build ]]; then echo "Run 'npm run build' first."; exit 1; fi

# File sessions and cache in a throwaway directory: no stale login throttling and no Redis state.
CACHE_DIR="$(mktemp -d)"
export APP_ENV=local APP_DEBUG=false APP_URL="http://127.0.0.1:${PORT}"
export DB_DATABASE=delivery_platform_testing DB_URL=
export SESSION_DRIVER=file SESSION_FILES="${CACHE_DIR}/sessions"
export CACHE_STORE=file QUEUE_CONNECTION=sync MAIL_MAILER=log BROADCAST_CONNECTION=null
export ADMIN_MFA_REQUIRED_ROLES=
# Private provider files (demo seed) go to the throwaway directory too.
export PRIVATE_FILES_ROOT="${CACHE_DIR}/private-files"
export LOG_CHANNEL=stderr LOG_LEVEL=error
mkdir -p "${CACHE_DIR}/sessions"

export ADMIN_EMAIL="smoke.admin@example.test"
export ADMIN_PASSWORD="Smoke-Test-Passw0rd!"
export ADMIN_NAME="Smoke Admin"

php artisan cache:clear >/dev/null 2>&1
php artisan migrate:fresh --force --seed --no-interaction >/dev/null || exit 1
php artisan tinker --execute '
$admin = App\Models\Admin::factory()->create(["name" => getenv("ADMIN_NAME"), "email" => getenv("ADMIN_EMAIL"), "password" => getenv("ADMIN_PASSWORD")]);
$admin->givePermissionTo(["dashboard.view", "services.manage", "customers.view", "customers.view_pii", "customers.suspend", "drivers.view", "drivers.verify", "drivers.reject", "documents.view", "vehicles.view", "vehicles.approve", "vehicles.reject", "fare_policies.manage"]);
$customer = App\Models\User::factory()->create(["name" => "Smoke Customer", "phone_e164" => "+201009998877"]);
App\Models\CustomerProfile::query()->create(["user_id" => $customer->id]);
' >/dev/null || exit 1

# Same invocation as `php artisan serve`: document root and working directory are public/.
( cd public && exec php -S "127.0.0.1:${PORT}" -t . ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php >/dev/null 2>&1 ) &
SERVER_PID=$!
for _ in $(seq 1 50); do curl -s -o /dev/null "http://127.0.0.1:${PORT}/up" && break; sleep 0.2; done

BASE_URL="http://127.0.0.1:${PORT}" node --experimental-websocket tests/Browser/admin-login-smoke.mjs
STATUS=$?

# Leave the testing database clean for the PHPUnit suite.
php artisan migrate:fresh --force --no-interaction >/dev/null 2>&1
exit "${STATUS}"
