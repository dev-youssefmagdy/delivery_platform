<?php

namespace App\DTOs\Support;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Support\TicketCategory;
use Illuminate\Http\Request;

final readonly class MacroFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<TicketCategory> $categories
     */
    public function __construct(public ?string $title = null, public array $categories = [], public ?bool $active = null) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(
            title: self::stringFilter($input, 'title', 120),
            categories: self::enumListFilter($input, 'categories', TicketCategory::class),
            active: self::boolFilter($input, 'active'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->title, $this->categories, $this->active]);
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'categories' => array_map(static fn (TicketCategory $case): string => $case->value, $this->categories),
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
