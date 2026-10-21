<?php

namespace App\Services\Pricing;

use App\DTOs\Pricing\FarePolicyValues;
use App\DTOs\Pricing\PriceFactorLine;
use App\DTOs\Pricing\PriceGuidance;
use App\Enums\Pricing\PriceFactor;
use App\Enums\Pricing\SurchargeMode;
use App\Support\Money\Currency;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;

/**
 * Reference price calculator, formula V1 (SRS §9.1, I12): the only producer of price guidance.
 *
 *   subtotal    = base + per-km x distance + per-minute x duration + stops x stop fee + attribute surcharges
 *   recommended = max(subtotal, minimum fare), rounded half-up to the policy step
 *   low / high  = the policy's percentages of that recommendation, rounded the same way
 *
 * Pure: the same values and inputs always give the same answer, nothing is read from the clock, the market or the
 * supply side. There is intentionally no surge / demand / time-of-day multiplier here or anywhere else (SRS §9.1).
 */
final class ReferencePriceCalculator
{
    private const MAX_SURCHARGE_UNITS = 1000;

    public function __construct(private readonly CurrencyRegistry $currencies) {}

    /**
     * @param array<string, mixed> $serviceDetails the booking's validated answers
     */
    public function calculate(FarePolicyValues $policy, int $distanceMeters, int $durationSeconds, int $stopCount, array $serviceDetails = [], ?int $farePolicyId = null): PriceGuidance
    {
        $currency = $this->currencies->get($policy->currency);
        $lines = [];

        $lines[] = new PriceFactorLine(PriceFactor::Base, Money::of($policy->baseMinor, $currency));
        $lines[] = new PriceFactorLine(
            PriceFactor::Distance,
            Money::of(self::ratio($policy->perKmMinor, $distanceMeters, 1000), $currency),
            self::decimal($distanceMeters, 1000),
            unit: 'km',
        );
        $lines[] = new PriceFactorLine(
            PriceFactor::Duration,
            Money::of(self::ratio($policy->perMinMinor, $durationSeconds, 60), $currency),
            self::decimal($durationSeconds, 60),
            unit: 'min',
        );

        if ($stopCount > 0 && $policy->stopFeeMinor > 0) {
            $lines[] = new PriceFactorLine(PriceFactor::Stops, Money::of($policy->stopFeeMinor * $stopCount, $currency), (string) $stopCount);
        }

        foreach ($policy->surcharges as $index => $rule) {
            $line = $this->surcharge($policy, $index, $rule, $serviceDetails, $currency);

            if ($line !== null) {
                $lines[] = $line;
            }
        }

        $subtotal = Money::zero($currency)->add(...array_map(static fn (PriceFactorLine $line): Money => $line->amount, $lines));

        if ($subtotal->amount < $policy->minFareMinor) {
            $lines[] = new PriceFactorLine(PriceFactor::MinimumFare, Money::of($policy->minFareMinor - $subtotal->amount, $currency));
            $subtotal = Money::of($policy->minFareMinor, $currency);
        }

        $recommended = Money::of(self::roundToStep($subtotal->amount, $policy->roundingStepMinor), $currency);
        $low = Money::of(self::roundToStep($subtotal->multiplyBasisPoints($policy->rangeLowBp)->amount, $policy->roundingStepMinor), $currency);
        $high = Money::of(self::roundToStep($subtotal->multiplyBasisPoints($policy->rangeHighBp)->amount, $policy->roundingStepMinor), $currency);

        return new PriceGuidance(
            low: $low->min($recommended),
            recommended: $recommended,
            high: $high->amount < $recommended->amount ? $recommended : $high,
            factors: $lines,
            formula: $policy->formula,
            farePolicyId: $farePolicyId ?? $policy->policyId,
            distanceMeters: $distanceMeters,
            durationSeconds: $durationSeconds,
            stopCount: $stopCount,
        );
    }

    /**
     * @param array{field: string, mode: string, amount_minor: int, match?: string|null, label?: array<string, string|null>} $rule
     * @param array<string, mixed> $details
     */
    private function surcharge(FarePolicyValues $policy, int $index, array $rule, array $details, Currency $currency): ?PriceFactorLine
    {
        $value = $details[$rule['field']] ?? null;
        $amount = (int) $rule['amount_minor'];
        $label = $rule['label'] ?? null;

        if ($amount <= 0) {
            return null;
        }

        if ($policy->surchargeMode($index) === SurchargeMode::PerUnit) {
            if (! is_numeric($value) || (int) $value <= 0) {
                return null;
            }

            $units = min((int) $value, self::MAX_SURCHARGE_UNITS);

            return new PriceFactorLine(PriceFactor::Surcharge, Money::of($amount * $units, $currency), (string) $units, $label);
        }

        return self::matches($value, $rule['match'] ?? null)
            ? new PriceFactorLine(PriceFactor::Surcharge, Money::of($amount, $currency), null, $label)
            : null;
    }

    private static function matches(mixed $value, ?string $match): bool
    {
        if ($match !== null && $match !== '') {
            $answers = is_array($value) ? $value : [$value];

            foreach ($answers as $answer) {
                if (is_scalar($answer) && (string) $answer === $match) {
                    return true;
                }
            }

            return false;
        }

        return match (true) {
            is_bool($value) => $value,
            is_array($value) => $value !== [],
            is_numeric($value) => (float) $value > 0,
            is_string($value) => ! in_array(strtolower(trim($value)), ['', 'false', 'no', '0'], true),
            default => false,
        };
    }

    /**
     * unit x quantity / divisor, rounded half-up to a whole minor unit (integer math: no floats near money).
     */
    private static function ratio(int $unitMinor, int $quantity, int $divisor): int
    {
        return intdiv($unitMinor * max($quantity, 0) + intdiv($divisor, 2), $divisor);
    }

    private static function decimal(int $quantity, int $divisor): string
    {
        $tenths = intdiv(max($quantity, 0) * 10 + intdiv($divisor, 2), $divisor);

        return intdiv($tenths, 10).'.'.($tenths % 10);
    }

    public static function roundToStep(int $amount, int $step): int
    {
        if ($step <= 1) {
            return max($amount, 0);
        }

        return intdiv(max($amount, 0) + intdiv($step, 2), $step) * $step;
    }
}
