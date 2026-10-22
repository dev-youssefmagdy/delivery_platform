<?php

namespace App\Policies;

use App\DTOs\Support\TicketVisibilityScope;
use App\Enums\Admin\AdminPermission;
use App\Models\Admin;
use App\Models\SupportTicket;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Support tickets (support.view / support.manage / support.view_all). Agents see only tickets assigned to them, the unassigned
 * support queue and, as members of finance / safety / operations, the tickets escalated to their team (TicketVisibilityScope,
 * shared with the inbox query). Working a ticket needs `support.manage` on top of being able to see it; actions also check the
 * ticket's state, so the server refuses what the UI hides. Linking finance operations additionally needs `payments.view`.
 */
class SupportTicketPolicy
{
    public function viewAny(Authorizable $admin): bool
    {
        return $admin instanceof Admin && ! TicketVisibilityScope::for($admin)->seesNothing();
    }

    public function view(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $admin instanceof Admin && TicketVisibilityScope::for($admin)->allows($ticket);
    }

    public function reply(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $this->work($admin, $ticket) && $ticket->status->value !== 'closed';
    }

    public function assign(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $this->work($admin, $ticket) && $ticket->status->isActive();
    }

    public function claim(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $this->assign($admin, $ticket) && $ticket->assigned_admin_id !== ($admin instanceof Admin ? $admin->id : null);
    }

    public function escalate(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $this->work($admin, $ticket) && $ticket->status->isActive();
    }

    public function resolve(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $this->work($admin, $ticket) && $ticket->status->isActive();
    }

    public function reopen(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $this->work($admin, $ticket) && $ticket->status->isFinished();
    }

    public function changePriority(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $this->work($admin, $ticket) && $ticket->status->isActive();
    }

    public function linkFinance(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $this->work($admin, $ticket) && $admin->can(AdminPermission::PaymentsView->value);
    }

    public function viewChat(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $this->work($admin, $ticket) && $ticket->booking_id !== null;
    }

    private function work(Authorizable $admin, SupportTicket $ticket): bool
    {
        return $admin instanceof Admin && $admin->can(AdminPermission::SupportManage->value) && $this->view($admin, $ticket);
    }
}
