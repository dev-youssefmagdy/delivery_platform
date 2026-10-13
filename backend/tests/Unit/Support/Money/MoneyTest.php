<?php

namespace Tests\Unit\Support\Money;

use App\Support\Money\Currency;
use App\Support\Money\CurrencyMismatchException;
use App\Support\Money\Money;
use App\Support\Money\RoundingMode;
use InvalidArgumentException;
use OverflowException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    private Currency $egp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->egp = Currency::of('EGP', 2);
    }

    public function test_it_stores_integer_minor_units(): void
    {
        $money = Money::of(1005, $this->egp);

        $this->assertSame(1005, $money->amount);
        $this->assertSame('EGP', $money->currency->code);
        $this->assertSame('10.05', $money->toDecimal());
        $this->assertSame(['amount_minor' => 1005, 'currency' => 'EGP', 'amount' => '10.05'], $money->toArray());
    }

    public function test_from_decimal_parses_exactly_without_floats(): void
    {
        $this->assertSame(1999, Money::fromDecimal('19.99', $this->egp)->amount);
        $this->assertSame(-350, Money::fromDecimal('-3.5', $this->egp)->amount);
        $this->assertSame(10000, Money::fromDecimal('100', $this->egp)->amount);
        $this->assertSame(30, Money::fromDecimal('0.3', $this->egp)->amount);
    }

    public function test_from_decimal_rejects_excess_precision_unless_rounding_is_given(): void
    {
        $this->assertSame(1001, Money::fromDecimal('10.005', $this->egp, RoundingMode::HalfUp)->amount);
        $this->assertSame(1000, Money::fromDecimal('10.005', $this->egp, RoundingMode::HalfEven)->amount);

        $this->expectException(InvalidArgumentException::class);
        Money::fromDecimal('10.005', $this->egp);
    }

    public function test_from_decimal_rejects_garbage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromDecimal('1e3', $this->egp);
    }

    public function test_add_and_subtract(): void
    {
        $a = Money::of(1000, $this->egp);
        $b = Money::of(250, $this->egp);

        $this->assertSame(1250, $a->add($b)->amount);
        $this->assertSame(1500, $a->add($b, $b)->amount);
        $this->assertSame(750, $a->subtract($b)->amount);
        $this->assertSame(-250, Money::zero($this->egp)->subtract($b)->amount);
        $this->assertSame(1000, $a->amount, 'Money is immutable');
    }

    public function test_operations_refuse_mixed_currencies(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Money::of(100, $this->egp)->add(Money::of(100, Currency::of('USD')));
    }

    public function test_multiply_by_quantity(): void
    {
        $this->assertSame(3000, Money::of(1000, $this->egp)->multiply(3)->amount);
        $this->assertSame(-3000, Money::of(1000, $this->egp)->multiply(-3)->amount);
    }

    /**
     * @return array<string, array{int, int, RoundingMode, int}>
     */
    public static function basisPointCases(): array
    {
        return [
            '10% of 100.00' => [10000, 1000, RoundingMode::HalfUp, 1000],
            '12.5% of 10.05 = 125.625 half-up' => [1005, 1250, RoundingMode::HalfUp, 126],
            '15% of 0.10 = 1.5 half-up' => [10, 1500, RoundingMode::HalfUp, 2],
            '15% of 0.10 = 1.5 half-even' => [10, 1500, RoundingMode::HalfEven, 2],
            '25% of 0.10 = 2.5 half-even' => [10, 2500, RoundingMode::HalfEven, 2],
            '25% of 0.10 = 2.5 half-up' => [10, 2500, RoundingMode::HalfUp, 3],
            '25% of 0.10 = 2.5 down' => [10, 2500, RoundingMode::Down, 2],
            '1% of 0.01 = 0.01 up' => [1, 100, RoundingMode::Up, 1],
            'negative half-up away from zero' => [-10, 2500, RoundingMode::HalfUp, -3],
            'negative down toward zero' => [-10, 2500, RoundingMode::Down, -2],
            '0% is zero' => [12345, 0, RoundingMode::HalfUp, 0],
            '100% is identity' => [12345, 10000, RoundingMode::HalfUp, 12345],
        ];
    }

    #[DataProvider('basisPointCases')]
    public function test_multiply_basis_points_with_documented_rounding(int $amount, int $bp, RoundingMode $mode, int $expected): void
    {
        $this->assertSame($expected, Money::of($amount, $this->egp)->multiplyBasisPoints($bp, $mode)->amount);
    }

    public function test_commission_example_from_the_plan_balances(): void
    {
        $fare = Money::fromDecimal('100.00', $this->egp);
        $commission = $fare->multiplyBasisPoints(1000);
        $providerShare = $fare->subtract($commission);

        $this->assertSame(1000, $commission->amount);
        $this->assertSame(9000, $providerShare->amount);
        $this->assertTrue($commission->add($providerShare)->equals($fare));
    }

    /**
     * @return array<string, array{int, list<int>, list<int>}>
     */
    public static function allocationCases(): array
    {
        return [
            'even split with remainder' => [100, [1, 1, 1], [34, 33, 33]],
            '70/30' => [1001, [70, 30], [701, 300]],
            'zero ratio gets nothing' => [5, [1, 0, 1], [3, 0, 2]],
            'negative amount' => [-100, [1, 1, 1], [-34, -33, -33]],
            'single part' => [999, [5], [999]],
            'tiny amount many parts' => [2, [1, 1, 1, 1], [1, 1, 0, 0]],
        ];
    }

    /**
     * @param list<int> $ratios
     * @param list<int> $expected
     */
    #[DataProvider('allocationCases')]
    public function test_allocate_never_loses_or_creates_minor_units(int $amount, array $ratios, array $expected): void
    {
        $parts = Money::of($amount, $this->egp)->allocate($ratios);

        $this->assertSame($expected, array_map(fn (Money $m): int => $m->amount, $parts));
        $this->assertSame($amount, array_sum(array_map(fn (Money $m): int => $m->amount, $parts)));
    }

    public function test_split(): void
    {
        $parts = Money::of(1000, $this->egp)->split(3);

        $this->assertSame([334, 333, 333], array_map(fn (Money $m): int => $m->amount, $parts));
    }

    public function test_allocate_rejects_invalid_ratios(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of(100, $this->egp)->allocate([0, 0]);
    }

    public function test_comparisons(): void
    {
        $small = Money::of(100, $this->egp);
        $big = Money::of(200, $this->egp);

        $this->assertTrue($big->greaterThan($small));
        $this->assertTrue($small->lessThan($big));
        $this->assertTrue($small->lessThanOrEqual(Money::of(100, $this->egp)));
        $this->assertTrue($small->equals(Money::of(100, $this->egp)));
        $this->assertFalse($small->equals(Money::of(100, Currency::of('USD'))));
        $this->assertSame($small, $small->min($big));
        $this->assertSame($big, $small->max($big));
        $this->assertTrue(Money::zero($this->egp)->isZero());
        $this->assertTrue($small->negate()->isNegative());
        $this->assertSame(100, $small->negate()->absolute()->amount);
    }

    public function test_overflow_is_detected(): void
    {
        $this->expectException(OverflowException::class);

        Money::of(PHP_INT_MAX, $this->egp)->add(Money::of(1, $this->egp));
    }

    public function test_currency_exponent_drives_decimal_conversion(): void
    {
        $kwd = Currency::of('KWD', 3);
        $jpy = Currency::of('JPY', 0);

        $this->assertSame('1.234', Money::of(1234, $kwd)->toDecimal());
        $this->assertSame(1234, Money::fromDecimal('1.234', $kwd)->amount);
        $this->assertSame('1234', Money::of(1234, $jpy)->toDecimal());
    }

    public function test_currency_validates_code_and_exponent(): void
    {
        $this->assertSame('EGP', Currency::of('egp')->code);

        $this->expectException(InvalidArgumentException::class);
        Currency::of('EG');
    }
}
