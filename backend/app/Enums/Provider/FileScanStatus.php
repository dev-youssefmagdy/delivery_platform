<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * What the file scanner said about an uploaded file. `skipped` means the noop driver ran (local/testing only):
 * the file was NOT scanned and the admin review screen says so. `not_scanned` means no scanner was configured.
 */
enum FileScanStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Clean = 'clean';
    case Infected = 'infected';
    case Skipped = 'skipped';
    case NotScanned = 'not_scanned';

    public function label(): string
    {
        return __("enums.file_scan_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Clean => BadgeColor::Success,
            self::Infected => BadgeColor::Danger,
            self::Skipped, self::NotScanned => BadgeColor::Warning,
        };
    }
}
