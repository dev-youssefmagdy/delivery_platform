<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How the provider handed the platform its cash commission.
 */
enum CashSettlementMethod: string implements HasLabel
{
    use EnumHelpers;

    case CashDeposit = 'cash_deposit';
    case BankTransfer = 'bank_transfer';
    case MobileWallet = 'mobile_wallet';

    public function label(): string
    {
        return __("enums.cash_settlement_method.{$this->value}");
    }
}
