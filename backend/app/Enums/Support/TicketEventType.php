<?php

namespace App\Enums\Support;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum TicketEventType: string implements HasLabel
{
    use EnumHelpers;

    case Created = 'created';
    case Reply = 'reply';
    case InternalNote = 'internal_note';
    case Assigned = 'assigned';
    case Unassigned = 'unassigned';
    case StatusChanged = 'status_changed';
    case PriorityChanged = 'priority_changed';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Reopened = 'reopened';
    case Linked = 'linked';
    case SlaBreached = 'sla_breached';

    public function label(): string
    {
        return __("enums.ticket_event_type.{$this->value}");
    }
}
