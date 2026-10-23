<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Why a journal entry exists. Written once with the entry; the later milestones (payments, payouts, refunds,
 * adjustments, cancellation fees) post through the same ledger with their own type.
 */
enum JournalEntryType: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case BookingSettlementCash = 'booking_settlement_cash';
    case BookingSettlementGateway = 'booking_settlement_gateway';
    case Tip = 'tip';
    case Refund = 'refund';
    case PayoutReserve = 'payout_reserve';
    case PayoutPaid = 'payout_paid';
    case Adjustment = 'adjustment';
    case CashSettlement = 'cash_settlement';
    case CancellationFee = 'cancellation_fee';
    case DebtSettlement = 'debt_settlement';
    case Reversal = 'reversal';
    case ReferralReward = 'referral_reward';
    case LoyaltyReward = 'loyalty_reward';
    case IncentiveBonus = 'incentive_bonus';
    case CreditExpiry = 'credit_expiry';

    public function label(): string
    {
        return __("enums.journal_entry_type.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::BookingSettlementCash, self::BookingSettlementGateway, self::Tip => BadgeColor::Success,
            self::Refund, self::Reversal, self::CreditExpiry => BadgeColor::Warning,
            self::ReferralReward, self::LoyaltyReward, self::IncentiveBonus => BadgeColor::Primary,
            self::PayoutReserve, self::PayoutPaid => BadgeColor::Info,
            self::Adjustment, self::CashSettlement, self::CancellationFee, self::DebtSettlement => BadgeColor::Neutral,
        };
    }
}
