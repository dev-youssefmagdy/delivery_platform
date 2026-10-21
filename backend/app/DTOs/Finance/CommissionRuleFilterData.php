<?php

namespace App\DTOs\Finance;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Finance\CommissionType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Commission rules listing: service, zone, type of the current version, enabled flag and an
 * "in effect during" date range (rules whose period intersects the range).
 */
final readonly class CommissionRuleFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<int> $categoryIds
     * @param list<int> $zoneIds
     * @param list<CommissionType> $types
     */
    public function __construct(
        public array $categoryIds = [],
        public array $zoneIds = [],
        public array $types = [],
        public ?bool $active = null,
        public ?CarbonImmutable $effectiveFrom = null,
        public ?CarbonImmutable $effectiveTo = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'effective_on');

        return new self(
            categoryIds: self::idListFilter($input, 'category_ids'),
            zoneIds: self::idListFilter($input, 'zone_ids'),
            types: self::enumListFilter($input, 'types', CommissionType::class),
            active: self::boolFilter($input, 'active'),
            effectiveFrom: $from,
            effectiveTo: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->categoryIds, $this->zoneIds, $this->types, $this->active, $this->effectiveFrom ?? $this->effectiveTo]);
    }

    public function toArray(): array
    {
        return array_filter([
            'category_ids' => $this->categoryIds,
            'zone_ids' => $this->zoneIds,
            'types' => array_map(static fn (CommissionType $type): string => $type->value, $this->types),
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
            'effective_on' => array_filter(['from' => $this->effectiveFrom?->toDateString(), 'to' => $this->effectiveTo?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
