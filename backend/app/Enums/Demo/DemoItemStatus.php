<?php

namespace App\Enums\Demo;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

enum DemoItemStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';

    /** Set only through the archive action (mandatory reason). */
    case Archived = 'archived';

    public function label(): string
    {
        return __("enums.demo_item_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Active => BadgeColor::Success,
            self::Inactive => BadgeColor::Warning,
            self::Archived => BadgeColor::Danger,
        };
    }

    /**
     * Statuses an admin may pick in the form (Archived requires the archive action).
     *
     * @return list<self>
     */
    public static function editable(): array
    {
        return [self::Draft, self::Active, self::Inactive];
    }
}
