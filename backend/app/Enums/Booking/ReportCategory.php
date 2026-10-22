<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\TicketCategory;

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

    /**
     * The support ticket category a report becomes (P3.7). A complaint about behaviour is about the driver when a customer
     * files it and about the customer when a provider does.
     */
    public function ticketCategory(bool $byProvider): TicketCategory
    {
        return match ($this) {
            self::Safety => TicketCategory::SafetyHarassment,
            self::Behaviour => $byProvider ? TicketCategory::CustomerConduct : TicketCategory::DriverProvider,
            self::FareDispute => TicketCategory::FareExtraCharge,
            self::RouteOrDelay => TicketCategory::Booking,
            self::LostItem => TicketCategory::LostItem,
            self::Other => TicketCategory::Other,
        };
    }
}
