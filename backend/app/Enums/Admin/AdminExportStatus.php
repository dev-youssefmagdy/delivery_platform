<?php

namespace App\Enums\Admin;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Lifecycle of a queued admin CSV export (Plan §5.4).
 */
enum AdminExportStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return __("enums.admin_export_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Pending, self::Processing => BadgeColor::Info,
            self::Completed => BadgeColor::Success,
            self::Failed => BadgeColor::Danger,
        };
    }

    public function isFinished(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }
}
