<?php

namespace App\Enums\Identity;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum OtpPurpose: string implements HasLabel
{
    use EnumHelpers;

    case Login = 'login';

    public function label(): string
    {
        return __("enums.otp_purpose.{$this->value}");
    }
}
