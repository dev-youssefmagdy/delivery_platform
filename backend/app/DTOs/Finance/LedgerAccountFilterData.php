<?php

namespace App\DTOs\Finance;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Finance\LedgerAccountType;
use Illuminate\Http\Request;

/**
 * Filters of the Ledger accounts listing: type, currency, negative balances only and a balance range (major units in
 * the form, minor units here).
 */
final readonly class LedgerAccountFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<LedgerAccountType> $types
     */
    public function __construct(
        public array $types = [],
        public ?string $currency = null,
        public ?bool $negativeOnly = null,
        public ?int $balanceMinMinor = null,
        public ?int $balanceMaxMinor = null,
        public ?string $uuid = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        $balance = is_array($input['balance'] ?? null) ? $input['balance'] : [];
        $currency = self::stringFilter($input, 'currency', 3);

        return new self(
            types: self::enumListFilter($input, 'types', LedgerAccountType::class),
            currency: $currency === null ? null : strtoupper($currency),
            negativeOnly: self::boolFilter($input, 'negative_only'),
            balanceMinMinor: self::minorUnitsFilter($balance, 'min'),
            balanceMaxMinor: self::minorUnitsFilter($balance, 'max'),
            uuid: self::stringFilter($input, 'uuid', 36),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->types, $this->currency, $this->negativeOnly, $this->balanceMinMinor ?? $this->balanceMaxMinor, $this->uuid]);
    }

    public function toArray(): array
    {
        return array_filter([
            'types' => array_map(static fn (LedgerAccountType $type): string => $type->value, $this->types),
            'currency' => $this->currency,
            'negative_only' => $this->negativeOnly === null ? null : ($this->negativeOnly ? '1' : '0'),
            'balance' => array_filter([
                'min' => $this->balanceMinMinor === null ? null : self::decimalFromMinor($this->balanceMinMinor),
                'max' => $this->balanceMaxMinor === null ? null : self::decimalFromMinor($this->balanceMaxMinor),
            ], static fn (?string $value): bool => $value !== null),
            'uuid' => $this->uuid,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
