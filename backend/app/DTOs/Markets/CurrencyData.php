<?php

namespace App\DTOs\Markets;

use App\Http\Requests\Admin\Markets\StoreCurrencyRequest;
use App\Http\Requests\Admin\Markets\UpdateCurrencyRequest;
use App\Models\Currency;

/**
 * Validated input for creating/updating a currency.
 */
final readonly class CurrencyData
{
    public function __construct(
        public string $code,
        public int $exponent,
        public string $symbolAr,
        public string $symbolEn,
        public bool $active,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreCurrencyRequest|UpdateCurrencyRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $current = $request->route('currency');
        $code = $current instanceof Currency ? $current->code : '';
        $exponent = $current instanceof Currency ? $current->exponent : 2;

        return new self(
            code: strtoupper(trim((string) ($data['code'] ?? $code))),
            exponent: (int) ($data['exponent'] ?? $exponent),
            symbolAr: trim((string) $data['symbol_ar']),
            symbolEn: trim((string) $data['symbol_en']),
            active: (bool) ($data['active'] ?? false),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * Model attributes (lock_version is managed by the service).
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'code' => $this->code,
            'exponent' => $this->exponent,
            'symbol_ar' => $this->symbolAr,
            'symbol_en' => $this->symbolEn,
            'active' => $this->active,
        ];
    }
}
