<?php

namespace App\Enums\Payments;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Payment axis (Plan §7.1, SRS §12.2), separate from the trip lifecycle; used by `payments.status` and mirrored on
 * `bookings.payment_status` for the trip payment. `allowedTransitions()` owns the graph that PaymentTransitionService
 * enforces: `paid` is reached only from a verified webhook or gateway query (I8) or, for cash, from the provider's
 * confirmation (`cash_collected`).
 */
enum PaymentStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case NotRequired = 'not_required';
    case Pending = 'pending';
    case RequiresAction = 'requires_action';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case CashDue = 'cash_due';
    case CashCollected = 'cash_collected';
    case ReconciliationRequired = 'reconciliation_required';

    public function label(): string
    {
        return __("enums.payment_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::NotRequired => BadgeColor::Neutral,
            self::Pending, self::RequiresAction, self::Authorized, self::CashDue => BadgeColor::Info,
            self::Paid, self::CashCollected => BadgeColor::Success,
            self::Failed, self::ReconciliationRequired => BadgeColor::Danger,
            self::PartiallyRefunded, self::Refunded => BadgeColor::Warning,
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::NotRequired => [],
            self::Pending => [self::RequiresAction, self::Authorized, self::Paid, self::Failed, self::ReconciliationRequired],
            self::RequiresAction => [self::Pending, self::Authorized, self::Paid, self::Failed, self::ReconciliationRequired],
            self::Authorized => [self::Paid, self::Failed, self::ReconciliationRequired],
            // A late success after a failure or a timeout is real money: the payment can still become paid.
            self::Failed => [self::Paid, self::ReconciliationRequired],
            self::ReconciliationRequired => [self::Pending, self::RequiresAction, self::Authorized, self::Paid, self::Failed],
            self::Paid => [self::PartiallyRefunded, self::Refunded],
            self::PartiallyRefunded => [self::Refunded],
            self::Refunded, self::CashCollected => [],
            self::CashDue => [self::CashCollected, self::ReconciliationRequired],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Money is still expected from the customer (or the gateway has not answered): shown as "open" in positions.
     */
    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Pending, self::RequiresAction, self::Authorized, self::ReconciliationRequired, self::CashDue];
    }

    /**
     * The gateway has not given a final answer yet.
     *
     * @return list<self>
     */
    public static function awaitingGateway(): array
    {
        return [self::Pending, self::RequiresAction, self::Authorized];
    }

    /**
     * The customer's money was received (online or in cash): the amount that refunds are bounded by.
     */
    public function isCollected(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded, self::Refunded, self::CashCollected], true);
    }
}
