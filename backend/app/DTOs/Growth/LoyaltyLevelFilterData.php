<?php

namespace App\DTOs\Growth;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Illuminate\Http\Request;

/**
 * Filters of the loyalty levels listing: active or inactive.
 */
final readonly class LoyaltyLevelFilterData implements FilterData
{
    use ParsesFilterInput;

    public function __construct(public ?bool $active = null) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(self::boolFilter($input, 'active'));
    }

    public function activeCount(): int
    {
        return self::countActive([$this->active]);
    }

    public function toArray(): array
    {
        return array_filter(['active' => $this->active], static fn (mixed $value): bool => $value !== null);
    }
}
