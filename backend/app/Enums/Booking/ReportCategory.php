<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What a booking report is about (stored in `booking_reports`; P3.7 turns them into support tickets).
 */
enum ReportCategory: string implements HasLabel
{
    use EnumHelpers;

    case Safety = 'safety';
    case Behaviour = 'behaviour';
    case FareDispute = 'fare_dispute';
    case RouteOrDelay = 'route_or_delay';
    case LostItem = 'lost_item';
    case Other = 'other';

    public function label(): string
    {
        return __("enums.report_category.{$this->value}");
    }
}
