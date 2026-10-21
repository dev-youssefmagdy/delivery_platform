<?php

namespace App\Repositories\Contracts\Finance;

use App\Models\ProviderProfile;
use App\Models\User;

/**
 * Payments that are not settled yet (pending, requires action, awaiting refund). The payments tables arrive in P3.2:
 * until then the bound implementation answers "none" by construction, and P3.2 rebinds this interface to a query over
 * `payments` / `payment_refunds`. The ledger-backed financial position adds this count to the wallet part (ADR-115).
 */
interface PaymentPositionRepositoryInterface
{
    public function openForCustomer(User $customer): int;

    public function openForProvider(ProviderProfile $profile): int;
}
