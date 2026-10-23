<?php

namespace App\Enums\Reporting;

enum KpiUnit: string
{
    case Count = 'count';
    case Percent = 'percent';
    case Money = 'money';
    case Seconds = 'seconds';
}
