<?php

namespace App\Support\Money;

use NumberFormatter;

/**
 * Locale-aware display formatting. Works on integers only (no float conversion), so large amounts keep
 * every minor unit. Rounding never happens here: amounts are already whole minor units (ADR-04).
 *
 * ar: "١٬٢٣٤٫٥٠ ج.م" (Arabic-Indic digits unless $latinDigits) · en: "EGP 1,234.50".
 */
final class MoneyFormatter
{
    public function format(Money $money, ?string $locale = null, bool $latinDigits = false, bool $withSymbol = true): string
    {
        $locale ??= app()->getLocale();
        $number = $this->formatNumber($money, $locale, $latinDigits);

        if (! $withSymbol) {
            return $number;
        }

        $symbol = $money->currency->symbol($this->language($locale));

        return $this->symbolAfterNumber($locale) ? "{$number} {$symbol}" : "{$symbol} {$number}";
    }

    public function formatNumber(Money $money, ?string $locale = null, bool $latinDigits = false): string
    {
        $locale ??= app()->getLocale();
        $icuLocale = $latinDigits ? "{$locale}@numbers=latn" : $locale;
        $currency = $money->currency;

        $absolute = abs($money->amount);
        $whole = intdiv($absolute, $currency->subunit());
        $fraction = $absolute % $currency->subunit();

        $grouped = new NumberFormatter($icuLocale, NumberFormatter::DECIMAL);
        $grouped->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);
        $result = (string) $grouped->format($whole);

        if ($currency->exponent > 0) {
            $digits = new NumberFormatter($icuLocale, NumberFormatter::DECIMAL);
            $digits->setAttribute(NumberFormatter::GROUPING_USED, 0);
            $digits->setAttribute(NumberFormatter::MIN_INTEGER_DIGITS, $currency->exponent);
            $digits->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);

            $result .= $grouped->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL).$digits->format($fraction);
        }

        if ($money->amount < 0) {
            $result = $grouped->getSymbol(NumberFormatter::MINUS_SIGN_SYMBOL).$result;
        }

        return $result;
    }

    private function symbolAfterNumber(string $locale): bool
    {
        return $this->language($locale) === 'ar';
    }

    private function language(string $locale): string
    {
        return strtolower(substr($locale, 0, 2));
    }
}
