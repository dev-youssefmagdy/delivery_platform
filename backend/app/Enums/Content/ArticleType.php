<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Help-center article or blog/news article.
 */
enum ArticleType: string implements HasLabel
{
    use EnumHelpers;

    case Help = 'help';
    case Blog = 'blog';

    public function label(): string
    {
        return __("enums.article_type.{$this->value}");
    }
}
