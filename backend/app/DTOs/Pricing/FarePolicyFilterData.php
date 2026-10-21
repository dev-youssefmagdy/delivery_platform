<?php

namespace App\DTOs\Pricing;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Fare policies listing: category, zone, enabled flag and "in effect on" date.
 */
final readonly class FarePolicyFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<int> $categoryIds
     * @param list<int> $zoneIds
     */
    public function __construct(
        public array $categoryIds = [],
        public array $zoneIds = [],
        public ?bool $active = null,
        public ?CarbonImmutable $effectiveOn = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(
            categoryIds: self::idListFilter($input, 'category_ids'),
            zoneIds: self::idListFilter($input, 'zone_ids'),
            active: self::boolFilter($input, 'active'),
            effectiveOn: self::dateFilter($input, 'effective_on'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->categoryIds, $this->zoneIds, $this->active, $this->effectiveOn]);
    }

    public function toArray(): array
    {
        return array_filter([
            'category_ids' => $this->categoryIds,
            'zone_ids' => $this->zoneIds,
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
            'effective_on' => $this->effectiveOn?->toDateString(),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
