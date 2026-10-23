<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Support\Auth\PhoneNumber;

/**
 * Providers for the finance screens (payout, cash reconciliation filters and forms). Finance roles do not hold `drivers.view`,
 * so this lookup is gated by `finance.view` or `payouts.view` instead and returns the name and masked phone only.
 */
final class FinanceProviderLookup implements Lookup
{
    public function __construct(private readonly ProviderProfileRepositoryInterface $providers) {}

    public function permission(): AdminPermission
    {
        // Either permission is enough; the check below enforces it per request through the registry's permission() value.
        return auth('admin')->user()?->can(AdminPermission::PayoutsView->value) ? AdminPermission::PayoutsView : AdminPermission::FinanceView;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->providers->lookup($term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->providers->findManyByIds($ids)->all());
    }

    /**
     * @param array<int, ProviderProfile> $items
     * @return list<LookupOptionData>
     */
    private function map(array $items): array
    {
        return array_values(array_map(static function (ProviderProfile $provider): LookupOptionData {
            $phone = $provider->user->phone_e164;
            $label = ($provider->user->name ?? __('admin.providers.unnamed')).($phone === null ? '' : ' · '.PhoneNumber::mask($phone));

            return new LookupOptionData($provider->id, $label, ['uuid' => $provider->uuid]);
        }, $items));
    }
}
