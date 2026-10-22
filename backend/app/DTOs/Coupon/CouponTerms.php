<?php

namespace App\DTOs\Coupon;

use App\DTOs\Finance\DiscountFundingSplit;
use App\Enums\Coupon\DiscountType;
use App\Enums\Coupon\FundingSource;
use App\Support\Money\Money;
use InvalidArgumentException;

/**
 * The FINANCIAL terms of a coupon: what the discount is and who pays it. These are what a `coupon_versions` row freezes
 * (SRS §16.8: prevent editing a live coupon in a way that retroactively changes completed bookings). A reservation keeps
 * the version it was created with, so changing the value of a live coupon never changes a trip already in flight.
 *
 * Pure: `discountFor()` has no clock and no database.
 */
final readonly class CouponTerms
{
    public function __construct(
        public DiscountType $type,
        public int $value,
        public ?int $maxDiscountMinor,
        public ?int $minFareMinor,
        public FundingSource $fundingSource,
        public int $platformBp,
        public int $providerBp,
        public int $partnerBp,
        public bool $restoreOnRefund,
    ) {
        if ($value < 0 || ($type === DiscountType::Percentage && $value > 10_000)) {
            throw new InvalidArgumentException('A percentage coupon is 0..10000 basis points; a fixed amount is not negative.');
        }

        if ($platformBp + $providerBp + $partnerBp !== 10_000 || min($platformBp, $providerBp, $partnerBp) < 0) {
            throw new InvalidArgumentException('A funding split must add up to 10000 basis points.');
        }
    }

    /**
     * Default split of a single funder; `split` supplies its own basis points.
     */
    public static function splitFor(FundingSource $source, int $platformBp = 0, int $providerBp = 0, int $partnerBp = 0): array
    {
        return match ($source) {
            FundingSource::Platform => [10_000, 0, 0],
            FundingSource::Provider => [0, 10_000, 0],
            FundingSource::Partner => [0, 0, 10_000],
            FundingSource::Split => [$platformBp, $providerBp, $partnerBp],
        };
    }

    /**
     * The discount this coupon grants on a fare (the provider gross: fare + add-ons; never the fee, tax or tip).
     * Zero below the minimum fare. Percentages round half-up once; the cap and the fare itself bound the result.
     */
    public function discountFor(Money $fare): Money
    {
        $zero = Money::zero($fare->currency);

        if ($fare->isNegative() || ($this->minFareMinor !== null && $fare->amount < $this->minFareMinor)) {
            return $zero;
        }

        $discount = $this->type === DiscountType::Percentage
            ? $fare->multiplyBasisPoints($this->value)
            : Money::of($this->value, $fare->currency);

        if ($this->maxDiscountMinor !== null) {
            $discount = $discount->min(Money::of($this->maxDiscountMinor, $fare->currency));
        }

        return $discount->min($fare);
    }

    public function funding(): DiscountFundingSplit
    {
        return new DiscountFundingSplit($this->platformBp, $this->providerBp, $this->partnerBp);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'discount_type' => $this->type->value,
            'discount_value' => $this->value,
            'max_discount_minor' => $this->maxDiscountMinor,
            'min_fare_minor' => $this->minFareMinor,
            'funding_source' => $this->fundingSource->value,
            'funding_split' => ['platform_bp' => $this->platformBp, 'provider_bp' => $this->providerBp, 'partner_bp' => $this->partnerBp],
            'restore_on_refund' => $this->restoreOnRefund,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromSnapshot(array $data): self
    {
        $split = (array) ($data['funding_split'] ?? []);

        return new self(
            type: DiscountType::from((string) $data['discount_type']),
            value: (int) $data['discount_value'],
            maxDiscountMinor: isset($data['max_discount_minor']) ? (int) $data['max_discount_minor'] : null,
            minFareMinor: isset($data['min_fare_minor']) ? (int) $data['min_fare_minor'] : null,
            fundingSource: FundingSource::from((string) $data['funding_source']),
            platformBp: (int) ($split['platform_bp'] ?? 10_000),
            providerBp: (int) ($split['provider_bp'] ?? 0),
            partnerBp: (int) ($split['partner_bp'] ?? 0),
            restoreOnRefund: (bool) ($data['restore_on_refund'] ?? true),
        );
    }

    public function equals(self $other): bool
    {
        return $this->toSnapshot() === $other->toSnapshot();
    }
}
