<?php

namespace App\Services\Finance;

use App\Enums\Finance\LedgerAccountType;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Finance\JournalLineRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The wallet of a customer or provider as the apps see it: balances and the paginated statement (journal lines of the person's
 * wallet accounts, newest first). Read-only; the lines are the ledger itself.
 */
final class WalletStatementService
{
    public function __construct(
        private readonly LedgerAccountRepositoryInterface $accounts,
        private readonly JournalLineRepositoryInterface $lines,
    ) {}

    /**
     * @return Collection<int, LedgerAccount>
     */
    public function customerAccounts(User $customer): Collection
    {
        return $this->accounts->forOwner(LedgerAccountType::CustomerWallet, $customer->id);
    }

    /**
     * @return Collection<int, LedgerAccount>
     */
    public function providerAccounts(ProviderProfile $provider): Collection
    {
        return $this->accounts->forOwner(LedgerAccountType::ProviderWallet, $provider->id);
    }

    /**
     * @param Collection<int, LedgerAccount> $accounts
     * @return LengthAwarePaginator<int, JournalLine>
     */
    public function page(Collection $accounts, int $perPage): LengthAwarePaginator
    {
        return $this->lines->statementPage($accounts->map(static fn (LedgerAccount $account): int => $account->id)->all(), max(1, min(50, $perPage)));
    }

    /**
     * Every line of the accounts, oldest first (personal data export).
     *
     * @param Collection<int, LedgerAccount> $accounts
     * @return Collection<int, JournalLine>
     */
    public function allLines(Collection $accounts): Collection
    {
        return $this->lines->statementBetween($accounts->map(static fn (LedgerAccount $account): int => $account->id)->all(), null, null);
    }
}
