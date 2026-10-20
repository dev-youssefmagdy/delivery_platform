<?php

use App\Jobs\Customers\ReactivateExpiredSuspensions;
use App\Jobs\Provider\ReactivateExpiredProviderSuspensions;
use App\Models\AdminExport;
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
