<?php

namespace App\Services\Support;

use App\Enums\Admin\AdminPermission;
use App\Enums\Support\TicketGroup;
use App\Models\Admin;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Assignment rule for new and escalated tickets: the active admin of the owning team with the fewest open tickets (ties by name).
 * Nobody eligible means the ticket stays in the group's queue, unassigned, and shows in the "unassigned" quick filter.
 */
final class TicketAssigner
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly SupportTicketRepositoryInterface $tickets,
    ) {}

    public function pick(TicketGroup $group): ?Admin
    {
        $candidates = $this->eligible($group);

        if ($candidates === []) {
            return null;
        }

        $load = $this->tickets->activeCountsByAdmin(array_map(static fn (Admin $admin): int => $admin->id, $candidates));
        usort($candidates, static fn (Admin $a, Admin $b): int => [$load[$a->id] ?? 0, $a->name] <=> [$load[$b->id] ?? 0, $b->name]);

        return $candidates[0];
    }

    /**
     * Admins that can work tickets of the group (also used to fill the "assign to" list).
     *
     * @return list<Admin>
     */
    public function eligible(TicketGroup $group): array
    {
        try {
            $agents = $this->admins->activeWithPermission(AdminPermission::SupportManage->value);
        } catch (PermissionDoesNotExist) {
            return [];
        }

        return array_values(array_filter(
            $agents->all(),
            static fn (Admin $admin): bool => $group === TicketGroup::Support || $admin->can($group->permission()->value),
        ));
    }
}
