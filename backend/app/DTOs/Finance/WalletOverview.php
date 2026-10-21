<?php

namespace App\DTOs\Finance;

use App\Models\LedgerAccount;

/**
 * Wallets of one person for the admin tab. `cashDebtMinor` / `cashDebtLimitMinor` are set for providers only.
 */
final readonly class WalletOverview
{
    /**
     * @param list<LedgerAccount> $accounts
     */
    public function __construct(public array $accounts, public ?int $cashDebtMinor, public ?int $cashDebtLimitMinor) {}

    public function overLimit(): bool
    {
        return $this->cashDebtMinor !== null && $this->cashDebtLimitMinor !== null && $this->cashDebtMinor > $this->cashDebtLimitMinor;
    }
}
