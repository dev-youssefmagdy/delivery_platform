<?php

use App\Models\AdminExport;
use App\Models\IdempotencyKey;
use App\Models\OtpChallenge;
use Illuminate\Support\Facades\Schedule;

/*
| Scheduler (Plan §4.4). Every scheduled task must be idempotent and safe to rerun.
| Run locally with `php artisan schedule:work`; in production a cron calls `schedule:run` every minute.
*/

Schedule::command('system:heartbeat')->everyMinute()->withoutOverlapping();

Schedule::command('model:prune', ['--model' => [IdempotencyKey::class, AdminExport::class, OtpChallenge::class]])->hourly()->withoutOverlapping();
