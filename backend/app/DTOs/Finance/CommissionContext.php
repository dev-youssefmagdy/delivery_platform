<?php

namespace App\DTOs\Finance;

use Carbon\CarbonInterface;

/**
 * What a commission rule is matched against: the booking's service, zone, currency and date, and (when they exist)
 * the provider's tier and the campaign the booking belongs to.
 */
final readonly class CommissionContext
{
    public function __construct(
        public int $serviceCategoryId,
        public ?int $serviceZoneId,
        public string $currency,
        public CarbonInterface $at,
        public ?string $providerTier = null,
        public ?string $campaignCode = null,
    ) {}
}
