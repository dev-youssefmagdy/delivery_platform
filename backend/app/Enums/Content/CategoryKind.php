<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What a content category groups.
 */
enum CategoryKind: string implements HasLabel
{
    use EnumHelpers;

    case Faq = 'faq';
    case Article = 'article';

    public function label(): string
    {
        return __("enums.category_kind.{$this->value}");
    }
}
