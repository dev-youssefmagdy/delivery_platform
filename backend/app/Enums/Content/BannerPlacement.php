<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Where a banner or announcement is shown (apps and website).
 */
enum BannerPlacement: string implements HasLabel
{
    use EnumHelpers;

    case AppHome = 'app_home';
    case AppBooking = 'app_booking';
    case WebHome = 'web_home';
    case WebTopBar = 'web_top_bar';
    case WebServices = 'web_services';

    public function label(): string
    {
        return __("enums.banner_placement.{$this->value}");
    }
}
