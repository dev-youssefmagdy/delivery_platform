<?php

namespace App\Services\Lead;

use App\DTOs\Notification\NotificationMessage;
use App\Enums\Admin\AdminPermission;
use App\Enums\Notification\NotificationEvent;
use App\Models\Admin;
use App\Models\Lead;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Services\Notification\NotificationService;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Throwable;

/**
 * Tells the staff who work leads that a new onboarding lead arrived (in-app admin notification). A failure is reported and
 * never fails the visitor's submission.
 */
final class LeadNotifier
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly NotificationService $notifications,
    ) {}

    public function received(Lead $lead): int
    {
        $sent = 0;

        try {
            try {
                $staff = $this->admins->activeWithPermission(AdminPermission::LeadsManage->value);
            } catch (PermissionDoesNotExist) {
                return 0;
            }

            foreach ($staff as $admin) {
                /** @var Admin $admin */
                if (! $admin->can(AdminPermission::LeadsManage->value)) {
                    continue;
                }

                $result = $this->notifications->send(NotificationMessage::toAdmin(
                    NotificationEvent::LeadReceived,
                    $admin,
                    ['reference' => (string) $lead->reference],
                    $lead->uuid,
                    mb_substr('lead.received:'.$lead->uuid.':'.$admin->id, 0, 120),
                ));
                $sent += $result === null ? 0 : 1;
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $sent;
    }
}
