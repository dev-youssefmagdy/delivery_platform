<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Notification\DeliveryStatus;
use App\Models\NotificationDelivery;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * The delivery log (notifications.manage) is read-only apart from sending a failed delivery again.
 */
class NotificationDeliveryPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::NotificationsManage->value);
    }

    public function view(Authorizable $admin, NotificationDelivery $delivery): bool
    {
        return $admin->can(AdminPermission::NotificationsManage->value);
    }

    public function retry(Authorizable $admin, NotificationDelivery $delivery): bool
    {
        return $admin->can(AdminPermission::NotificationsManage->value) && $delivery->status === DeliveryStatus::Failed;
    }
}
