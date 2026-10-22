<?php

namespace App\Enums\Support;

use App\Enums\Admin\AdminPermission;
use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The team that owns a ticket. Escalating moves a ticket to finance, safety or operations; the group decides who else may see it.
 */
enum TicketGroup: string implements HasLabel
{
    use EnumHelpers;

    case Support = 'support';
    case Finance = 'finance';
    case Safety = 'safety';
    case Operations = 'operations';

    public function label(): string
    {
        return __("enums.ticket_group.{$this->value}");
    }

    /**
     * Permission that lets an admin of this team see tickets escalated to it (besides support.view_all).
     */
    public function permission(): AdminPermission
    {
        return match ($this) {
            self::Support => AdminPermission::SupportView,
            self::Finance => AdminPermission::FinanceView,
            self::Safety => AdminPermission::IncidentsManage,
            self::Operations => AdminPermission::BookingsManage,
        };
    }

    /**
     * @return list<self>
     */
    public static function escalationTargets(): array
    {
        return [self::Finance, self::Safety, self::Operations];
    }
}
