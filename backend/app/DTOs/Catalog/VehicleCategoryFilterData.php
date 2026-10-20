<?php

namespace App\DTOs\Catalog;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Illuminate\Http\Request;

/**
 * Filters of the Vehicle Categories listing.
 */
final readonly class VehicleCategoryFilterData implements FilterData
{
    use ParsesFilterInput;

    public function __construct(
        public ?string $search = null,
        public ?bool $active = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(search: self::stringFilter($input, 'search'), active: self::boolFilter($input, 'active'));
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->active]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
        ], static fn (string|int|null $value): bool => $value !== null);
    }
}
