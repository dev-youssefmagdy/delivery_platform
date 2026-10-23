# Release report

Date: 2026-11-20 (P6.4). Repository: `main`, never pushed by this work.

## 0. Read this first

**The quality gate has NOT been run since P2.3.** By the project owner's instruction, no test suite, pint, phpstan, `php artisan test`, `flutter analyze|test|build`, vitest, browser smoke or `scripts/check.sh`
was run for milestones P2.4 to P6.4. **Every milestone from P2.4 to P6.4 is marked 🟡, not ✅** (P2.9 is not built at all). Nothing in this report should be read as "tested".
The code was written by reading and by cheap probes (`php -l`, in-process requests, route analysis, secret sweeps). It may contain compile errors, type errors, failing or stale tests (one is known:
`CustomerDeletionAndExportTest`), broken Flutter builds and regressions in P0 to P2.3, which were last verified before P2.4 was started.

The release is therefore **not production-ready until the commands in section 3 pass and the 🟡 items in section 7 are cleared.**

## 1. What P6.4 added

| File | Purpose |
| --- | --- |
| `.github/workflows/backend.yml` | composer install, pint --test, phpstan, `php artisan test --parallel` on MySQL 8 + Redis 7 services, npm ci/build, vitest, contrast scanner |
| `.github/workflows/flutter.yml` | format, analyze, test for `platform_core`, `customer_app`, `driver_app`; debug APK for both apps (artifacts) |
| `.github/workflows/scanners.yml` | translation parity, ARB parity, route authentication/authorization inventory, gitleaks |
| `scripts/verify-all.sh`, `make verify-all` | the same checks locally, never stops at the first failure, writes `verify-results/SUMMARY.md` and one log per step |
| `docs/DEPLOYMENT.md` | server, nginx + TLS, admin subdomain option, supervisor, cron, S3, env per environment, deploy and rollback, mobile flavors, Firebase/APNs, signing, GitLab and macOS CI sketches |
| `docs/OPERATIONS.md` (extended) | backups, restore steps (**never test-restored**), monitoring, failed-job and webhook runbooks, incident response |
| `backend/.env.production.example` | production template with every variable that matters |
| `README.md` | entry point |
| `backend/config/logging.php`, `.env.example` | the Slack (pager) log channel level now has its own variable `LOG_SLACK_LEVEL` (default `critical`); it previously read `LOG_LEVEL`, which would have sent every line |

None of the workflow files has run on GitHub; YAML syntax was checked locally. `verify-all.sh` passed `bash -n` only.

## 2. Milestone status

Legend: ✅ gate passed when the milestone closed (last verified at P2.3) · 🟡 built, gate not run · ❌ not built.
The P5.1 row in `IMPLEMENTATION_STATUS.md` still said ❌ although the milestone exists (ADR-167..172); it was corrected to 🟡 in this commit.

