# Operations runbook

Started in P6.2 (queues, scheduler, retention, data lifecycle). P6.4 extends it with deployment, backups, TLS and the release procedure.
Tuning rationale and open questions are in `docs/PERFORMANCE.md`.

## Processes

| Process | Command | Notes |
| --- | --- | --- |
| Web / API | php-fpm or Octane behind the reverse proxy | `TRUSTED_PROXIES` set; `APP_DEBUG=false` |
| Scheduler | cron `* * * * * php artisan schedule:run` on ONE node | tasks use `withoutOverlapping`; with several nodes use `schedule:run --isolated` or a single scheduler node |
| Queue: critical | `php artisan queue:work redis --queue=critical --sleep=1 --tries=3 --timeout=60 --max-time=3600` | 2+ processes; dispatch waves, offer and search expiry |
| Queue: general | `php artisan queue:work redis --queue=default,notifications --sleep=1 --timeout=60 --max-time=3600` | 2+ processes |
| Queue: long | `php artisan queue:work redis_long --queue=notifications,low --timeout=900 --max-time=3600` | 1 process; requires `QUEUE_LONG_CONNECTION=redis_long` |
| Realtime | `php artisan reverb:start --host=0.0.0.0 --port=8080` | scale-out: `REVERB_SCALING_ENABLED=true` (Redis); see PERFORMANCE.md section 5 |

Supervisor example (one program per row above; `numprocs` as in the table):

```ini
[program:dp-queue-critical]
command=php /var/www/delivery_platform/backend/artisan queue:work redis --queue=critical --sleep=1 --tries=3 --timeout=60 --max-time=3600
numprocs=2
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
stopasgroup=true
stopwaitsecs=70
user=www-data
```

`stopwaitsecs` must exceed the worker `--timeout` (use 960 for the long worker). After every deploy run `php artisan queue:restart`.

## Scheduled data lifecycle

See the retention table in `docs/PERFORMANCE.md` section 4. Checking and running by hand:

```bash
php artisan schedule:list
php artisan retention:run --dry-run          # counts only; changes nothing
php artisan retention:run --only=webhook_payloads
php artisan locations:prune
php artisan chat:purge-expired
```

Retention settings are environment variables (`config/retention.php`, `LOCATION_RETENTION_DAYS`, `LOCATION_BOOKING_RETENTION_DAYS`, `CHAT_RETENTION_DAYS`,
`NOTIFICATIONS_RETENTION_DAYS`, `SAFETY_RETENTION_DAYS`, `IDEMPOTENCY_TTL_HOURS`). Shortening a period deletes data at the next run and cannot be undone: dry-run first.
Legal hold on a safety incident, or an open support ticket on a booking, protects that booking's location samples, chat, share links and webhook payloads from these jobs. No job deletes
ledger, journal, payment, refund, payout, booking or audit rows.

## Queue health and alerts

* Final job failures log `CRITICAL queue.job_failed` (no payload). A backed-up queue logs `CRITICAL queue.backed_up` (`queue:monitor`, every minute, 200 jobs per queue).
  Alert on both in the log pipeline (P6.4 wires the pager).
* Inspect: `php artisan queue:failed`, `queue:retry <uuid>` (safe: handlers are idempotent), `queue:forget <uuid>`. Failed rows are pruned after 30 days.
* A growing `critical` queue means offers and searches expire late: add critical workers first, then look at slow jobs.
* The delivery log (admin, Notifications) shows per-channel retries for notifications; those do not use the queue's retry.

## Performance baseline

No measurements exist yet; how to take them is in `docs/PERFORMANCE.md` section 0 and `loadtest/README.md`.

---

# P6.4 additions: backups, monitoring, runbooks, incidents

Everything below was written, **not executed**: no backup was taken and no restore was rehearsed. Deployment: `docs/DEPLOYMENT.md`.

## Backups

| What | How | Frequency | Retention (proposal, confirm with legal and finance) |
| --- | --- | --- | --- |
| MySQL | managed-service automated backups with point-in-time recovery **plus** an encrypted logical dump copied off-site | PITR continuous; dump daily 02:00 | 35 days daily, 12 monthly; financial rows are never deleted by the app, so the backup is the only way back from operator error |
| Private object storage | bucket versioning + cross-region or second-provider replication (or `rclone sync` with versioning) | continuous / daily | versions 90 days |
| `.env` and Firebase service-account JSON | secret manager (or encrypted vault); never in the database dump | on change | latest + 2 |
| Redis | not a source of truth (queue, cache, sessions, locks). Losing it drops queued jobs and signs users out; delayed dispatch/expiry jobs are re-found by the 5 s schedulers | AOF on | none |

