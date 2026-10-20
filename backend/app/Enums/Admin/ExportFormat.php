<?php

namespace App\Enums\Admin;

use App\Enums\Concerns\EnumHelpers;

/**
 * File format of a queued admin export: listing exports are CSV, personal-data exports are JSON.
 */
enum ExportFormat: string
{
    use EnumHelpers;

    case Csv = 'csv';
    case Json = 'json';
}
