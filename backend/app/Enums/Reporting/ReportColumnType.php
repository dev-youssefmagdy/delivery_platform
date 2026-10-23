<?php

namespace App\Enums\Reporting;

enum ReportColumnType: string
{
    case Text = 'text';
    case Date = 'date';
    case Integer = 'integer';
    case Money = 'money';
    case Percent = 'percent';
    case Seconds = 'seconds';
    case Decimal = 'decimal';
}
