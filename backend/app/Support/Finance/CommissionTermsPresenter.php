<?php

namespace App\Support\Finance;

use App\DTOs\Finance\CommissionTerms;
use App\Enums\Finance\CommissionType;
use App\Models\CommissionRuleVersion;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;

/**
 * Human text for commission terms ("10%", "5.00 EGP", "10% + 2.00 EGP, min 3.00 EGP") in the current locale, for the
 * admin listings and pages. Display only: amounts are never parsed back from it.
 */
final class CommissionTermsPresenter
{
    public function __construct(private readonly CurrencyRegistry $currencies, private readonly MoneyFormatter $formatter) {}

    public function describe(CommissionTerms|CommissionRuleVersion $terms, string $currency): string
    {
        $type = $terms->type;
        $percentBp = $terms instanceof CommissionTerms ? $terms->percentBp : $terms->percent_bp;
        $fixed = $terms instanceof CommissionTerms ? $terms->fixedMinor : $terms->fixed_minor;
        $min = $terms instanceof CommissionTerms ? $terms->minMinor : $terms->min_minor;
        $max = $terms instanceof CommissionTerms ? $terms->maxMinor : $terms->max_minor;

        $parts = [];

        if ($type !== CommissionType::Fixed) {
            $parts[] = $this->percent($percentBp);
        }

        if ($type !== CommissionType::Percentage) {
            $parts[] = $this->money($fixed, $currency);
        }

        $text = implode(' + ', $parts);

        if ($min !== null) {
            $text .= ', '.__('admin.commission_rules.min_short', ['amount' => $this->money($min, $currency)]);
        }

        if ($max !== null) {
            $text .= ', '.__('admin.commission_rules.max_short', ['amount' => $this->money($max, $currency)]);
        }

        return $text;
    }

    public function percent(int $basisPoints): string
    {
        return rtrim(rtrim(number_format($basisPoints / 100, 2, '.', ''), '0'), '.').'%';
    }

    public function money(int $minor, string $currency): string
    {
        return $this->formatter->format(Money::of($minor, $this->currencies->get($currency)));
    }
}