| ID | Milestone | Status | Meaning |
| --- | --- | --- | --- |
| P0.1 | Repository audit, git, documentation spine, tooling baseline | ✅ | gate was run and passed when closed |
| P0.2 | Backend core architecture | ✅ | gate was run and passed when closed |
| P0.3 | Admin UI kit, AJAX/Precognition forms, DataTables, filters card, generator | ✅ | gate was run and passed when closed |
| P0.4 | Flutter foundation + shared package | ✅ | gate was run and passed when closed |
| P1.1 | Admin authentication, roles/permissions, admin users, audit log UI | ✅ | gate was run and passed when closed |
| P1.2 | Settings, markets, locales, policy versions, app config API | ✅ | gate was run and passed when closed |
| P1.3 | Identity API: OTP, users, profiles, devices, consents, deletion | ✅ | gate was run and passed when closed |
| P1.4 | Service catalog, vehicle categories, form schemas, versioned snapshots | ✅ | gate was run and passed when closed |
| P1.5 | Geography: cities, service zones, availability, maps adapter | ✅ | gate was run and passed when closed |
| P1.6 | Customers admin module | ✅ | gate was run and passed when closed |
| P2.1 | Provider onboarding, vehicles, documents, verification | ✅ | gate was run and passed when closed |
| P2.2 | Availability, live location, eligibility, dispatch engine | ✅ | gate was run and passed when closed |
| P2.3 | Reference pricing & booking creation | ✅ | gate was run and passed when closed |
| P2.4 | Offers, counter-offers & atomic selection | 🟡 | built; gate NOT run |
| P2.5 | Trip lifecycle: heading, arrived, PIN, start, complete, cancellation v1, ratings, share link | 🟡 | built; gate NOT run |
| P2.6 | Customer app: complete ride flow | 🟡 | built; gate NOT run |
| P2.7 | Driver app: complete ride flow | 🟡 | built; gate NOT run |
| P2.8 | Admin: bookings, offers monitor, live operations v1 | 🟡 | built; gate NOT run |
| P2.9 | E2E ride test, race tests, MVP hardening | ❌ | not built (the `qa:race-*` commands from P2.4 exist; no end-to-end test) |
| P3.1 | Double-entry ledger, wallets, commission rules, price breakdown | 🟡 | built; gate NOT run |
| P3.2 | Payments: gateway adapter, cash flow, webhooks, tips | 🟡 | built; gate NOT run |
| P3.3 | Coupon engine and coupon manager | 🟡 | built; gate NOT run |
| P3.4 | Provider earnings, payouts, adjustments, cash reconciliation | 🟡 | built; gate NOT run |
| P3.5 | Cancellation/no-show policies with fees, fare adjustments, receipts | 🟡 | built; gate NOT run |
| P3.6 | Notifications platform (push, SMS, email, in-app inbox) | 🟡 | built; gate NOT run |
| P3.7 | Booking chat and support tickets/disputes | 🟡 | built; gate NOT run |
| P3.8 | Flutter: wallet, payments, coupons, earnings, payouts | 🟡 | built; gate NOT run |
| P4.1 | Courier/parcel backend | 🟡 | built; gate NOT run |
| P4.2 | Furniture moving & freight backend (scoped offers) | 🟡 | built; gate NOT run |
| P4.3 | Flutter courier/moving/freight flows | 🟡 | built; gate NOT run |
| P4.4 | Admin & E2E for non-ride services | 🟡 | built; gate NOT run |
| P5.1 | Safety center, SOS, trusted contacts | 🟡 | built; gate NOT run |
| P5.2 | Reviews moderation & risk signals | 🟡 | built; gate NOT run |
| P5.3 | Operations dashboard, reports, exports | 🟡 | built; gate NOT run |
| P5.4 | CMS | 🟡 | built; gate NOT run |
| P5.5 | Public website | 🟡 | built; gate NOT run |
| P5.6 | Integration health, app versions, feature flags, system pages | 🟡 | built; gate NOT run |
| P5.7 | Referrals, loyalty, provider incentives | 🟡 | built; gate NOT run |
| P6.1 | Security, IDOR, race & webhook review | 🟡 | built; gate NOT run |
| P6.2 | Performance, indexes, retention | 🟡 | built; gate NOT run |
| P6.3 | Localization, RTL & accessibility review | 🟡 | built; gate NOT run |
| P6.4 | CI, deployment, backups, release report | 🟡 | built; gate NOT run |
## 3. Verification commands (NOT RUN) and expected output

One command: `scripts/verify-all.sh` (add `--with-apk`, `--with-browser`, or `--only backend|flutter|scanners`). Expected: every line `PASS`, final `ALL GREEN.`, exit 0, `verify-results/SUMMARY.md` listing 0 not passed.
A missing tool is recorded as `SKIP` and counts as not green. Prerequisites: `docs/SETUP.md` (MySQL testing database and grants, Redis, PHP 8.3, Node 20, Flutter 3.47.1, gitleaks).

