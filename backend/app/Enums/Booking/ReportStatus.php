<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum ReportStatus: string implements HasLabel
{
    use EnumHelpers;

    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return __("enums.report_status.{$this->value}");
    }
}
