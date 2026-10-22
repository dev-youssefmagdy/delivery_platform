<?php

namespace App\Enums\Support;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What a support ticket is about (SRS §14.3). The case also says which escalation group normally owns it and whether it is a
 * dispute (notified as a dispute update) or a safety matter (notified as a safety case update, higher priority).
 */
enum TicketCategory: string implements HasLabel
{
    use EnumHelpers;

    case Booking = 'booking';
    case FareExtraCharge = 'fare_extra_charge';
    case PaymentRefund = 'payment_refund';
    case Coupon = 'coupon';
    case DriverProvider = 'driver_provider';
    case CustomerConduct = 'customer_conduct';
    case LostItem = 'lost_item';
    case DamagedGoods = 'damaged_goods';
    case DeliveryFailure = 'delivery_failure';
    case SafetyHarassment = 'safety_harassment';
    case Account = 'account';
    case Verification = 'verification';
    case Payout = 'payout';
    case Technical = 'technical';
    case Other = 'other';

    public function label(): string
    {
        return __("enums.ticket_category.{$this->value}");
    }

    public function isSafety(): bool
    {
        return $this === self::SafetyHarassment;
    }

    public function isDispute(): bool
    {
        return in_array($this, [self::FareExtraCharge, self::PaymentRefund, self::DamagedGoods, self::DeliveryFailure, self::CustomerConduct, self::DriverProvider], true);
    }

    public function defaultPriority(): TicketPriority
    {
        return match ($this) {
            self::SafetyHarassment => TicketPriority::Urgent,
            self::PaymentRefund, self::FareExtraCharge, self::Payout, self::LostItem, self::DamagedGoods => TicketPriority::High,
            default => TicketPriority::Normal,
        };
    }

    public function defaultGroup(): TicketGroup
    {
        return match ($this) {
            self::SafetyHarassment => TicketGroup::Safety,
            self::PaymentRefund, self::Payout => TicketGroup::Finance,
            default => TicketGroup::Support,
        };
    }

    /**
     * Categories a customer or provider may choose when opening a ticket. Verification and payout are provider matters.
     *
     * @return list<self>
     */
    public static function forApp(string $app): array
    {
        $providerOnly = [self::Verification, self::Payout, self::CustomerConduct];
        $customerOnly = [self::DriverProvider, self::Coupon];

        return array_values(array_filter(self::cases(), static fn (self $case): bool => ! in_array($case, $app === 'provider' ? $customerOnly : $providerOnly, true)));
    }
}
