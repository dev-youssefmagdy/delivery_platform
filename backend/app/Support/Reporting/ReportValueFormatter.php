<?php

namespace App\Support\Reporting;

use App\Enums\Reporting\KpiUnit;
use App\Enums\Reporting\ReportColumnType;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\Support\Money\UnknownCurrencyException;
use NumberFormatter;

/**
 * Locale-aware formatting of report and dashboard values (Arabic-Indic digits in the UI, Latin digits in CSV). Missing values are an em dash,
 * never a zero, so "no data" is not mistaken for "zero".
 */
final class ReportValueFormatter
{
    public function __construct(private readonly MoneyFormatter $money, private readonly CurrencyRegistry $currencies) {}

    public function format(ReportColumnType $type, mixed $value, ?string $currency = null, bool $latinDigits = false): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($type) {
            ReportColumnType::Text, ReportColumnType::Date => (string) $value,
            ReportColumnType::Integer => $this->number((float) $value, 0, $latinDigits),
            ReportColumnType::Decimal => $this->number((float) $value, 2, $latinDigits),
            ReportColumnType::Percent => $this->number((float) $value, 1, $latinDigits).'%',
            ReportColumnType::Seconds => $this->seconds((int) round((float) $value), $latinDigits),
            ReportColumnType::Money => $this->moneyValue((int) round((float) $value), $currency, $latinDigits),
        };
    }

    public function kpi(KpiUnit $unit, int|float|null $value, ?string $currency = null): string
    {
        return $this->format(match ($unit) {
            KpiUnit::Count => ReportColumnType::Integer,
            KpiUnit::Percent => ReportColumnType::Percent,
            KpiUnit::Money => ReportColumnType::Money,
            KpiUnit::Seconds => ReportColumnType::Seconds,
        }, $value, $currency);
    }

    public function moneyValue(int $minor, ?string $currency, bool $latinDigits = false): string
    {
        try {
            return $this->money->format(Money::of($minor, $this->currencies->get($currency ?? (string) config('admin.default_currency', 'EGP'))), latinDigits: $latinDigits);
        } catch (UnknownCurrencyException) {
            return $this->number($minor / 100, 2, $latinDigits).' '.($currency ?? '');
        }
    }

    public function number(float $value, int $decimals, bool $latinDigits = false): string
    {
        $locale = app()->getLocale();
        $formatter = new NumberFormatter($latinDigits ? "{$locale}@numbers=latn" : $locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);

        return (string) $formatter->format($value);
    }

    public function seconds(int $seconds, bool $latinDigits = false): string
    {
        if ($seconds < 120) {
            return __('reporting.units.seconds', ['n' => $this->number($seconds, 0, $latinDigits)]);
        }

        if ($seconds < 7200) {
            return __('reporting.units.minutes', ['n' => $this->number($seconds / 60, 1, $latinDigits)]);
        }

        return __('reporting.units.hours', ['n' => $this->number($seconds / 3600, 1, $latinDigits)]);
    }
}
