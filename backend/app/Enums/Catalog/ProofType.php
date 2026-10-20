<?php

namespace App\Enums\Catalog;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Proof of delivery a category can require at completion (SRS §5.1 C).
 */
enum ProofType: string implements HasLabel
{
    use EnumHelpers;

    case Photo = 'photo';
    case Signature = 'signature';
    case RecipientOtp = 'recipient_otp';
    case PickupPin = 'pickup_pin';
    case Checklist = 'checklist';
    case Weight = 'weight';

    public function label(): string
    {
        return __("enums.proof_type.{$this->value}");
    }
}
