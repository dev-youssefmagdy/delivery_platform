<?php

namespace App\Enums\Demo;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum DemoItemTag: string implements HasLabel
{
    use EnumHelpers;

    case New = 'new';
    case Popular = 'popular';
    case Seasonal = 'seasonal';
    case Limited = 'limited';

    public function label(): string
    {
        return __("enums.demo_item_tag.{$this->value}");
    }
}
