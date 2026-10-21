<?php

namespace App\Repositories\Eloquent\Provider;

use App\DTOs\Location\ActiveBooking;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Provider\ProviderWorkloadRepositoryInterface;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * Honest placeholder: no bookings (P2.3) or wallets (P3.1) exist yet, so no provider has an active job or owes money.
 * It fails loudly once either table exists, so it must be replaced with the real queries in those milestones.
 */
final class ProviderWorkloadRepository implements ProviderWorkloadRepositoryInterface
{
    private const FUTURE_TABLES = ['bookings', 'wallets'];

    public function activeBooking(ProviderProfile $profile): ?ActiveBooking
    {
        $this->assertNothingToCount();

        return null;
    }

    public function cashDebtMinor(ProviderProfile $profile): int
    {
        $this->assertNothingToCount();

        return 0;
    }

    private function assertNothingToCount(): void
    {
        foreach (self::FUTURE_TABLES as $table) {
            if (Schema::hasTable($table)) {
                throw new LogicException("ProviderWorkloadRepository must query [{$table}] now that it exists (P2.3 active job, P3.1 cash debt).");
            }
        }
    }
}
