<?php

namespace App\Enums\Geo;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Kind of a customer's saved place. A customer has at most one `home` and one `work`.
 */
enum SavedPlaceType: string implements HasLabel
{
    use EnumHelpers;

    case Home = 'home';
    case Work = 'work';
    case Other = 'other';

    public function label(): string
    {
        return __("enums.saved_place_type.{$this->value}");
    }

    public function isUnique(): bool
    {
        return $this !== self::Other;
    }
}
