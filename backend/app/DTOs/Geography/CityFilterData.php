<?php

namespace App\DTOs\Geography;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Illuminate\Http\Request;

/**
 * Filters of the Cities listing (search, country, active).
 */
final readonly class CityFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<int> $countryIds
     */
    public function __construct(
        public ?string $search = null,
        public array $countryIds = [],
        public ?bool $active = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(
            search: self::stringFilter($input, 'search'),
            countryIds: self::idListFilter($input, 'country_ids'),
            active: self::boolFilter($input, 'active'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->countryIds, $this->active]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'country_ids' => $this->countryIds,
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
