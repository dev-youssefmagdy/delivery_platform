<?php

namespace App\DTOs\Geography;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Geo\ZoneStatus;
use Illuminate\Http\Request;

/**
 * Filters of the Service Zones listing (Plan Appendix C: city, status, category availability).
 */
final readonly class ServiceZoneFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<int> $cityIds
     * @param list<ZoneStatus> $statuses
     * @param list<int> $categoryIds zones where ANY of these categories is enabled
     */
    public function __construct(
        public ?string $search = null,
        public array $cityIds = [],
        public array $statuses = [],
        public array $categoryIds = [],
        public ?bool $restrictedPickup = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(
            search: self::stringFilter($input, 'search'),
            cityIds: self::idListFilter($input, 'city_ids'),
            statuses: self::enumListFilter($input, 'statuses', ZoneStatus::class),
            categoryIds: self::idListFilter($input, 'category_ids'),
            restrictedPickup: self::boolFilter($input, 'restricted_pickup'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->cityIds, $this->statuses, $this->categoryIds, $this->restrictedPickup]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'city_ids' => $this->cityIds,
            'statuses' => array_map(static fn (ZoneStatus $status): string => $status->value, $this->statuses),
            'category_ids' => $this->categoryIds,
            'restricted_pickup' => $this->restrictedPickup === null ? null : ($this->restrictedPickup ? '1' : '0'),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
