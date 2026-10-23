#!/usr/bin/env bash
# One-command local mirror of CI (.github/workflows/*). Runs every check in order, never stops at
# the first failure, writes a summary to verify-results/SUMMARY.md and one log per step.
#
#   scripts/verify-all.sh                 # everything except APK builds and browser smoke
#   scripts/verify-all.sh --with-apk      # also flutter build apk --debug for both apps
#   scripts/verify-all.sh --with-browser  # also scripts/browser-smoke.sh (needs Chrome)
#   scripts/verify-all.sh --only backend|flutter|scanners
#
# Needs: PHP 8.3, Composer, Node 20, MySQL 8 (testing DB, see docs/SETUP.md), Redis 7, Flutter 3.47.
# Exit code 0 only when every step passed. A step whose tool is missing is recorded as SKIP and
# makes the run fail (a skipped gate is not a passed gate).
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${ROOT}/verify-results"
WITH_APK=0; WITH_BROWSER=0; ONLY="all"
while [ $# -gt 0 ]; do
    case "$1" in
        --with-apk) WITH_APK=1 ;;
        --with-browser) WITH_BROWSER=1 ;;
        --only) ONLY="${2:-all}"; shift ;;
        *) echo "Unknown option: $1"; exit 2 ;;
    esac
    shift
done

rm -rf "${OUT}"; mkdir -p "${OUT}"
SUMMARY="${OUT}/SUMMARY.md"
ROWS=(); FAILED=0; N=0
START="$(date -u +%FT%TZ)"

step() { # step "label" cmd...
    local label="$1"; shift
    N=$((N + 1))
    local log; log="${OUT}/$(printf '%02d' "${N}")-$(echo "${label}" | tr -c 'A-Za-z0-9\n' '-' | tr -s '-').log"
    echo; echo "==> ${label}"
    local t0=$SECONDS rc
    "$@" >"${log}" 2>&1; rc=$?
    local dt=$((SECONDS - t0))
    if [ "${rc}" -eq 0 ]; then
        echo "    PASS (${dt}s)"; ROWS+=("| PASS | ${label} | ${dt}s | $(basename "${log}") |")
    else
        echo "    FAIL rc=${rc} (${dt}s), see ${log}"; tail -n 15 "${log}" | sed 's/^/    | /'
        ROWS+=("| FAIL | ${label} | ${dt}s | $(basename "${log}") |"); FAILED=$((FAILED + 1))
    fi
}

skip() { echo "    SKIP: $1 ($2)"; ROWS+=("| SKIP | $1 | - | $2 |"); FAILED=$((FAILED + 1)); }
need() { command -v "$1" >/dev/null 2>&1; }
in_dir() { local d="$1"; shift; ( cd "$d" && "$@" ); }

run_backend() {
    if ! need php || ! need composer || ! need npm; then skip "backend" "php/composer/npm missing"; return; fi
    local B="${ROOT}/backend"
    step "backend: composer install" in_dir "$B" composer install --no-interaction --prefer-dist --no-progress
    step "backend: composer audit" in_dir "$B" composer audit
    step "backend: pint --test" in_dir "$B" vendor/bin/pint --test
    step "backend: phpstan analyse" in_dir "$B" vendor/bin/phpstan analyse --no-progress --memory-limit=1G
    step "backend: config:clear" in_dir "$B" php artisan config:clear
    step "backend: php artisan test --parallel" in_dir "$B" php artisan test --parallel
    step "backend: npm ci" in_dir "$B" npm ci
    step "backend: npm run build" in_dir "$B" npm run build
    step "backend: vitest" in_dir "$B" npm test
    step "backend: npm audit (production)" in_dir "$B" npm audit --omit=dev
    step "backend: contrast scanner" in_dir "$B" node ../scripts/check-contrast.mjs
}

run_scanners() {
    if ! need php; then skip "scanners" "php missing"; return; fi
    step "scan: translations ar/en parity" php "${ROOT}/scripts/check-translations.php"
    step "scan: ARB parity (apps + platform_core)" php "${ROOT}/scripts/check-arb-parity.php"
    step "scan: route authentication/authorization inventory" bash -c \
        "cd '${ROOT}/backend' && php artisan route:list --json | php ../scripts/route-inventory.php > '${OUT}/route-inventory.md'"
    if need gitleaks; then
        step "scan: gitleaks (tree + history)" gitleaks detect --source "${ROOT}" --no-banner --redact
    else
        skip "scan: gitleaks" "not installed (https://github.com/gitleaks/gitleaks); CI runs it"
    fi
    step "scan: tracked .env files" bash -c "! git -C '${ROOT}' ls-files | grep -E '(^|/)\.env($|\.[^e])' | grep -v '\.env\.example$'"
    step "scan: debug bypass settings in env examples" bash -c \
        "! grep -nE '^(APP_DEBUG=true|OTP_TEST_ENABLED=true|ADMIN_DEMO_ENABLED=true)' '${ROOT}/backend/.env.production.example' 2>/dev/null"
}

run_flutter() {
    if ! need flutter || ! need dart; then skip "flutter" "flutter/dart missing"; return; fi
    local d name
    for d in "${ROOT}/packages/platform_core" "${ROOT}/customer_app" "${ROOT}/driver_app"; do
        name="$(basename "$d")"
        step "${name}: flutter pub get" in_dir "$d" flutter pub get
        step "${name}: dart format" in_dir "$d" dart format --output=none --set-exit-if-changed .
        step "${name}: flutter analyze" in_dir "$d" flutter analyze
        step "${name}: flutter test" in_dir "$d" flutter test
    done
    if [ "${WITH_APK}" -eq 1 ]; then
        for d in "${ROOT}/customer_app" "${ROOT}/driver_app"; do
            step "$(basename "$d"): build apk --debug" in_dir "$d" flutter build apk --debug --dart-define=FLAVOR=dev
        done
    fi
}

case "${ONLY}" in
    all) run_backend; run_scanners; run_flutter ;;
    backend) run_backend ;;
    scanners) run_scanners ;;
    flutter) run_flutter ;;
    *) echo "--only takes all|backend|flutter|scanners"; exit 2 ;;
esac
if [ "${WITH_BROWSER}" -eq 1 ]; then step "browser smoke (admin login)" "${ROOT}/scripts/browser-smoke.sh"; fi

{
    echo "# Verification summary"
    echo
    echo "Started ${START}, finished $(date -u +%FT%TZ). Steps: ${N}. Not passed: ${FAILED}."
    echo
    echo "| Result | Step | Time | Log |"
    echo "| --- | --- | --- | --- |"
    printf '%s\n' "${ROWS[@]}"
} >"${SUMMARY}"

echo; echo "Summary: ${SUMMARY}"
if [ "${FAILED}" -gt 0 ]; then echo "NOT GREEN: ${FAILED} step(s) failed or were skipped."; exit 1; fi
echo "ALL GREEN."
