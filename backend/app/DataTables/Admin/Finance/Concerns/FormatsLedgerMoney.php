<?php

namespace App\DataTables\Admin\Finance\Concerns;

use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;

/**
 * Ledger rows carry `*_minor` amounts and a currency code per row: this formats one in the current locale.
 *
 * @mixin \App\DataTables\Admin\AdminDataTable
 */
trait FormatsLedgerMoney
{
    protected function formatMinor(int $minor, string $currency): string
    {
        return app(MoneyFormatter::class)->format(Money::of($minor, app(CurrencyRegistry::class)->get($currency)));
    }

    /**
     * Balances read better with their sign visible; the cell is coloured by the caller (negative = owed).
     */
    protected function balanceCell(int $minor, string $currency): string
    {
        $class = $minor < 0 ? 'text-danger-700' : 'text-slate-900';

        return '<span class="tabular-nums whitespace-nowrap '.$class.'" dir="ltr">'.e($this->formatMinor($minor, $currency)).'</span>';
    }
}
