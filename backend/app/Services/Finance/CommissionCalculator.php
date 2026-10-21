<?php

namespace App\Services\Finance;

use App\DTOs\Finance\CommissionTerms;
use App\Support\Money\Money;

/**
 * Pure commission arithmetic for one set of terms (ADR-18, SRS §12.4). The base is the agreed fare plus accepted
 * add-ons, before any customer discount. A percentage is rounded once (half up) in minor units; a hybrid adds the
 * fixed part; the result is clamped to the version's minimum and maximum and never exceeds the base itself.
 */
final class CommissionCalculator
{
    public function commission(CommissionTerms $terms, Money $base): Money
    {
        $currency = $base->currency;

        if (! $base->isPositive()) {
            return Money::zero($currency);
        }

        $amount = Money::zero($currency);

        if ($terms->type->usesPercent()) {
            $amount = $amount->add($base->multiplyBasisPoints($terms->percentBp));
        }

        if ($terms->type->usesFixed()) {
            $amount = $amount->add(Money::of($terms->fixedMinor, $currency));
        }

        if ($terms->minMinor !== null) {
            $amount = $amount->max(Money::of($terms->minMinor, $currency));
        }

        if ($terms->maxMinor !== null) {
            $amount = $amount->min(Money::of($terms->maxMinor, $currency));
        }

        return $amount->min($base);
    }

    /**
     * Commission on a tip: only when the version asks for it, and only its percentage part (a fixed fee is per trip).
     */
    public function tipCommission(CommissionTerms $terms, Money $tip): Money
    {
        if (! $terms->commissionOnTips || ! $terms->type->usesPercent() || ! $tip->isPositive()) {
            return Money::zero($tip->currency);
        }

        return $tip->multiplyBasisPoints($terms->percentBp);
    }
}