| Command | Expected |
| --- | --- |
| `cd backend && composer install && vendor/bin/pint --test` | exit 0, no files listed (expect formatting diffs after 18 milestones of unchecked code: fix with `composer lint:fix`) |
| `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` | `[OK] No errors` (level 6 per ADR-15; expect real findings) |
| `php artisan config:clear && php artisan test --parallel` | all green; no test exists for P2.4 onward (see MISSED_TESTS), so green proves only P0 to P2.3 and regressions there |
| `npm ci && npm run build && npm test` | Vite build succeeds; Vitest green |
| `node scripts/check-contrast.mjs` (from `backend/`) | exit 0 (waivers listed in `docs/L10N_A11Y_REVIEW.md`) |
| `php scripts/check-translations.php` and `php scripts/check-arb-parity.php` | exit 0, no missing keys or parity gaps (written in P6.3 and only run on the dev tree) |
| `cd backend && php artisan route:list --json \| php ../scripts/route-inventory.php` | exit 0: every route authenticated and authorized |
| `composer audit`, `npm audit --omit=dev` | none; known: 2 moderate dev-only advisories in the vitest chain |
| `gitleaks detect --source . --redact` | no leaks (a regex sweep found only a test fixture password; gitleaks itself never ran) |
| in `packages/platform_core`, `customer_app`, `driver_app`: `flutter pub get && dart format --output=none --set-exit-if-changed . && flutter analyze && flutter test` | exit 0 each |
| `flutter build apk --debug` in both apps | APK built (Android licenses accepted; no Firebase files needed) |
| `make browser-smoke` | the admin login form works in headless Chrome |

### What has and has not been verified

