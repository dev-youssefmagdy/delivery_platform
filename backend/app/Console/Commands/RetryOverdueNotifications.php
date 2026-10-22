<?php

namespace App\Console\Commands;

use App\Jobs\Notification\SendNotificationDelivery;
use App\Repositories\Contracts\Notification\NotificationDeliveryRepositoryInterface;
use Illuminate\Console\Command;

/**
 * Safety net for lost queue jobs: a delivery still queued (or stuck in `sending`) long after it was due is queued
 * again. Idempotent: `DeliveryProcessor` ignores deliveries that are finished or claimed by another worker.
 */
class RetryOverdueNotifications extends Command
{
    protected $signature = 'notifications:retry-overdue {--limit=200}';

    protected $description = 'Queue deliveries that are overdue (lost jobs) again';

    public function handle(NotificationDeliveryRepositoryInterface $deliveries): int
    {
        $before = now()->subMinutes(max(1, (int) config('notifications.overdue_after_minutes', 5)));
        $overdue = $deliveries->overdue($before, max(1, (int) $this->option('limit')));

        foreach ($overdue as $delivery) {
            SendNotificationDelivery::dispatch($delivery->id);
        }

        $this->info($overdue->count().' overdue deliveries queued.');

        return self::SUCCESS;
    }
}
