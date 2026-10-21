<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The chart of accounts (SRS §12.3). Each type has a normal side: the cached balance of an account is the sum of
 * its normal-side lines minus its opposite-side lines, so a healthy account is positive whatever its side.
 *
 * - Liabilities and revenue (credit-normal): wallets, platform revenue, payout clearing, taxes payable.
 * - Assets and expenses (debit-normal): gateway clearing, promo expense, partner receivable, adjustments.
 *
 * Owned accounts (wallets) have an owner; platform accounts have none. `allowsNegative()` is the default
 * negative-balance policy copied to each new account: customer wallets and payout clearing never go below zero,
 * provider wallets may (cash commission owed, limited by the cash-debt setting through eligibility), and the
 * platform's own clearing/expense accounts may because they are bookkeeping counterparts.
 */
enum LedgerAccountType: string implements HasLabel
{
    use EnumHelpers;

    case CustomerWallet = 'customer_wallet';
    case ProviderWallet = 'provider_wallet';
    case PlatformRevenue = 'platform_revenue';
    case PromoExpense = 'promo_expense';
    case PartnerReceivable = 'partner_receivable';
    case GatewayClearing = 'gateway_clearing';
    case PayoutClearing = 'payout_clearing';
    case TaxesPayable = 'taxes_payable';
    case Adjustments = 'adjustments';

    public function normalSide(): EntrySide
    {
        return match ($this) {
            self::PromoExpense, self::PartnerReceivable, self::GatewayClearing, self::Adjustments => EntrySide::Debit,
            default => EntrySide::Credit,
        };
    }

    public function isOwned(): bool
    {
        return $this === self::CustomerWallet || $this === self::ProviderWallet;
    }

    /**
     * Morph alias of the owner model of an owned account.
     */
    public function ownerType(): ?string
    {
        return match ($this) {
            self::CustomerWallet => 'user',
            self::ProviderWallet => 'provider_profile',
            default => null,
        };
    }

    public function isWallet(): bool
    {
        return $this->isOwned();
    }

    public function allowsNegative(): bool
    {
        return match ($this) {
            self::CustomerWallet, self::PayoutClearing, self::PlatformRevenue, self::TaxesPayable => false,
            default => true,
        };
    }

    public function label(): string
    {
        return __("enums.ledger_account_type.{$this->value}");
    }
}
