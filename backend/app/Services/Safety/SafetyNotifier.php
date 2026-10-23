<?php

namespace App\Services\Safety;

use App\DTOs\Notification\NotificationMessage;
use App\Enums\Admin\AdminPermission;
use App\Enums\Notification\NotificationEvent;
use App\Models\Admin;
use App\Models\SafetyIncident;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Services\Notification\NotificationService;
use App\Services\Notification\PartyNotifier;
use App\Services\Operations\OperationsFeedPublisher;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Throwable;

/**
 * Tells the safety team (admin notification + the live operations feed) and the reporting person (SafetyCaseUpdate through
 * PartyNotifier, P3.6). Failures are reported and never stop the safety action.
 */
final class SafetyNotifier
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly NotificationService $notifications,
        private readonly PartyNotifier $party,
        private readonly OperationsFeedPublisher $feed,
    ) {}

    /**
     * @return int how many officers got a notification (0 means the case waits in the queue only)
     */
    public function teamRaised(SafetyIncident $incident): int
    {
        $count = 0;

        try {
            $officers = $this->admins->activeWithPermission(AdminPermission::IncidentsManage->value);
        } catch (PermissionDoesNotExist) {
            $officers = collect();
        }

        foreach ($officers as $admin) {
            /** @var Admin $admin */
            if (! $admin->can(AdminPermission::IncidentsManage->value)) {
                continue;
            }

            $sent = $this->notifications->send(NotificationMessage::toAdmin(
                NotificationEvent::SafetyIncidentRaised,
                $admin,
                ['code' => (string) $incident->number],
                $incident->uuid,
                mb_substr('safety.raised:'.$incident->uuid.':'.$admin->id, 0, 120),
            ));
            $count += $sent === null ? 0 : 1;
        }

        $this->feedChanged($incident);

        return $count;
    }

    public function feedChanged(SafetyIncident $incident): void
    {
        try {
            $this->feed->safetyIncident($incident->uuid, $incident->priority->value, $incident->status->value);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * An update on the case for the person who raised it; `$discriminator` makes the notice once per occurrence.
     */
    public function userUpdated(SafetyIncident $incident, string $discriminator): void
    {
        try {
            $incident->loadMissing('reporter');
            $this->party->user($incident->reporter, $incident->reporter_type, NotificationEvent::SafetyCaseUpdate, $incident->uuid.':'.$discriminator, [], $incident->uuid);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
