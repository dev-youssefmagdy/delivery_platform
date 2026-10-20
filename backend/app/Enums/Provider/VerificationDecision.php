<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The three outcomes of reviewing an application (SRS §16.3).
 */
enum VerificationDecision: string implements HasLabel
{
    use EnumHelpers;

    case Approve = 'approve';
    case RequestMoreInformation = 'request_more_information';
    case Reject = 'reject';

    public function label(): string
    {
        return __("enums.verification_decision.{$this->value}");
    }
}
