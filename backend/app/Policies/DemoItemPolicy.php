<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Demo\DemoItemStatus;
use App\Models\DemoItem;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Object-level authorization for DemoItem (permissions: demo_items.*).
 */
class DemoItemPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::DemoItemsView->value);
    }

    public function view(Authorizable $admin, DemoItem $demoItem): bool
    {
        return $admin->can(AdminPermission::DemoItemsView->value);
    }

    public function create(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::DemoItemsManage->value);
    }

    public function update(Authorizable $admin, DemoItem $demoItem): bool
    {
        return $admin->can(AdminPermission::DemoItemsManage->value) && $demoItem->status !== DemoItemStatus::Archived;
    }

    public function archive(Authorizable $admin, DemoItem $demoItem): bool
    {
        return $admin->can(AdminPermission::DemoItemsManage->value) && $demoItem->status !== DemoItemStatus::Archived;
    }

    public function delete(Authorizable $admin, DemoItem $demoItem): bool
    {
        return $admin->can(AdminPermission::DemoItemsManage->value);
    }

    public function export(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::DemoItemsExport->value);
    }
}
