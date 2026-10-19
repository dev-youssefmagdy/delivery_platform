<?php

namespace App\DTOs\Admin;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Illuminate\Http\Request;

/**
 * Filters of the roles listing (name search, system roles only).
 */
final readonly class RoleFilterData implements FilterData
{
    use ParsesFilterInput;

    public function __construct(
        public ?string $search = null,
        public ?bool $system = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(self::stringFilter($input, 'search'), self::boolFilter($input, 'system'));
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->system]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'system' => $this->system === null ? null : (int) $this->system,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
