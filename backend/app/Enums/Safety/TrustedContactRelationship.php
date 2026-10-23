<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum TrustedContactRelationship: string implements HasLabel
{
    use EnumHelpers;

    case Family = 'family';
    case Partner = 'partner';
    case Friend = 'friend';
    case Colleague = 'colleague';
    case Other = 'other';

    public function label(): string
    {
        return __("safety.enums.relationship.{$this->value}");
    }
}
