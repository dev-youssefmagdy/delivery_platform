<?php

namespace App\DTOs\Coupon;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Coupon\CouponStatus;
use App\Enums\Coupon\DiscountType;
use App\Enums\Coupon\FundingSource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Coupons listing (Appendix C): code, status, discount type, funding source, service family, "active on
 * date" and a validity date range (coupons whose window intersects the range).
 */
final readonly class CouponFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<CouponStatus> $statuses
     * @param list<DiscountType> $discountTypes
     * @param list<FundingSource> $fundingSources
     * @param list<int> $familyIds
     */
    public function __construct(
        public ?string $code = null,
        public array $statuses = [],
        public array $discountTypes = [],
        public array $fundingSources = [],
        public array $familyIds = [],
        public ?CarbonImmutable $activeOn = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'date_range');

        return new self(
            code: self::stringFilter($input, 'code', 40),
            statuses: self::enumListFilter($input, 'statuses', CouponStatus::class),
            discountTypes: self::enumListFilter($input, 'discount_types', DiscountType::class),
            fundingSources: self::enumListFilter($input, 'funding_sources', FundingSource::class),
            familyIds: self::idListFilter($input, 'family_ids'),
            activeOn: self::dateFilter($input, 'active_on'),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->code, $this->statuses, $this->discountTypes, $this->fundingSources, $this->familyIds, $this->activeOn, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'statuses' => array_map(static fn (CouponStatus $status): string => $status->value, $this->statuses),
            'discount_types' => array_map(static fn (DiscountType $type): string => $type->value, $this->discountTypes),
            'funding_sources' => array_map(static fn (FundingSource $source): string => $source->value, $this->fundingSources),
            'family_ids' => $this->familyIds,
            'active_on' => $this->activeOn?->toDateString(),
            'date_range' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
