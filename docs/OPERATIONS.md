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
