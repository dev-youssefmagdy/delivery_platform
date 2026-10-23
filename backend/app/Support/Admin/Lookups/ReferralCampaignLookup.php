<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Models\ReferralCampaign;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;

/**
 * Referral campaigns for the filters of the relationship and reward listings (`referrals.view`).
 */
final class ReferralCampaignLookup implements Lookup
{
    public function __construct(private readonly ReferralRepositoryInterface $referrals) {}

    public function permission(): ?AdminPermission
    {
        return AdminPermission::ReferralsView;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->referrals->campaignLookup($term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->referrals->campaignsByIds($ids)->all());
    }

    /**
     * @param array<int, ReferralCampaign> $items
     * @return list<LookupOptionData>
     */
    private function map(array $items): array
    {
        return array_values(array_map(static fn (ReferralCampaign $item): LookupOptionData => new LookupOptionData($item->id, $item->displayName(), []), $items));
    }
}
