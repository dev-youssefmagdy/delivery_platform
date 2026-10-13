<?php

namespace Tests\Unit\Support\Money;

use App\Support\Money\Currency;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use PHPUnit\Framework\TestCase;

class MoneyFormatterTest extends TestCase
{
    private Currency $egp;

    private MoneyFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->egp = Currency::of('EGP', 2, ['ar' => 'ج.م', 'en' => 'EGP']);
        $this->formatter = new MoneyFormatter;
    }

    public function test_english_formatting(): void
    {
        $this->assertSame('EGP 1,234.50', $this->formatter->format(Money::of(123450, $this->egp), 'en'));
        $this->assertSame('EGP 0.05', $this->formatter->format(Money::of(5, $this->egp), 'en'));
        $this->assertSame('1,234.50', $this->formatter->formatNumber(Money::of(123450, $this->egp), 'en'));
    }

    public function test_arabic_formatting_uses_arabic_indic_digits_and_trailing_symbol(): void
    {
        $this->assertSame('١٬٢٣٤٫٥٠ ج.م', $this->formatter->format(Money::of(123450, $this->egp), 'ar'));
    }

    public function test_arabic_formatting_with_latin_digits(): void
    {
        $formatted = $this->formatter->format(Money::of(123450, $this->egp), 'ar', latinDigits: true);

        $this->assertStringEndsWith(' ج.م', $formatted);
        $this->assertMatchesRegularExpression('/^1.234.50 /u', $formatted);
    }

    public function test_negative_amounts_and_large_values_keep_every_minor_unit(): void
    {
        $this->assertSame('EGP -10.05', $this->formatter->format(Money::of(-1005, $this->egp), 'en'));
        $this->assertSame('EGP 92,233,720,368,547,758.07', $this->formatter->format(Money::of(PHP_INT_MAX, $this->egp), 'en'));
    }

    public function test_zero_exponent_currency_has_no_decimal_part(): void
    {
        $jpy = Currency::of('JPY', 0, ['en' => '¥']);

        $this->assertSame('¥ 1,500', $this->formatter->format(Money::of(1500, $jpy), 'en'));
    }

    public function test_symbol_falls_back_to_code(): void
    {
        $this->assertSame('USD 1.00', $this->formatter->format(Money::of(100, Currency::of('USD')), 'en'));
    }
}
