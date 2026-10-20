<?php

namespace App\Repositories\Eloquent\Provider;

use App\Models\ProviderProfile;
use App\Repositories\Contracts\Provider\ProviderActivityRepositoryInterface;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * There are no bookings, wallets or payouts before P2.3/P3.1/P3.4, so nothing can be open and the honest count is 0.
 * As soon as one of those tables exists this implementation MUST be replaced with the real queries; until then it
 * fails loudly instead of silently approving the deletion of a provider with a live job or money owed.
 */
final class ProviderActivityRepository implements ProviderActivityRepositoryInterface
{
    private const FUTURE_TABLES = ['bookings', 'payments', 'wallets', 'journal_entries', 'payout_requests'];

    public function activeJobCount(ProviderProfile $profile): int
    {
        $this->assertNothingToCount();

        return 0;
    }

    public function openFinancialItemCount(ProviderProfile $profile): int
    {
        $this->assertNothingToCount();

        return 0;
    }

    private function assertNothingToCount(): void
    {
        foreach (self::FUTURE_TABLES as $table) {
            if (Schema::hasTable($table)) {
                throw new LogicException("ProviderActivityRepository must query [{$table}] now that it exists (P2.3/P3.1/P3.4).");
            }
        }
    }
}
