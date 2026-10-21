<?php

namespace App\Repositories\Eloquent\Finance;

use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Finance\PaymentPositionRepositoryInterface;

/**
 * Neutral until the payments tables exist (P3.2): no payment, refund or webhook row can exist yet, so zero open
 * payments is the true answer, not a stub that hides data. Replaced in RepositoryServiceProvider by P3.2.
 */
final class PrePaymentsPaymentPositionRepository implements PaymentPositionRepositoryInterface
{
    public function openForCustomer(User $customer): int
    {
        return 0;
    }

    public function openForProvider(ProviderProfile $profile): int
    {
        return 0;
    }
}