Verified (cheap checks only, over the milestones' lifetime): `php -l` on changed PHP; YAML parse of the three workflows; `bash -n` on the scripts; in-process HTTP probes of security headers and several admin pages under a rolled-back transaction (P6.1);
`route:list` analysis; regex secret sweep over tree and history (P6.1); `composer audit`; `npm audit`; `flutter pub outdated`; the P6.4 grep audits in section 8.
**Not verified**: everything else. No PHPUnit, no static analysis, no Dart analysis or test of code from P2.4 onward, no APK or IPA build, no browser or device review, no performance measurement, no restore, no live integration.

## 4. Completed, partially completed, not implemented

**Completed and gate-verified (P0.1 to P2.3):** foundation, admin UI kit, Flutter foundation, admin auth/roles, settings/markets/policies, identity API, catalog, geography, customers admin, provider onboarding, dispatch, pricing and booking creation.
Verified at P2.3; unverified since.

**Built but unverified (🟡, P2.4 to P6.4):** offers and atomic selection, trip lifecycle, both apps' ride flows, admin bookings and live map, ledger, payments, coupons, payouts, fees and receipts, notifications, chat and support,
wallet and money screens, courier / moving / freight, safety and SOS, reviews and risk, dashboards and reports, CMS, public website, integration health and feature flags, referrals / loyalty / incentives, security fixes (P6.1),
indexes / retention / queue tuning (P6.2), localization and accessibility fixes (P6.3), CI and operations documents (P6.4).

**Not implemented:**

| Item | Reason | Next step |
| --- | --- | --- |
| P2.9 end-to-end ride test and MVP race hardening | deferred with all testing by instruction | build after the suite is green; `qa:race-accept` / `qa:race-offer` exist for manual race proof |
| Automated tests for P2.4 to P6.3 | by instruction | `MISSED_TESTS.md`, order in section 9 |
| Tested backup and restore | no server, by instruction | rehearse `docs/OPERATIONS.md` restore procedure on staging |
| iOS build and signing | no macOS here | `docs/DEPLOYMENT.md` section 9 |
| Credit usage restriction at spend time (wallet money is fungible, ADR-198) | design limit | decide product rule |
| Push on referral / loyalty / quest reward | not built | add notification templates |
| Admin CSP without `unsafe-eval` / `unsafe-inline` | needs Alpine CSP build and a browser pass | rewrite 86 inline expressions |
| Arabic font bundled in the apps | licence check | after device review |
| Customer wallet top-up (`platform_wallet` payments) | out of scope | later |
| History endpoint `GET /provider/bookings`, appeals, PDF receipts (print-to-PDF only), admin proof-picture viewer, admin parcel-attempt resolution (partly P4.4) | scoped out | see "Known gaps" in `IMPLEMENTATION_STATUS.md` |

## 5. Known limitations (collected from the docs)

* **No automated verification** of any code from P2.4 onward; stale test known (`CustomerDeletionAndExportTest`).
* **Realtime (Reverb) and location tracking** never tested on devices or with two real clients; apps fall back to API reads after a sequence gap, which is also untested.
* **SMS Misr, Paymob, maps (Google / OSRM + Nominatim), FCM/APNs, ClamAV, S3** adapters were written from vendor documentation and tested, at most, against faked HTTP. Paymob request fields and HMAC field order are assumptions to verify on a Paymob test account (`docs/INTEGRATIONS.md`).
* **CSP was never seen in a browser**; roll out with `CSP_MODE=report-only`. Admin CSP still allows `unsafe-eval` and inline styles.
* **No browser or device review** of any admin screen, website page or app screen in Arabic or English, RTL, 200% text scale, or four screen sizes (`docs/L10N_A11Y_REVIEW.md`). Text-scale wrapping of money rows unobserved.
* **No performance measurements**: index choices, DataTables, retention batch sizes, queue worker counts and Reverb capacity are reasoned, not measured (`docs/PERFORMANCE.md` section 0, `loadtest/README.md`).
* **iOS was never built**; the Xcode projects were edited by hand. Android was built only before P2.4.
* **Credit restriction** is not enforced when money is spent (ADR-198).
* Idempotency middleware lock is 30 s; a crash between commit and response storage can re-run non-money writes. Ledger replay under MySQL REPEATABLE READ can answer 500 instead of a replay (no double posting).
* SOS bypasses the general API limiter by design; MFA is required by role, not by permission; a few mutating routes lack an Idempotency-Key (listed in `docs/SECURITY_REVIEW.md`).
* Provider document / vehicle photo routes answer 403 rather than 404 for another provider's uuid (existence disclosure only).
* Not every upload path was audited for its storage disk; S3 never exercised (`docs/DEPLOYMENT.md` section 7).
* Egypt document requirements, commission defaults, cancellation policies and fare policies are seeded starting points that need local legal and business review.
* Migrations ran only on the development database; large-table index builds are unmeasured.
* Default seeders create demo data outside production only; verify `db:seed` in a production-like environment does not create demo providers.

## 6. Environment variables and credentials

Template: `backend/.env.production.example`; full reference: `backend/.env.example` and `docs/INTEGRATIONS.md`.

### External credentials checklist

| Item | Needed | Variables / location | Done |
| --- | --- | --- | --- |
| Domain and DNS | api, ws (realtime), admin (optional), website host | `APP_URL`, `REVERB_HOST` | [ ] |
| TLS certificates | all hosts, auto-renew | nginx / certbot | [ ] |
| SMS Misr | username, password, sender id approved for OTP text, live environment | `SMS_DRIVER=smsmisr`, `SMSMISR_*` | [ ] |
| Maps | Google Maps Platform key (Geocoding, Places, Routes) **or** self-hosted Nominatim + OSRM; licensed tile URL for admin and apps | `MAPS_DRIVER`, `MAPS_API_KEY`, `ADMIN_MAP_TILE_URL`, `MAP_TILE_URL` | [ ] |
| Paymob | merchant account, card and wallet integration ids, API / secret / public keys, HMAC secret, notification URL registered, refund permission | `PAYMENT_DRIVER=paymob`, `PAYMOB_*`, `PAYMENT_RETURN_URL` | [ ] |
| Firebase / FCM | project, two apps per platform, service-account JSON outside git, `google-services.json`, `GoogleService-Info.plist` | `PUSH_DRIVER=fcm`, `FIREBASE_*` | [ ] |
| APNs | auth key (.p8), key id, team id uploaded to Firebase | Firebase console | [ ] |
| Apple / Google Play | developer accounts, app records, signing keys | CI secrets | [ ] |
| Mail | SMTP or API provider, verified sending domain (SPF, DKIM, DMARC) | `MAIL_*` | [ ] |
| Object storage | private bucket, access keys, encryption, versioning | `AWS_*`, `*_DISK` | [ ] |
| Backups | off-site bucket with separate credentials, encryption key held offline, dead-man's-switch URL | scripts in OPERATIONS.md | [ ] |
| Sentry | project and DSN | `SENTRY_DSN`, `SENTRY_ENVIRONMENT`, `SENTRY_RELEASE` | [ ] |
| Pager | Slack-compatible webhook for CRITICAL logs | `LOG_STACK=json,slack`, `LOG_SLACK_WEBHOOK_URL` | [ ] |
| ClamAV | clamd reachable from the app | `FILE_SCANNER_DRIVER=clamav`, `CLAMAV_HOST/PORT` | [ ] |
| Realtime keys | generated Reverb app id / key / secret (and `VITE_REVERB_*`, app `REVERB_KEY`) | `REVERB_*` | [ ] |

### Production readiness checklist

- [ ] `scripts/verify-all.sh` ALL GREEN, including `--with-apk`; `MISSED_TESTS.md` executed in the order of section 9
- [ ] P2.9 end-to-end ride and race tests written and green
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, no `fake` / `log` / `sandbox` / `noop` drivers, `OTP_TEST_ENABLED=false`, `ADMIN_DEMO_ENABLED=false`, no demo providers
- [ ] `TRUSTED_PROXIES`, `CORS_ALLOWED_ORIGINS`, `SESSION_SECURE_COOKIE`, `ADMIN_PATH` (and IP allowlist) set; `CSP_MODE=report-only` rolled to `enforce` after a clean browser pass
- [ ] `QUEUE_LONG_CONNECTION=redis_long`; all supervisor units and the single scheduler cron running; Admin > Integration health green
- [ ] `composer install --no-dev`; `admin:sync-role-permissions` in the deploy script
- [ ] Every integration exercised end to end on staging with sandbox credentials, then once live (OTP SMS, a real payment and refund, webhook, push on Android and iOS, maps, ClamAV, S3 upload and signed download)
- [ ] Realtime and location tested on two physical devices on mobile data; apps on Android and iOS release builds
- [ ] Backup running, restore rehearsed and recorded in `docs/OPERATIONS.md`
- [ ] Monitoring, CRITICAL alert route and Sentry confirmed by a deliberate test error
- [ ] Browser and device review in ar and en (RTL, 200% text) done; CSP console clean
- [ ] Load test baseline taken and `docs/PERFORMANCE.md` section 6 filled
- [ ] Legal: terms, privacy, driver terms and restricted-goods policy text reviewed by counsel; data-retention periods approved
- [ ] Security: `docs/SECURITY_REVIEW.md` residual risks accepted or fixed; external penetration test (recommended)

### First-run checklist (new production install)

1. Deploy (`docs/DEPLOYMENT.md` section 8): `migrate --force`, `db:seed --force` (reference data, permissions, roles).
2. `php artisan admin:create` (interactive; role `super_admin`); sign in at the admin path and enrol MFA.
3. Admin > Policies: write and **publish** `terms`, `privacy`, `driver_terms` and `restricted_goods` for each market and locale (apps block sign-up flows that need an unpublished policy).
4. Admin > Settings: currency, markets, commission, cancellation and fare policies, support contacts, emergency numbers; Admin > App versions: the real Google Play and App Store links.
5. `.env`: real `WEBSITE_BRAND_NAME`, `WEBSITE_SUPPORT_EMAIL`, `WEBSITE_SUPPORT_PHONE`, `WEBSITE_SOCIAL_*`, store links; run `php artisan config:cache`.
6. Replace the CMS demo content (pages, FAQs, blog, banners) with real, reviewed text in Arabic and English; check the website in both languages.
7. Admin > Integration health: confirm SMS, maps, payments, push, storage and scanner drivers show configured; send a real OTP to your own phone.
8. Enable gateways (card, wallet) only after a live test payment and refund; keep cash on. Feature flags `referrals`, `loyalty`, `incentives` stay off until campaigns are configured.
9. Grant new permissions to roles in Admin > Roles where needed; invite staff admins.
10. Confirm the scheduler heartbeat, queue workers, backups and alerts, then open to a closed pilot before public launch.

## 7. Deployment steps

Summary of `docs/DEPLOYMENT.md`: provision (PHP 8.3, MySQL 8, Redis 7, nginx + TLS, supervisor, S3, ClamAV) -> `.env` from `backend/.env.production.example` -> build (`composer install --no-dev`, `npm ci && npm run build`) -> `migrate --force` -> `admin:sync-role-permissions` ->
cache config / routes / views -> switch `current` symlink -> reload php-fpm -> `queue:restart` -> health check. Rollback: switch the symlink back and `queue:restart`; restore from backup only for data loss.

## 8. Static audit performed in P6.4 (grep, no execution of tests)

Grep audits over the tree (not tests; they cannot prove absence):
* **Secrets**: no AWS / private-key / Stripe / Slack / GitHub / Google key patterns anywhere outside vendor and build directories; no tracked `.env`, keystore, `key.properties`, `google-services.json`, `GoogleService-Info.plist` or Firebase credential file. Gitleaks itself was not run.
* **Debug bypasses**: no `dd(`, `dump(`, `var_dump(`, `print_r(`, `ray(` in `backend/app|routes|resources|database`; the production env template sets `APP_DEBUG=false`, `OTP_TEST_ENABLED=false`, `ADMIN_DEMO_ENABLED=false`. Dart has only `debugPrint` in `bootstrap.dart` (prints the API base URL in non-release mode; acceptable, no secrets).
* **Placeholder UI**: no TODO / FIXME / lorem in app code. One stale string: `bkSafetyNotice` in both customer ARB files says the SOS feature is "coming soon"; it is **unused** by any widget since P5.1 (dead string, remove it and run `flutter gen-l10n`).
* **Dead routes, unprotected endpoints, untranslated text**: delegated to `scripts/route-inventory.php` and `scripts/check-translations.php` / `check-arb-parity.php`, which were not executed in P6.4 (they are in `verify-all.sh` and CI); the P6.1 and P6.3 runs on the development tree are recorded in their reviews.

## 9. Order in which to run `MISSED_TESTS.md`

`MISSED_TESTS.md` (1287 lines, one section per milestone from P2.4 to P6.3) describes tests that were never written. Start with the whole gate to learn the baseline, then write and run in this order,
fixing production code as failures appear (expect many in the first pass):

1. **Gate baseline**: `scripts/verify-all.sh`; fix pint, phpstan, compile and stale-test failures. Nothing else is meaningful before this.
2. **P6.1 security**: route authorization guards, IDOR matrix, idempotency on money routes, webhook signature and replay, concurrency tests (data exposure and money risk first).
3. **P3.1 to P3.5 money**: ledger postings and balance, payments and webhooks, coupons, payouts and cash reconciliation, fees and receipts.
4. **P2.4, P2.5, P2.8 booking core** (offers and atomic selection, trip lifecycle, admin bookings) plus the P2.9 end-to-end ride test.
5. **P3.6, P3.7, P5.1** notifications, chat and support, safety and SOS (safety-critical behaviour).
6. **P4.1 to P4.4** courier, moving, freight and admin flows.
7. **P2.6, P2.7, P3.8, P4.3** Flutter widget and flow tests, then device runs.
8. **P5.2 to P5.7** reviews and risk, dashboards, CMS, website, system health, growth.
9. **P6.2 performance** (index and retention tests, then real measurements with `loadtest/`) and **P6.3** (RTL goldens, text scale, parity).
10. Browser and device review, CSP report-only soak, backup restore rehearsal, then the production readiness checklist in section 6.

When a section is done, delete its block from `MISSED_TESTS.md`, flip the milestone to ✅ in `IMPLEMENTATION_STATUS.md` only after the gate passes.
