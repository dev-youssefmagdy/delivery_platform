#!/usr/bin/env bash
# Milestone quality gate (Plan §11.1): backend PHP + assets, then every Flutter
# app/package in the monorepo. Usage:
#   scripts/check.sh            # everything
#   scripts/check.sh backend    # backend only
#   scripts/check.sh flutter    # Flutter apps/packages only
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET="${1:-all}"
FAILURES=()

run() {
    local label="$1"; shift
    echo
    echo "==> ${label}"
    if "$@"; then
        echo "    PASS: ${label}"
    else
        echo "    FAIL: ${label}"
        FAILURES+=("${label}")
    fi
}

check_backend() {
    cd "${ROOT}/backend" || return 1
    run "backend: pint --test" vendor/bin/pint --test
    run "backend: phpstan analyse" vendor/bin/phpstan analyse --no-progress --memory-limit=1G
    run "backend: config:clear" php artisan config:clear --ansi
    run "backend: php artisan test --parallel" php artisan test --parallel
    run "backend: npm run build" npm run build
}

check_flutter_project() {
    local dir="$1" name
    name="$(basename "${dir}")"
    cd "${dir}" || return 1
    run "${name}: flutter pub get" flutter pub get
    run "${name}: dart format" dart format --output=none --set-exit-if-changed .
    run "${name}: flutter analyze" flutter analyze
    if [ -d test ]; then
        run "${name}: flutter test" flutter test
    fi
}

check_flutter() {
    local dir
    for dir in "${ROOT}/customer_app" "${ROOT}/driver_app" "${ROOT}"/packages/*/; do
        [ -f "${dir%/}/pubspec.yaml" ] && check_flutter_project "${dir%/}"
    done
}

case "${TARGET}" in
    backend) check_backend ;;
    flutter) check_flutter ;;
    all) check_backend; check_flutter ;;
    *) echo "Unknown target: ${TARGET} (use all|backend|flutter)"; exit 2 ;;
esac

echo
if [ "${#FAILURES[@]}" -gt 0 ]; then
    echo "FAILED (${#FAILURES[@]}):"
    printf '  - %s\n' "${FAILURES[@]}"
    exit 1
fi
echo "All checks passed."
