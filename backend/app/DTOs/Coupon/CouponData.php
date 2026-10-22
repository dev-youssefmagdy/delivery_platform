<?php

namespace App\DTOs\Coupon;

use App\Enums\Coupon\CouponScopeType;
use App\Enums\Coupon\DiscountType;
use App\Enums\Coupon\FundingSource;
use App\Http\Requests\Admin\Coupon\StoreCouponRequest;
use App\Http\Requests\Admin\Coupon\UpdateCouponRequest;
use App\Models\Coupon;
use Carbon\CarbonImmutable;

/**
 * Validated input of creating / updating a coupon. Amounts are integer minor units; a percentage arrives as a percent in the
 * form and is stored in basis points. The financial terms (discount, caps, minimum fare, funding, refund policy) become a new
 * immutable version when they change on a live coupon; everything else edits the coupon in place and only affects new uses.
 */
final readonly class CouponData
{
    /**
     * @param array<string, string|null> $title
     * @param array<string, string|null> $description
     * @param array<string, string|null> $legalTerms
     * @param array<string, list<int>> $scopes CouponScopeType value => ids
     * @param array<string, list<int>> $exclusions
     * @param list<string> $segments
     * @param list<string> $paymentMethods
     * @param list<array{day: int, from: string, to: string}> $schedule
     */
    public function __construct(
        public string $name,
        public ?string $code,
        public array $title,
        public array $description,
        public array $legalTerms,
        public string $currency,
        public DiscountType $discountType,
        public int $discountValue,
        public ?int $maxDiscountMinor,
        public ?int $minFareMinor,
        public ?int $perCustomerLimit,
        public ?int $globalLimit,
        public ?int $budgetMinor,
        public FundingSource $fundingSource,
        public int $platformBp,
        public int $providerBp,
        public int $partnerBp,
        public bool $stackable,
        public bool $restoreOnRefund,
        public array $scopes,
        public array $exclusions,
        public array $segments,
        public array $paymentMethods,
        public array $schedule,
        public ?CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt,
        public string $timezone,
        public int $priority,
        public ?string $reason,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreCouponRequest|UpdateCouponRequest $request, string $defaultCurrency): self
    {
        /** @var array<string, mixed> $d */
        $d = $request->validated();
        $current = $request->route('coupon');
        $type = DiscountType::from((string) $d['discount_type']);
        $source = FundingSource::from((string) $d['funding_source']);
        $timezone = (string) ($d['timezone'] ?? config('admin.display_timezone', 'UTC'));
        $optional = static fn (mixed $value): ?int => is_numeric($value) && $value !== '' ? (int) $value : null;
        $ids = static fn (mixed $value): array => array_values(array_unique(array_map('intval', array_filter((array) $value, 'is_numeric'))));
        // Start and end are typed in the admin display timezone (like every date in the panel); the coupon's own timezone governs its weekly schedule.
        $display = (string) config('admin.display_timezone', 'UTC');
        $local = static fn (mixed $value): ?CarbonImmutable => is_string($value) && $value !== '' ? CarbonImmutable::createFromFormat('!Y-m-d H:i', $value, $display)->utc() : null;

        [$platform, $provider, $partner] = CouponTerms::splitFor(
            $source,
            (int) round(((float) ($d['split_platform'] ?? 0)) * 100),
            (int) round(((float) ($d['split_provider'] ?? 0)) * 100),
            (int) round(((float) ($d['split_partner'] ?? 0)) * 100),
        );

        $schedule = [];

        foreach ((array) ($d['schedule'] ?? []) as $row) {
            $schedule[] = ['day' => (int) $row['day'], 'from' => (string) $row['from'], 'to' => (string) $row['to']];
        }

        $code = isset($d['code']) && trim((string) $d['code']) !== '' ? mb_strtoupper(trim((string) $d['code'])) : null;

        return new self(
            name: trim((string) $d['name']),
            code: $current instanceof Coupon ? $current->code : $code,
            title: self::translations($d['title'] ?? []),
            description: self::translations($d['description'] ?? []),
            legalTerms: self::translations($d['legal_terms'] ?? []),
            currency: $current instanceof Coupon ? $current->currency : $defaultCurrency,
            discountType: $type,
            discountValue: $type === DiscountType::Percentage ? (int) round(((float) ($d['percent'] ?? 0)) * 100) : (int) ($d['fixed_minor'] ?? 0),
            maxDiscountMinor: $type === DiscountType::Percentage ? $optional($d['max_discount_minor'] ?? null) : null,
            minFareMinor: $optional($d['min_fare_minor'] ?? null),
            perCustomerLimit: $optional($d['per_customer_limit'] ?? null),
            globalLimit: $optional($d['global_limit'] ?? null),
            budgetMinor: $optional($d['budget_minor'] ?? null),
            fundingSource: $source,
            platformBp: $platform,
            providerBp: $provider,
            partnerBp: $partner,
            stackable: (bool) ($d['stackable'] ?? false),
            restoreOnRefund: (bool) ($d['restore_on_refund'] ?? true),
            scopes: [
                CouponScopeType::ServiceFamily->value => $ids($d['family_ids'] ?? []),
                CouponScopeType::ServiceCategory->value => $ids($d['category_ids'] ?? []),
                CouponScopeType::VehicleCategory->value => $ids($d['vehicle_category_ids'] ?? []),
                CouponScopeType::City->value => $ids($d['city_ids'] ?? []),
                CouponScopeType::Zone->value => $ids($d['zone_ids'] ?? []),
            ],
            exclusions: [
                'service_category_ids' => $ids($d['excluded_category_ids'] ?? []),
                'vehicle_category_ids' => $ids($d['excluded_vehicle_category_ids'] ?? []),
            ],
            segments: array_values(array_map('strval', (array) ($d['segments'] ?? []))),
            paymentMethods: array_values(array_map('strval', (array) ($d['payment_methods'] ?? []))),
            schedule: $schedule,
            startsAt: $local($d['starts_at'] ?? null),
            endsAt: $local($d['ends_at'] ?? null),
            timezone: $timezone,
            priority: (int) ($d['priority'] ?? 0),
            reason: isset($d['reason']) && trim((string) $d['reason']) !== '' ? trim((string) $d['reason']) : null,
            lockVersion: isset($d['lock_version']) ? (int) $d['lock_version'] : null,
        );
    }

    public function terms(): CouponTerms
    {
        return new CouponTerms(
            $this->discountType,
            $this->discountValue,
            $this->maxDiscountMinor,
            $this->minFareMinor,
            $this->fundingSource,
            $this->platformBp,
            $this->providerBp,
            $this->partnerBp,
            $this->restoreOnRefund,
        );
    }

    /**
     * Columns of the coupon row that are not counters or status.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description === [] ? null : $this->description,
            'legal_terms' => $this->legalTerms === [] ? null : $this->legalTerms,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'max_discount_minor' => $this->maxDiscountMinor,
            'min_fare_minor' => $this->minFareMinor,
            'per_customer_limit' => $this->perCustomerLimit,
            'global_limit' => $this->globalLimit,
            'budget_minor' => $this->budgetMinor,
            'funding_source' => $this->fundingSource,
            'funding_split' => ['platform_bp' => $this->platformBp, 'provider_bp' => $this->providerBp, 'partner_bp' => $this->partnerBp],
            'stackable' => $this->stackable,
            'restore_on_refund' => $this->restoreOnRefund,
            'exclusions' => $this->exclusions,
            'segments' => $this->segments === [] ? null : $this->segments,
            'payment_methods' => $this->paymentMethods === [] ? null : $this->paymentMethods,
            'schedule' => $this->schedule === [] ? null : $this->schedule,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'timezone' => $this->timezone,
            'priority' => $this->priority,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private static function translations(mixed $value): array
    {
        $result = [];

        foreach ((array) $value as $locale => $text) {
            $text = is_string($text) ? trim($text) : '';

            if ($text !== '') {
                $result[(string) $locale] = $text;
            }
        }

        return $result;
    }
}
