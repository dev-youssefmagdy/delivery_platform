<?php

namespace App\Enums\Content;

use App\Enums\Admin\AdminPermission;
use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Transitions of the CMS workflow, with the permission each needs and the states it starts from.
 * Everything except "submit" needs content.publish, and every action that changes what the public sees needs a reason.
 */
enum WorkflowAction: string implements HasLabel
{
    use EnumHelpers;

    case Submit = 'submit';
    case Reject = 'reject';
    case Publish = 'publish';
    case Schedule = 'schedule';
    case Revert = 'revert';
    case Archive = 'archive';
    case Restore = 'restore';

    public function label(): string
    {
        return __("enums.content_workflow_action.{$this->value}");
    }

    public function permission(): AdminPermission
    {
        return $this === self::Submit ? AdminPermission::ContentManage : AdminPermission::ContentPublish;
    }

    public function reasonRequired(): bool
    {
        return $this !== self::Submit;
    }

    /**
     * @return list<ContentWorkflowStatus>
     */
    public function allowedFrom(): array
    {
        return match ($this) {
            self::Submit => [ContentWorkflowStatus::Draft],
            self::Reject => [ContentWorkflowStatus::InReview],
            self::Publish => [ContentWorkflowStatus::InReview, ContentWorkflowStatus::Draft, ContentWorkflowStatus::Scheduled],
            self::Schedule => [ContentWorkflowStatus::InReview, ContentWorkflowStatus::Draft, ContentWorkflowStatus::Scheduled],
            self::Revert => [ContentWorkflowStatus::Scheduled, ContentWorkflowStatus::Published],
            self::Archive => [ContentWorkflowStatus::Draft, ContentWorkflowStatus::InReview, ContentWorkflowStatus::Scheduled, ContentWorkflowStatus::Published],
            self::Restore => [ContentWorkflowStatus::Archived],
        };
    }

    public function target(): ContentWorkflowStatus
    {
        return match ($this) {
            self::Submit => ContentWorkflowStatus::InReview,
            self::Reject, self::Revert, self::Restore => ContentWorkflowStatus::Draft,
            self::Publish => ContentWorkflowStatus::Published,
            self::Schedule => ContentWorkflowStatus::Scheduled,
            self::Archive => ContentWorkflowStatus::Archived,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Submit => 'arrow-right',
            self::Reject, self::Revert => 'arrow-left',
            self::Publish => 'check-circle',
            self::Schedule => 'clock',
            self::Archive => 'archive',
            self::Restore => 'refresh',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Publish => 'success',
            self::Archive, self::Reject => 'danger',
            default => 'neutral',
        };
    }
}
