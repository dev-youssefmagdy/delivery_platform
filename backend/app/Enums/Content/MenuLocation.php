<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Website menus. One menu per location.
 */
enum MenuLocation: string implements HasLabel
{
    use EnumHelpers;

    case Header = 'header';
    case FooterExplore = 'footer_explore';
    case FooterLegal = 'footer_legal';

    public function label(): string
    {
        return __("enums.menu_location.{$this->value}");
    }
}
