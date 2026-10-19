<?php

namespace App\Enums\Settings;

use App\Enums\Concerns\EnumHelpers;

/**
 * Which admin form component renders a setting (the settings screen is generated from SettingKey metadata).
 */
enum SettingInput: string
{
    use EnumHelpers;

    case Text = 'text';
    case Email = 'email';
    case Phone = 'phone';
    case Number = 'number';
    case Money = 'money';
    case Toggle = 'toggle';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case NumberList = 'number_list';
}
