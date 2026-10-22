<?php

namespace App\Enums\Payout;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * A payout request's life (SRS §12.3). The balance is reserved in the ledger when the request is made, so the open states
 * hold money back from the provider's wallet:
 *
 * requested -> under_review -> approved -> processing -> paid, with failed (the transfer did not happen), rejected (an admin
 * said no) and cancelled (the provider withdrew) returning the reservation, and reversed (a paid transfer came back).
 */
enum PayoutStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Requested = 'requested';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    public function label(): string
    {
        return __("enums.payout_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Requested, self::UnderReview => BadgeColor::Warning,
            self::Approved, self::Processing => BadgeColor::Info,
            self::Paid => BadgeColor::Success,
            self::Failed, self::Rejected, self::Reversed => BadgeColor::Danger,
            self::Cancelled => BadgeColor::Neutral,
        };
    }

    /**
     * The request still holds the provider's money in the payout clearing account (nothing has left or come back yet).
     */
    public function holdsReservation(): bool
    {
        return in_array($this, self::open(), true);
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Requested, self::UnderReview, self::Approved, self::Processing];
    }

    /**
     * @return list<self>
     */
    public static function awaitingDecision(): array
    {
        return [self::Requested, self::UnderReview];
    }

    /**
     * The provider may withdraw the request only before an admin approved it.
     */
    public function isCancellableByProvider(): bool
    {
        return in_array($this, self::awaitingDecision(), true);
    }
}
