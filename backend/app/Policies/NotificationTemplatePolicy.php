<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Models\NotificationTemplate;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Notification templates: one permission (notifications.manage) covers viewing and editing. Templates are never deleted.
 */
class NotificationTemplatePolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function view(Authorizable $admin, NotificationTemplate $template): bool
    {
        return $this->allowed($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $this->allowed($admin);
    }

    public function update(Authorizable $admin, NotificationTemplate $template): bool
    {
        return $this->allowed($admin);
    }

    public function activate(Authorizable $admin, NotificationTemplate $template): bool
    {
        return $this->allowed($admin) && ! $template->active;
    }

    public function deactivate(Authorizable $admin, NotificationTemplate $template): bool
    {
        return $this->allowed($admin) && $template->active;
    }

    private function allowed(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::NotificationsManage->value);
    }
}
