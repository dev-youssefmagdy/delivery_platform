<?php

namespace App\DTOs\Catalog;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Catalog\ServiceFamilyKind;
use Illuminate\Http\Request;

/**
 * Filters of the Service Families listing.
 */
final readonly class ServiceFamilyFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<ServiceFamilyKind> $kinds
     */
    public function __construct(
        public ?string $search = null,
        public array $kinds = [],
        public ?bool $active = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(
            search: self::stringFilter($input, 'search'),
            kinds: self::enumListFilter($input, 'kinds', ServiceFamilyKind::class),
            active: self::boolFilter($input, 'active'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->kinds, $this->active]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'kinds' => array_map(static fn (ServiceFamilyKind $kind): string => $kind->value, $this->kinds),
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
