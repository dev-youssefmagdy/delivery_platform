<?php

namespace App\DataTables\Admin\Payout;

use App\DTOs\Payout\PayoutFilterData;
use App\Models\ProviderProfile;
use LogicException;

/**
 * Payout requests of one provider (the Payouts tab of the provider page): the provider comes from the `{provider}` route
 * parameter, which the route's `can:` middleware already authorized.
 */
final class ProviderPayoutsDataTable extends PayoutRequestsDataTable
{
    public const TABLE_ID = 'provider-payouts-table';

    protected function scoped(PayoutFilterData $filters): PayoutFilterData
    {
        $provider = $this->request()->route('provider');

        return $provider instanceof ProviderProfile ? $filters->forProvider($provider->id) : throw new LogicException('The provider payouts table needs a {provider} route parameter.');
    }
}
