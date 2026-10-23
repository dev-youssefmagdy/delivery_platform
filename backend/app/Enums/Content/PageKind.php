<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Kind of CMS page. `home` is the single homepage (blocks); `service` pages link to a service category and `city` pages to a city.
 */
enum PageKind: string implements HasLabel
{
    use EnumHelpers;

    case Home = 'home';
    case Generic = 'generic';
    case Service = 'service';
    case City = 'city';

    public function label(): string
    {
        return __("enums.page_kind.{$this->value}");
    }
}
