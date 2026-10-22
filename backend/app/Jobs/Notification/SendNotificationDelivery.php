<?php

namespace App\Jobs\Notification;

use App\Enums\Common\QueueName;
use App\Services\Notification\DeliveryProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * One attempt to deliver one notification on one channel. Retries are scheduled by `DeliveryProcessor` (its own
 * back-off and attempt counter, visible in the delivery log), so the queue itself never retries this job.
 */
class SendNotificationDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public readonly int $deliveryId)
    {
        $this->onQueue(QueueName::Notifications->value);
    }

    public function handle(DeliveryProcessor $processor): void
    {
        $processor->process($this->deliveryId);
    }
}
