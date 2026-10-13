<?php

namespace App\Support\Money;

use InvalidArgumentException;
use JsonSerializable;
use OverflowException;

/**
 * Immutable money amount in integer minor units (ADR-04). Never use floats for money.
 *
 * Arithmetic that can produce fractions (basis points, ratios) is done with bcmath on decimal strings,
 * then rounded once to a whole minor unit using an explicit RoundingMode (default HalfUp).
 */
final readonly class Money implements JsonSerializable
{
    public const BASIS_POINTS = 10_000;

    public function __construct(public int $amount, public Currency $currency) {}

    public static function of(int $minor, Currency $currency): self
    {
        return new self($minor, $currency);
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    /**
     * Parse a decimal string ("12.50", "-3", "0.005") in major units. Rejects more fraction digits than the
     * currency exponent unless a rounding mode is given.
     */
    public static function fromDecimal(string $decimal, Currency $currency, ?RoundingMode $rounding = null): self
    {
        $decimal = trim($decimal);

        if (preg_match('/^-?\d+(\.\d+)?$/', $decimal) !== 1) {
            throw new InvalidArgumentException("Invalid decimal amount [{$decimal}].");
        }

        $fraction = str_contains($decimal, '.') ? strlen(explode('.', $decimal)[1]) : 0;

        if ($fraction > $currency->exponent && $rounding === null) {
            throw new InvalidArgumentException("Amount [{$decimal}] has more precision than {$currency->code} allows.");
        }

        $minor = bcmul($decimal, (string) $currency->subunit(), max($fraction, 0));

        return new self(self::roundToInt($minor, $rounding ?? RoundingMode::HalfUp), $currency);
    }

    public function add(self ...$others): self
    {
        $sum = $this->amount;

        foreach ($others as $other) {
            $this->assertSameCurrency($other);
            $sum = self::checkedInt(bcadd((string) $sum, (string) $other->amount));
        }

        return new self($sum, $this->currency);
    }

    public function subtract(self ...$others): self
    {
        $result = $this->amount;

        foreach ($others as $other) {
            $this->assertSameCurrency($other);
            $result = self::checkedInt(bcsub((string) $result, (string) $other->amount));
        }

        return new self($result, $this->currency);
    }

    /**
     * Multiply by a whole number (quantity).
     */
    public function multiply(int $factor): self
    {
        return new self(self::checkedInt(bcmul((string) $this->amount, (string) $factor)), $this->currency);
    }

    /**
     * Percentage in basis points: 1000 bp = 10%. Result is rounded once with $rounding.
     * Example: 10.05 EGP (1005) × 1250 bp = 125.625 → 126 minor (HalfUp) / 126 (HalfEven, .625 > .5).
     */
    public function multiplyBasisPoints(int $basisPoints, RoundingMode $rounding = RoundingMode::HalfUp): self
    {
        $exact = bcdiv(bcmul((string) $this->amount, (string) $basisPoints), (string) self::BASIS_POINTS, 6);

        return new self(self::roundToInt($exact, $rounding), $this->currency);
    }

    /**
     * Split into parts proportional to $ratios without losing or creating minor units.
     * Remainders are distributed one unit at a time to the earliest parts (largest-remainder friendly,
     * deterministic). Σ(result) === $this->amount always holds.
     *
     * @param list<int> $ratios non-negative integers, at least one positive
     * @return list<self>
     */
    public function allocate(array $ratios): array
    {
        if ($ratios === []) {
            throw new InvalidArgumentException('Cannot allocate to an empty list of ratios.');
        }

        $total = 0;

        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw new InvalidArgumentException('Allocation ratios must be non-negative.');
            }
            $total += $ratio;
        }

        if ($total === 0) {
            throw new InvalidArgumentException('At least one allocation ratio must be positive.');
        }

        $sign = $this->amount < 0 ? -1 : 1;
        $absolute = abs($this->amount);
        $parts = [];
        $remainder = $absolute;

        foreach ($ratios as $ratio) {
            $share = (int) bcdiv(bcmul((string) $absolute, (string) $ratio), (string) $total, 0);
            $parts[] = $share;
            $remainder -= $share;
        }

        for ($i = 0; $remainder > 0; $i = ($i + 1) % count($parts)) {
            if ($ratios[$i] === 0) {
                continue;
            }
            $parts[$i]++;
            $remainder--;
        }

        return array_map(fn (int $part): self => new self($sign * $part, $this->currency), $parts);
    }

    /**
     * Split evenly into $count parts (remainder to the first parts).
     *
     * @return list<self>
     */
    public function split(int $count): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Split count must be at least 1.');
        }

        return $this->allocate(array_fill(0, $count, 1));
    }

    public function negate(): self
    {
        return new self(-$this->amount, $this->currency);
    }

    public function absolute(): self
    {
        return new self(abs($this->amount), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function compare(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->amount <=> $other->amount;
    }

    public function equals(self $other): bool
    {
        return $this->currency->equals($other->currency) && $this->amount === $other->amount;
    }

    public function greaterThan(self $other): bool
    {
        return $this->compare($other) > 0;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->compare($other) >= 0;
    }

    public function lessThan(self $other): bool
    {
        return $this->compare($other) < 0;
    }

    public function lessThanOrEqual(self $other): bool
    {
        return $this->compare($other) <= 0;
    }

    public function min(self $other): self
    {
        return $this->lessThanOrEqual($other) ? $this : $other;
    }

    public function max(self $other): self
    {
        return $this->greaterThanOrEqual($other) ? $this : $other;
    }

    /**
     * Exact decimal string in major units, e.g. 1005 EGP → "10.05". No rounding, no localisation.
     */
    public function toDecimal(): string
    {
        return bcdiv((string) $this->amount, (string) $this->currency->subunit(), $this->currency->exponent);
    }

    /**
     * @return array{amount_minor: int, currency: string, amount: string}
     */
    public function toArray(): array
    {
        return [
            'amount_minor' => $this->amount,
            'currency' => $this->currency->code,
            'amount' => $this->toDecimal(),
        ];
    }

    /**
     * @return array{amount_minor: int, currency: string, amount: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function assertSameCurrency(self $other): void
    {
        if (! $this->currency->equals($other->currency)) {
            throw CurrencyMismatchException::between($this->currency, $other->currency);
        }
    }

    /**
     * Round a decimal string to an integer using $mode.
     */
    private static function roundToInt(string $decimal, RoundingMode $mode): int
    {
        $negative = str_starts_with($decimal, '-');
        $unsigned = ltrim($decimal, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $fraction = rtrim($fraction, '0');
        $whole = $whole === '' ? '0' : $whole;

        if ($fraction === '') {
            return self::checkedInt(($negative ? '-' : '').$whole);
        }

        $half = bccomp('0.'.$fraction, '0.5', strlen($fraction));

        $roundAway = match ($mode) {
            RoundingMode::Down => false,
            RoundingMode::Up => true,
            RoundingMode::HalfUp => $half >= 0,
            RoundingMode::HalfEven => $half > 0 || ($half === 0 && ((int) substr($whole, -1)) % 2 === 1),
        };

        if ($roundAway) {
            $whole = bcadd($whole, '1');
        }

        return self::checkedInt(($negative && $whole !== '0' ? '-' : '').$whole);
    }

    private static function checkedInt(string $value): int
    {
        if (bccomp($value, (string) PHP_INT_MAX) > 0 || bccomp($value, (string) PHP_INT_MIN) < 0) {
            throw new OverflowException("Money amount [{$value}] overflows a 64-bit integer.");
        }

        return (int) $value;
    }
}
