<?php

namespace App\Enums\Payout;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Where a provider receives a payout. The fields each type needs are validated by the request; the details are stored encrypted.
 */
enum PayoutMethodType: string implements HasLabel
{
    use EnumHelpers;

    case BankAccount = 'bank_account';
    case MobileWallet = 'mobile_wallet';

    public function label(): string
    {
        return __("enums.payout_method_type.{$this->value}");
    }

    /**
     * Detail fields that must be present for the type.
     *
     * @return list<string>
     */
    public function requiredFields(): array
    {
        return match ($this) {
            self::BankAccount => ['holder_name', 'bank_name', 'account_number'],
            self::MobileWallet => ['holder_name', 'wallet_number'],
        };
    }

    /**
     * The field whose last digits identify the method on screens (the rest never leaves the server).
     */
    public function identifierField(): string
    {
        return match ($this) {
            self::BankAccount => 'account_number',
            self::MobileWallet => 'wallet_number',
        };
    }
}
