<?php

namespace App\DTOs\Support;

use App\Enums\Admin\AdminPermission;
use App\Enums\Support\TicketGroup;
use App\Models\Admin;
use App\Models\SupportTicket;

/**
 * Which tickets an admin may see (SRS §16.11, "agents only see assigned/authorized tickets"):
 * - `support.view_all`: every ticket;
 * - `support.view`: tickets assigned to them, and unassigned tickets of the support group (the pick-up queue);
 * - a team permission (finance.view, incidents.manage, bookings.manage): tickets escalated to that team's group.
 * The same rule feeds the policy (one ticket) and the repository (listings), so a listing can never show what a page refuses.
 */
final readonly class TicketVisibilityScope
{
    /**
     * @param list<TicketGroup> $groups groups whose tickets the admin sees as a team member
     */
    public function __construct(
        public int $adminId,
        public bool $viewAll,
        public bool $canViewAssignedAndQueue,
        public array $groups,
    ) {}

    public static function for(Admin $admin): self
    {
        $groups = [];

        foreach (TicketGroup::escalationTargets() as $group) {
            if ($admin->can($group->permission()->value)) {
                $groups[] = $group;
            }
        }

        return new self(
            $admin->id,
            $admin->can(AdminPermission::SupportViewAll->value),
            $admin->can(AdminPermission::SupportView->value),
            $groups,
        );
    }

    public function allows(SupportTicket $ticket): bool
    {
        if ($this->viewAll) {
            return true;
        }

        if ($ticket->assigned_admin_id === $this->adminId) {
            return $this->canViewAssignedAndQueue || in_array($ticket->group, $this->groups, true);
        }

        if ($ticket->assigned_admin_id === null && $ticket->group === TicketGroup::Support && $this->canViewAssignedAndQueue) {
            return true;
        }

        return $ticket->group !== TicketGroup::Support && in_array($ticket->group, $this->groups, true);
    }

    public function seesNothing(): bool
    {
        return ! $this->viewAll && ! $this->canViewAssignedAndQueue && $this->groups === [];
    }
}
