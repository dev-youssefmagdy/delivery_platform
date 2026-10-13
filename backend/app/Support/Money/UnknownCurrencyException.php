<?php

namespace App\Support\Money;

use InvalidArgumentException;

final class UnknownCurrencyException extends InvalidArgumentException
{
    public static function forCode(string $code): self
    {
        return new self("Unknown currency [{$code}].");
    }
}
