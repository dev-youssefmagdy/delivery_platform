# Delivery platform

Ride-hailing and delivery marketplace (Laravel 13 backend with admin panel and public website, Flutter customer and driver apps, shared `platform_core` package).

> **Status: feature-complete build, not yet verified.** No test or quality gate has run since milestone P2.3. Read `docs/RELEASE_REPORT.md` before anything else.

## One-command setup (local)

Prerequisites (`docs/SETUP.md`): PHP 8.3 + extensions, Composer, Node 20, MySQL 8 (databases and grants from SETUP section 2), Redis 7, Flutter 3.47.

```bash
cd backend && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate --seed \
  && npm install && npm run build && php artisan admin:create && composer dev
```

`composer dev` serves the app, a queue worker and the Vite server (workers, scheduler and Reverb details in `docs/SETUP.md`). Admin: `/admin/login`.
Apps: `cd customer_app && flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1 --dart-define=FLAVOR=dev` (same for `driver_app`).

## Verify everything

```bash
scripts/verify-all.sh      # or: make verify-all   (summary in verify-results/SUMMARY.md)
```

## Documentation

| Topic | File |
| --- | --- |
| Release report, readiness and credentials checklists, limitations | `docs/RELEASE_REPORT.md` |
| Milestone status | `docs/IMPLEMENTATION_STATUS.md` |
| Setup | `docs/SETUP.md` |
| Deployment (nginx, supervisor, env, deploy, mobile builds) | `docs/DEPLOYMENT.md` |
| Operations (workers, backups, runbooks, incidents) | `docs/OPERATIONS.md` |
| Integrations and environment variables | `docs/INTEGRATIONS.md` |
| Architecture, decisions, API | `docs/ARCHITECTURE.md`, `docs/DECISIONS.md`, `docs/API.md`, `docs/ROUTE_INVENTORY.md` |
| Security, performance, localization and accessibility reviews | `docs/SECURITY_REVIEW.md`, `docs/PERFORMANCE.md`, `docs/L10N_A11Y_REVIEW.md` |
| Tests still to write | `MISSED_TESTS.md` |
| Contributing | `docs/CONTRIBUTING.md` |
| CI | `.github/workflows/` |
