<?php

namespace App\DTOs\Reporting;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Reporting\ReportGrouping;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the dashboard and of every report page: market-day range, timezone, city / zone / category, grouping and currency.
 * Missing range = the last 30 days; an invalid or too long range falls back to the default (the page shows an inline error for it).
 * `pii` is only honoured when a queued export replays the filters: the export queue sets it after checking `reports.export_pii`.
 */
final readonly class ReportFilterData implements FilterData
{
    use ParsesFilterInput;

    public const DEFAULT_DAYS = 30;

    /**
     * @param list<int> $cityIds
     * @param list<int> $zoneIds
     * @param list<int> $categoryIds
     */
    public function __construct(
        public ReportingRange $range,
        public array $cityIds = [],
        public array $zoneIds = [],
        public array $categoryIds = [],
        public ReportGrouping $grouping = ReportGrouping::Date,
        public ?string $currency = null,
        public bool $pii = false,
        public bool $rangeGiven = false,
        public bool $rangeRejected = false,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'range');
        $timezone = self::stringFilter($input, 'timezone', 64);
        $timezone = $timezone !== null && in_array($timezone, timezone_identifiers_list(), true) ? $timezone : null;
        $default = ReportingRange::lastDays(self::DEFAULT_DAYS, $timezone);
        $given = $from !== null || $to !== null;
        $range = new ReportingRange($from ?? ($to?->subDays(self::DEFAULT_DAYS - 1) ?? $default->from), $to ?? ($from === null ? $default->to : CarbonImmutable::parse(min($from->addDays(self::DEFAULT_DAYS - 1)->toDateString(), $default->to->toDateString()))), $timezone);
        $rejected = false;

        if (! $range->isValid()) {
            $rejected = true;
            $range = $default;
        }

        $currency = self::stringFilter($input, 'currency', 3);

        return new self(
            range: $range,
            cityIds: self::idListFilter($input, 'city_ids'),
            zoneIds: self::idListFilter($input, 'zone_ids'),
            categoryIds: self::idListFilter($input, 'category_ids'),
            grouping: ReportGrouping::tryFromValue(self::stringFilter($input, 'group_by', 12)) ?? ReportGrouping::Date,
            currency: $currency !== null && preg_match('/^[A-Za-z]{3}$/', $currency) === 1 ? strtoupper($currency) : null,
            pii: self::boolFilter($input, 'pii') === true,
            rangeGiven: $given,
            rangeRejected: $rejected,
        );
    }

    /**
     * A copy with another range (dashboard presets, previous period).
     */
    public function withRange(ReportingRange $range): self
    {
        return new self($range, $this->cityIds, $this->zoneIds, $this->categoryIds, $this->grouping, $this->currency, $this->pii, $this->rangeGiven, false);
    }

    public function withPii(bool $pii): self
    {
        return new self($this->range, $this->cityIds, $this->zoneIds, $this->categoryIds, $this->grouping, $this->currency, $pii, $this->rangeGiven, $this->rangeRejected);
    }

    public function activeCount(): int
    {
        return self::countActive([
            $this->rangeGiven ? true : null, $this->range->timezone, $this->cityIds, $this->zoneIds, $this->categoryIds,
            $this->grouping === ReportGrouping::Date ? null : $this->grouping, $this->currency,
        ]);
    }

    public function toArray(): array
    {
        return array_filter([
            'range' => ['from' => $this->range->from->toDateString(), 'to' => $this->range->to->toDateString()],
            'timezone' => $this->range->timezone,
            'city_ids' => $this->cityIds,
            'zone_ids' => $this->zoneIds,
            'category_ids' => $this->categoryIds,
            'group_by' => $this->grouping === ReportGrouping::Date ? null : $this->grouping->value,
            'currency' => $this->currency,
            'pii' => $this->pii ? '1' : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
