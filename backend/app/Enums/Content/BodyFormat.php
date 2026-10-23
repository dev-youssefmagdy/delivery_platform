<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How rich text is written. Both are rendered to sanitized HTML on save; raw HTML is never passed through.
 */
enum BodyFormat: string implements HasLabel
{
    use EnumHelpers;

    case Markdown = 'markdown';
    case Html = 'html';

    public function label(): string
    {
        return __("enums.body_format.{$this->value}");
    }
}
