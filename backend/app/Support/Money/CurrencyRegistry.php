<?php

namespace App\Support\Money;

/**
 * Resolves a currency code to a Currency (exponent + symbols). Backed by the `currencies` table.
 */
interface CurrencyRegistry
{
    /**
     * @throws UnknownCurrencyException
     */
    public function get(string $code): Currency;

    public function has(string $code): bool;
}
