<?php

namespace App\Enums\Settings;

use App\Enums\Concerns\EnumHelpers;

/**
 * Storage/value type of a platform setting (ADR-10). Values are persisted as JSON.
 */
enum SettingType: string
{
    use EnumHelpers;

    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case StringList = 'string_list';
    case NumberList = 'number_list';

    public function isList(): bool
    {
        return $this === self::StringList || $this === self::NumberList;
    }
}
