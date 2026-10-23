<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Section types of a blocks-based page (homepage and generic pages).
 */
enum BlockType: string implements HasLabel
{
    use EnumHelpers;

    case Hero = 'hero';
    case RichText = 'rich_text';
    case Features = 'features';
    case Steps = 'steps';
    case Cta = 'cta';
    case Faq = 'faq';
    case Cities = 'cities';
    case AppDownload = 'app_download';

    public function label(): string
    {
        return __("enums.block_type.{$this->value}");
    }
}
