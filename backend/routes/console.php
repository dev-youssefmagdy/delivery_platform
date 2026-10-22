<?php

use App\Jobs\Customers\ReactivateExpiredSuspensions;
use App\Jobs\Provider\ReactivateExpiredProviderSuspensions;
use App\Jobs\Provider\SweepStaleProviders;
use App\Models\AdminExport;
use App\Models\AppNotification;
use App\Models\IdempotencyKey;
use App\Models\OtpChallenge;
use Illuminate\Support\Facades\Schedule;

/*
| Scheduler (Plan §4.4). Every scheduled task must be idempotent and safe to rerun.
| Run locally with `php artisan schedule:work`; in production a cron calls `schedule:run` every minute.
*/

Schedule::command('system:heartbeat')->everyMinute()->withoutOverlapping();

Schedule::job(new ReactivateExpiredSuspensions)->name('customers:reactivate-expired')->everyMinute()->withoutOverlapping();

Schedule::job(new ReactivateExpiredProviderSuspensions)->name('providers:reactivate-expired')->everyMinute()->withoutOverlapping();

Schedule::command('documents:check-expiry')->dailyAt('02:00')->withoutOverlapping();

Schedule::command('model:prune', ['--model' => [IdempotencyKey::class, AdminExport::class, OtpChallenge::class]])->hourly()->withoutOverlapping();

Schedule::job(new SweepStaleProviders)->name('providers:sweep-stale')->everyThirtySeconds()->withoutOverlapping();

Schedule::command('bookings:expire-searches')->everyFiveSeconds()->withoutOverlapping();

Schedule::command('offers:expire')->everyFiveSeconds()->withoutOverlapping();

Schedule::command('locations:prune')->dailyAt('03:30')->withoutOverlapping();

Schedule::command('ledger:reconcile')->dailyAt('04:00')->withoutOverlapping();

Schedule::command('payments:reconcile --sweep-only')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('payments:reconcile')->hourlyAt(30)->withoutOverlapping();

Schedule::command('coupons:sync-status')->everyMinute()->withoutOverlapping();

Schedule::command('bookings:expire-adjustments')->everyMinute()->withoutOverlapping();

Schedule::command('notifications:retry-overdue')->everyMinute()->withoutOverlapping();

Schedule::command('push-campaigns:dispatch-due')->everyMinute()->withoutOverlapping();

Schedule::command('model:prune', ['--model' => [AppNotification::class]])->dailyAt('05:00')->withoutOverlapping();

Schedule::command('chat:purge-expired')->dailyAt('03:45')->withoutOverlapping();

Schedule::command('support:maintain')->everyFiveMinutes()->withoutOverlapping();
