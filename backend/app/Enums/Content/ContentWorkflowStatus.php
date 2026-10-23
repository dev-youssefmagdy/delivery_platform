<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * CMS workflow (SRS 16.13): draft -> in_review -> scheduled | published -> archived. Only drafts are editable.
 */
enum ContentWorkflowStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case InReview = 'in_review';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return __("enums.content_workflow_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Warning,
            self::InReview => BadgeColor::Info,
            self::Scheduled => BadgeColor::Primary,
            self::Published => BadgeColor::Success,
            self::Archived => BadgeColor::Neutral,
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isLive(): bool
    {
        return $this === self::Published;
    }
}
