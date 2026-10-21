<?php

namespace App\DTOs\Booking;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Cancellation policies listing: name, service, zone, enabled flag and an "in effect during" date range.
 */
final readonly class CancellationPolicyFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<int> $categoryIds
     * @param list<int> $zoneIds
     */
    public function __construct(
        public ?string $name = null,
        public array $categoryIds = [],
        public array $zoneIds = [],
        public ?bool $active = null,
        public ?CarbonImmutable $effectiveFrom = null,
        public ?CarbonImmutable $effectiveTo = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'effective_on');

        return new self(
            name: self::stringFilter($input, 'name'),
            categoryIds: self::idListFilter($input, 'category_ids'),
            zoneIds: self::idListFilter($input, 'zone_ids'),
            active: self::boolFilter($input, 'active'),
            effectiveFrom: $from,
            effectiveTo: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->name, $this->categoryIds, $this->zoneIds, $this->active, $this->effectiveFrom ?? $this->effectiveTo]);
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'category_ids' => $this->categoryIds,
            'zone_ids' => $this->zoneIds,
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
            'effective_on' => array_filter(['from' => $this->effectiveFrom?->toDateString(), 'to' => $this->effectiveTo?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
