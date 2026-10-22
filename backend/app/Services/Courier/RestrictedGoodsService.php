<?php

namespace App\Services\Courier;

use App\Enums\Catalog\BookingType;
use App\Enums\Policy\PolicyType;
use App\Exceptions\BusinessRuleViolation;
use App\Models\PolicyVersion;
use App\Repositories\Contracts\Policy\PolicyVersionRepositoryInterface;

/**
 * Restricted-goods acknowledgement (SRS §5.1-C, §13.3): a parcel request must carry the uuid of the CURRENT published
 * `restricted_goods` policy version the customer was shown. An old version, a wrong one or none at all is refused, so the
 * stored acknowledgement always names the exact text the customer accepted. The server never accepts a bare "yes".
 */
final class RestrictedGoodsService
{
    public function __construct(private readonly PolicyVersionRepositoryInterface $policies) {}

    public function requiredFor(BookingType $type): bool
    {
        return $type === BookingType::Parcel;
    }

    /**
     * @throws BusinessRuleViolation `restricted_goods_ack_required`, `restricted_goods_ack_stale`, `restricted_goods_policy_unavailable`
     */
    public function assertAcknowledged(?string $policyVersionUuid): PolicyVersion
    {
        $current = $this->policies->findCurrentByType(PolicyType::RestrictedGoods);

        if (! $current instanceof PolicyVersion) {
            throw BusinessRuleViolation::make('restricted_goods_policy_unavailable', 'restricted_goods_policy_version');
        }

        if ($policyVersionUuid === null || $policyVersionUuid === '') {
            throw BusinessRuleViolation::make('restricted_goods_ack_required', 'restricted_goods_policy_version', ['policy_version' => $current->uuid]);
        }

        if (! hash_equals($current->uuid, $policyVersionUuid)) {
            throw BusinessRuleViolation::make('restricted_goods_ack_stale', 'restricted_goods_policy_version', ['policy_version' => $current->uuid]);
        }

        return $current;
    }
}
