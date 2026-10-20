<?php

namespace App\DTOs\Provider;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ProviderType;
use Illuminate\Http\Request;

/**
 * Filters of the Providers listing (Plan Appendix C: uuid/phone/name, status, provider type, service category,
 * city, online now, document expiring within N days).
 */
final readonly class ProviderFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<ProviderStatus> $statuses
     * @param list<ProviderType> $providerTypes
     * @param list<int> $serviceCategoryIds
     * @param list<int> $cityIds
     */
    public function __construct(
        public ?string $search = null,
        public array $statuses = [],
        public array $providerTypes = [],
        public array $serviceCategoryIds = [],
        public array $cityIds = [],
        public ?bool $online = null,
        public ?int $documentExpiringWithinDays = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        $days = self::intFilter($input, 'expiring_within_days');

        return new self(
            search: self::stringFilter($input, 'search', 100),
            statuses: self::enumListFilter($input, 'statuses', ProviderStatus::class),
            providerTypes: self::enumListFilter($input, 'provider_types', ProviderType::class),
            serviceCategoryIds: self::idListFilter($input, 'service_category_ids'),
            cityIds: self::idListFilter($input, 'city_ids'),
            online: self::boolFilter($input, 'online'),
            documentExpiringWithinDays: $days !== null && $days >= 1 && $days <= 365 ? $days : null,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->statuses, $this->providerTypes, $this->serviceCategoryIds, $this->cityIds, $this->online, $this->documentExpiringWithinDays]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'statuses' => array_map(static fn (ProviderStatus $status): string => $status->value, $this->statuses),
            'provider_types' => array_map(static fn (ProviderType $type): string => $type->value, $this->providerTypes),
            'service_category_ids' => $this->serviceCategoryIds,
            'city_ids' => $this->cityIds,
            'online' => $this->online === null ? null : ($this->online ? '1' : '0'),
            'expiring_within_days' => $this->documentExpiringWithinDays,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
