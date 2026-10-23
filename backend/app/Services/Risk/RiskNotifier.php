<?php

namespace App\Services\Risk;

use App\DTOs\Notification\NotificationMessage;
use App\Enums\Admin\AdminPermission;
use App\Enums\Notification\NotificationEvent;
use App\Enums\Risk\RiskSignalType;
use App\Models\Admin;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Services\Notification\NotificationService;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Throwable;

/**
 * Tells the staff who can work the risk queue that new signals wait (the review task). In-app admin notification through the
 * notification platform; a failure is reported and never stops the evaluation.
 */
final class RiskNotifier
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param string $discriminator makes the notice once per occurrence (a created signal uuid, or an escalated signal uuid)
     */
    public function signalsRaised(RiskSignalType $type, int $count, string $discriminator, ?string $linkId = null): int
    {
        $sent = 0;

        try {
            try {
                $officers = $this->admins->activeWithPermission(AdminPermission::RiskManage->value);
            } catch (PermissionDoesNotExist) {
                return 0;
            }

            foreach ($officers as $admin) {
                /** @var Admin $admin */
                if (! $admin->can(AdminPermission::RiskManage->value)) {
                    continue;
                }

                $result = $this->notifications->send(NotificationMessage::toAdmin(
                    NotificationEvent::RiskSignalRaised,
                    $admin,
                    ['type' => $type->label(), 'count' => $count],
                    $linkId,
                    mb_substr('risk.raised:'.$discriminator.':'.$admin->id, 0, 120),
                ));
                $sent += $result === null ? 0 : 1;
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $sent;
    }
}