Dump script sketch (`/usr/local/bin/dp-backup-db.sh`, cron daily; credentials from `~/.my.cnf` mode 0600, recipient key `backup@...` imported with `age` or `gpg`):

```bash
#!/usr/bin/env bash
set -euo pipefail
STAMP=$(date -u +%Y%m%dT%H%M%SZ); DEST=/var/backups/delivery-platform; mkdir -p "$DEST"
mysqldump --single-transaction --quick --routines --events --hex-blob --set-gtid-purged=OFF delivery_platform \
  | gzip -9 | age -r "$AGE_RECIPIENT" > "$DEST/db-$STAMP.sql.gz.age"
sha256sum "$DEST/db-$STAMP.sql.gz.age" > "$DEST/db-$STAMP.sha256"
aws s3 cp "$DEST/db-$STAMP.sql.gz.age" "s3://$BACKUP_BUCKET/db/" --sse AES256   # separate bucket and credentials from the app bucket
find "$DEST" -type f -mtime +7 -delete                                           # local copies only; remote retention by lifecycle rule
curl -fsS -X POST "$HEARTBEAT_URL" >/dev/null                                    # dead-man's-switch: alert when the backup did NOT run
```

The private key to decrypt lives offline with two named people. A backup nobody can decrypt is no backup.

### Restore procedure (NOT yet test-restored)

Rehearse on a scratch server **before go-live and quarterly**, then record date, duration and row counts here.

1. Declare the incident, put the API into maintenance (`php artisan down`) or block it at the proxy; stop workers and the scheduler (`supervisorctl stop dp-queue-critical:* dp-queue-general:* dp-queue-long:* dp-reverb`, remove the cron line).
2. Provision an empty MySQL 8 schema (`CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`) and user. Never restore over the live schema until the copy is verified.
3. Fetch the dump and its `.sha256`; `sha256sum -c`.
4. `age -d -i key.txt db-STAMP.sql.gz.age | gunzip | mysql delivery_platform_restore`.
5. Point a staging `.env` at the restored schema; run `php artisan migrate --pretend` (must show nothing pending for the dumped version; if the dump is older than the code, deploy the matching release first).
6. Verify: row counts of `users`, `bookings`, `journal_entries`, `payments` against the dump metadata; `php artisan ledger:reconcile` reports no drift; the admin can sign in; open one booking and one provider document (storage restore below).
7. Storage: restore the needed object versions into the private bucket (`aws s3api list-object-versions`, copy the wanted version back) or point `AWS_BUCKET` at the replica.
8. Switch production to the restored schema (or copy the data into the production schema), `php artisan config:cache`, `queue:restart`, start workers and scheduler, `php artisan up`.
9. Money check: run `ledger:reconcile`, `payments:reconcile` (see schedule list), compare the gateway settlement report with `payments` for the gap between the backup time and the incident; replay missed gateway webhooks from the gateway dashboard (handlers are idempotent).
10. Write the post-incident review; record this restore (date, RPO achieved, RTO achieved) in the table below.

| Date | Dump | Restored by | Duration | Result |
| --- | --- | --- | --- | --- |
| (none: never rehearsed) | | | | |

Targets until measured: RPO 24 h (dump) or minutes (PITR), RTO 4 h.

## Monitoring and alerts

* **Log pipeline**: `LOG_STACK=json,slack` with `LOG_SLACK_WEBHOOK_URL` sends CRITICAL and above (`LOG_SLACK_LEVEL`, default `critical`) to a Slack-compatible channel; or ship `laravel.json.log` / stderr to your aggregator and alert on `level_name=CRITICAL`. The route was not tried against a real webhook.
  Alert on the message keys `queue.job_failed`, `queue.backed_up`, ledger or payment reconciliation drift, and any CRITICAL.
