<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Lifecycle of managed content (policy versions now; CMS pages later). Published content is immutable.
 */
enum ContentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return __("enums.content_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Warning,
            self::Published => BadgeColor::Success,
            self::Archived => BadgeColor::Neutral,
        };
    }
}
