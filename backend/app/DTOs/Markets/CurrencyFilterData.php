<?php

namespace App\DTOs\Markets;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Currencies listing (filters card).
 */
final readonly class CurrencyFilterData implements FilterData
{
    use ParsesFilterInput;

    public function __construct(
        public ?string $search = null,
        public ?bool $active = null,
        public ?CarbonImmutable $createdFrom = null,
        public ?CarbonImmutable $createdTo = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$createdFrom, $createdTo] = self::dateRangeFilter($input, 'created_at');

        return new self(
            search: self::stringFilter($input, 'search'),
            active: self::boolFilter($input, 'active'),
            createdFrom: $createdFrom,
            createdTo: $createdTo,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->active, $this->createdFrom ?? $this->createdTo]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
            'created_at' => array_filter(['from' => $this->createdFrom?->toDateString(), 'to' => $this->createdTo?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
