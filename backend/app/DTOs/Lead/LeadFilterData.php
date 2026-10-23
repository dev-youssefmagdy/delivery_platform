<?php

namespace App\DTOs\Lead;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Lead\LeadStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the leads listing: city, service interest, status, created range.
 */
final readonly class LeadFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<int> $cityIds
     * @param list<int> $serviceCategoryIds
     * @param list<LeadStatus> $statuses
     */
    public function __construct(
        public array $cityIds = [],
        public array $serviceCategoryIds = [],
        public array $statuses = [],
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created');

        return new self(
            cityIds: self::idListFilter($input, 'city_ids'),
            serviceCategoryIds: self::idListFilter($input, 'category_ids'),
            statuses: self::enumListFilter($input, 'statuses', LeadStatus::class),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->cityIds, $this->serviceCategoryIds, $this->statuses, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'city_ids' => $this->cityIds,
            'category_ids' => $this->serviceCategoryIds,
            'statuses' => array_map(static fn (LeadStatus $case): string => $case->value, $this->statuses),
            'created' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== []);
    }
}
