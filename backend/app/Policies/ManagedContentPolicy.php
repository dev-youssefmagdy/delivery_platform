<?php

namespace App\Policies;

use App\Enums\Admin\AdminPermission;
use App\Enums\Content\ContentWorkflowStatus;
use App\Enums\Content\WorkflowAction;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;

/**
 * Authorization of the workflow content (pages, articles, FAQs, banners, menus), one policy for all five:
 *  - content.manage writes drafts and submits them for review;
 *  - content.publish reviews, publishes, schedules, reverts, archives and restores (and can read everything);
 *  - only drafts are editable; only never-published drafts are deletable.
 */
class ManagedContentPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ContentManage->value) || $admin->can(AdminPermission::ContentPublish->value);
    }

    public function view(Authorizable $admin, Model $content): bool
    {
        return $this->viewAny($admin);
    }

    public function create(Authorizable $admin): bool
    {
        return $admin->can(AdminPermission::ContentManage->value);
    }

    public function update(Authorizable $admin, Model $content): bool
    {
        return $admin->can(AdminPermission::ContentManage->value) && $this->status($content)?->isEditable() === true;
    }

    public function delete(Authorizable $admin, Model $content): bool
    {
        return $admin->can(AdminPermission::ContentManage->value)
            && $this->status($content) === ContentWorkflowStatus::Draft
            && $content->getAttribute('published_at') === null;
    }

    /**
     * Whether the admin may run the workflow action now (permission and a valid starting status).
     */
    public function transition(Authorizable $admin, Model $content, WorkflowAction $action): bool
    {
        $status = $this->status($content);

        return $admin->can($action->permission()->value) && $status !== null && in_array($status, $action->from(), true);
    }

    private function status(Model $content): ?ContentWorkflowStatus
    {
        $status = $content->getAttribute('status');

        return $status instanceof ContentWorkflowStatus ? $status : null;
    }
}
