<?php

namespace App\Services\Notification;

use App\Enums\Common\AuditAction;
use App\Enums\Notification\DeliveryStatus;
use App\Exceptions\ConflictException;
use App\Jobs\Notification\SendNotificationDelivery;
use App\Models\Admin;
use App\Models\NotificationDelivery;
use App\Repositories\Contracts\Notification\NotificationDeliveryRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Operator actions on the delivery log. A failed delivery can be sent again with a reason (for example after an SMS
 * provider outage); the attempt counter restarts, the old error stays in the audit entry.
 */
final class NotificationDeliveryAdminService
{
    public function __construct(
        private readonly NotificationDeliveryRepositoryInterface $deliveries,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws ConflictException `notification_delivery_not_failed`
     */
    public function retry(NotificationDelivery $delivery, string $reason, ?Admin $actor): NotificationDelivery
    {
        return $this->db->transaction(function () use ($delivery, $reason, $actor): NotificationDelivery {
            $locked = $this->deliveries->lockById($delivery->id);

            if (! $locked instanceof NotificationDelivery) {
                throw (new ModelNotFoundException)->setModel(NotificationDelivery::class);
            }

            if ($locked->status !== DeliveryStatus::Failed) {
                throw ConflictException::make('notification_delivery_not_failed');
            }

            $before = ['status' => $locked->status->value, 'attempts' => $locked->attempts, 'error_code' => $locked->error_code];
            $this->deliveries->update($locked, ['status' => DeliveryStatus::Queued, 'attempts' => 0, 'error_code' => null, 'error_message' => null, 'next_attempt_at' => null]);
            $this->audit->record(AuditAction::Reprocessed, $locked, $actor, $before, ['status' => DeliveryStatus::Queued->value], $reason, ['channel' => $locked->channel->value, 'event' => $locked->event_key]);

            SendNotificationDelivery::dispatch($locked->id)->afterCommit();

            return $locked;
        });
    }
}
