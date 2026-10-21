<?php

namespace App\DTOs\Finance;

use App\Enums\Finance\EntrySide;
use App\Models\LedgerAccount;
use App\Support\Money\Money;

/**
 * One line of an entry to post: an account, a side and a strictly positive amount in the account's currency.
 */
final readonly class JournalLineData
{
    public function __construct(public int $accountId, public EntrySide $side, public Money $amount) {}

    public static function debit(LedgerAccount|int $account, Money $amount): self
    {
        return new self($account instanceof LedgerAccount ? $account->id : $account, EntrySide::Debit, $amount);
    }

    public static function credit(LedgerAccount|int $account, Money $amount): self
    {
        return new self($account instanceof LedgerAccount ? $account->id : $account, EntrySide::Credit, $amount);
    }

    public function reversed(): self
    {
        return new self($this->accountId, $this->side->opposite(), $this->amount);
    }
}
