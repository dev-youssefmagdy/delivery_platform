<?php

namespace App\DTOs\Provider;

use App\Enums\Provider\ProviderType;
use Carbon\CarbonImmutable;

/**
 * Validated input of "start / update my application". Null means "not sent, keep the current value", so the
 * wizard can save its profile and services steps separately.
 */
final readonly class ProviderApplicationData
{
    /**
     * @param list<int>|null $serviceCategoryIds
     */
    public function __construct(
        public ?ProviderType $providerType = null,
        public ?int $cityId = null,
        public ?string $name = null,
        public ?CarbonImmutable $dateOfBirth = null,
        public ?array $serviceCategoryIds = null,
    ) {}
}
