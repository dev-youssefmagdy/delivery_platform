<?php

namespace App\DTOs\Finance;

use App\Enums\Finance\DiscountFunding;
use InvalidArgumentException;

/**
 * Who pays a discount, in basis points that add up to 10 000 (so the allocation never loses a minor unit).
 */
final readonly class DiscountFundingSplit
{
    public function __construct(public int $platformBp, public int $providerBp, public int $partnerBp)
    {
        if ($platformBp < 0 || $providerBp < 0 || $partnerBp < 0 || $platformBp + $providerBp + $partnerBp !== 10_000) {
            throw new InvalidArgumentException('A funding split must be non-negative and add up to 10000 basis points.');
        }
    }

    public static function of(DiscountFunding $funder): self
    {
        return match ($funder) {
            DiscountFunding::Platform => new self(10_000, 0, 0),
            DiscountFunding::Provider => new self(0, 10_000, 0),
            DiscountFunding::Partner => new self(0, 0, 10_000),
        };
    }

    /**
     * @return list<int> ratios in the order platform, provider, partner (for Money::allocate)
     */
    public function ratios(): array
    {
        return [$this->platformBp, $this->providerBp, $this->partnerBp];
    }
}
