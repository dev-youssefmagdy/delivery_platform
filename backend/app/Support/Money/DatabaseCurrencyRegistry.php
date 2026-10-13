<?php

namespace App\Support\Money;

use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;

/**
 * Currency lookups backed by the `currencies` table, memoised for the lifetime of the container instance.
 */
final class DatabaseCurrencyRegistry implements CurrencyRegistry
{
    /** @var array<string, Currency|null> */
    private array $resolved = [];

    public function __construct(private readonly CurrencyRepositoryInterface $currencies) {}

    public function get(string $code): Currency
    {
        return $this->lookup($code) ?? throw UnknownCurrencyException::forCode($code);
    }

    public function has(string $code): bool
    {
        return $this->lookup($code) !== null;
    }

    public function flush(): void
    {
        $this->resolved = [];
    }

    private function lookup(string $code): ?Currency
    {
        $code = strtoupper($code);

        if (! array_key_exists($code, $this->resolved)) {
            $this->resolved[$code] = $this->currencies->findByCode($code)?->toValue();
        }

        return $this->resolved[$code];
    }
}
