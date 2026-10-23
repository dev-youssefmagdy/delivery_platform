<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum IncidentEventType: string implements HasLabel
{
    use EnumHelpers;

    case Created = 'created';
    case RepeatSos = 'repeat_sos';
    case UserUpdate = 'user_update';
    case AttachmentAdded = 'attachment_added';
    case TrustedContactsNotified = 'trusted_contacts_notified';
    case Acknowledged = 'acknowledged';
    case Assigned = 'assigned';
    case Unassigned = 'unassigned';
    case Note = 'note';
    case PublicMessage = 'public_message';
    case SeverityChanged = 'severity_changed';
    case PriorityChanged = 'priority_changed';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Reopened = 'reopened';
    case LegalHoldSet = 'legal_hold_set';
    case LegalHoldReleased = 'legal_hold_released';
    case SlaBreached = 'sla_breached';
    case Exported = 'exported';

    public function label(): string
    {
        return __("safety.enums.event.{$this->value}");
    }
}
