<?php

namespace App\Repositories\Eloquent\Customers;

use App\Models\User;
use App\Repositories\Contracts\Customers\CustomerActivityRepositoryInterface;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * There are no bookings, payments or ledger tables before P2.3/P3.1, so nothing can be open and the honest count is
 * 0. As soon as one of those tables exists this implementation MUST be replaced with the real queries; until then it
 * fails loudly instead of silently approving deletions of customers with live bookings or unsettled money.
 */
final class CustomerActivityRepository implements CustomerActivityRepositoryInterface
{
    private const FUTURE_TABLES = ['bookings', 'payments', 'wallets', 'journal_entries'];

    public function activeBookingCount(User $user): int
    {
        $this->assertNothingToCount();

        return 0;
    }

    public function openFinancialItemCount(User $user): int
    {
        $this->assertNothingToCount();

        return 0;
    }

    private function assertNothingToCount(): void
    {
        foreach (self::FUTURE_TABLES as $table) {
            if (Schema::hasTable($table)) {
                throw new LogicException("CustomerActivityRepository must query [{$table}] now that it exists (P2.3/P3.1).");
            }
        }
    }
}