* **Sentry**: set `SENTRY_DSN`; alert on new issues in production and on error-rate regressions per release (`SENTRY_RELEASE`). Check "Error tracking" on Admin > Integration health.
* **Health**: `GET /api/v1/health` for the load balancer and an external uptime check (also on the website `/` and the WebSocket host). Admin > Integration health shows the scheduler heartbeat, queue state, drivers and storage; an old heartbeat (`HEALTH_HEARTBEAT_MAX_AGE`) means cron stopped.
* **Infrastructure**: disk (logs, MySQL), MySQL connections and replication lag, slow-query log, Redis memory and evictions, php-fpm saturation, Reverb connections and file descriptors, certificate expiry (< 14 days), backup dead-man's switch, S3 error rate.
* **Business signals** (Admin > Operations dashboard): bookings stuck in `searching` with no providers, offers expiring unanswered, payment failures and webhook rejects, pending payouts, cash debt, SOS cases not acknowledged.
* Thresholds and paging rotation are not decided: set them from the first weeks of real traffic.

## Failed-job runbook

1. Alert `queue.job_failed` (class, queue, attempts, exception class; no payload). Open Admin > Failed jobs or `php artisan queue:failed`.
2. Classify: transient (timeout, gateway 5xx, deploy during run) vs permanent (bug, bad data).
3. Transient: `php artisan queue:retry <uuid>` (handlers are idempotent) or retry from the admin screen with a reason. Permanent: fix, deploy, then retry; or `queue:forget <uuid>` after recording why.
4. `critical` queue growing: add workers on `critical` first, check for slow jobs, Redis latency and a dead worker (`supervisorctl status`). Late offer/search expiry is the symptom customers see.
5. A job class that fails repeatedly after a deploy: roll back the symlink (`docs/DEPLOYMENT.md` section 8) and `queue:restart`.
6. Long jobs (exports, metrics, push campaigns) must run on `redis_long`; failures with "has been attempted too many times" mean `QUEUE_LONG_CONNECTION` is unset in production.

## Payment webhook failure runbook

Endpoint: `POST /api/v1/payment-gateways/{gateway}/webhook` (only the configured gateway answers; others 404). Admin: Payments > Webhook events.

* **Rejected (bad signature / HMAC)**: spike = wrong `PAYMOB_HMAC_SECRET` after a rotation, a proxy altering the body, or probing. Compare the secret with the Paymob dashboard; confirm the proxy forwards the raw body; rejects are rate limited per IP and pruned by retention.
* **Accepted but not processed / failed**: open the event, read the outcome, fix the cause, use the admin reprocess (replays the stored payload; payload is kept for unprocessed events).
* **Gateway says paid, booking still unpaid**: the scheduled `payments:reconcile` queries the gateway for payments inside `PAYMENT_RECONCILE_WINDOW_HOURS`; run it by hand (`php artisan payments:reconcile`), or `POST /payments/{id}/refresh` through the support tools. Never mark a payment paid by editing rows.
* **Gateway outage**: card and wallet payments fail with a clear error; cash keeps working. Consider disabling the gateway in Admin > Settings or feature flags until recovery, tell support, reconcile afterwards.
* **Duplicate callbacks** are expected; the unique event id makes them no-ops.
* After any incident run `ledger:reconcile` and compare the gateway settlement report with the payments table.

## Incident response

1. **Detect and declare**: whoever sees it opens an incident channel, names an incident lead, and writes a one-line impact statement and start time.
2. **Severity**: SEV1 = money wrong (double charge, ledger drift, payouts), data exposure, platform down, SOS not reaching staff; SEV2 = one service or integration degraded (SMS, maps, push, payments); SEV3 = cosmetic or single-user.
3. **Contain first**: roll back the release, disable a feature flag or gateway, block an IP or admin account, revoke tokens (suspend the user; sessions end on the next request), rotate leaked credentials. Preserve evidence: do not delete logs, rows or files.
4. **Communicate**: status to support and operations; customer/provider notification through broadcast tools only after facts are confirmed. Personal-data breaches have legal notification deadlines: involve the data-protection owner at once.
5. **Recover and verify**: restore service, run `ledger:reconcile`, check the failed-job list, confirm health page green, watch the CRITICAL channel for an hour.
6. **Review within 5 working days**: timeline, root cause, what detected it, what was missing, actions with owners. Record it; add the regression test to `MISSED_TESTS.md` until the test suite is back in use.

Admin audit log (Admin > Audit log) and login activity are the first stop for "who changed this"; ledger entries are immutable and corrected only by reversal.

## Release checklist (each deploy)

Backup taken or PITR confirmed; migration reviewed for backward compatibility; `admin:sync-role-permissions`; `queue:restart`; health page green; CRITICAL channel quiet for 30 minutes; previous release kept for rollback.
