<?php

namespace App\DTOs\Provider;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Provider\DocumentSubject;
use Illuminate\Http\Request;

/**
 * Filters of the Document types listing.
 */
final readonly class DocumentTypeFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<DocumentSubject> $appliesTo
     */
    public function __construct(
        public ?string $search = null,
        public array $appliesTo = [],
        public ?bool $active = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(
            search: self::stringFilter($input, 'search'),
            appliesTo: self::enumListFilter($input, 'applies_to', DocumentSubject::class),
            active: self::boolFilter($input, 'active'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->appliesTo, $this->active]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'applies_to' => array_map(static fn (DocumentSubject $subject): string => $subject->value, $this->appliesTo),
            'active' => $this->active === null ? null : ($this->active ? '1' : '0'),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
