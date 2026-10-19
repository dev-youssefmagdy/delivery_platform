<?php

namespace App\DTOs\Policy;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Content\ContentStatus;
use App\Enums\Policy\PolicyType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Policy versions listing (filters card).
 */
final readonly class PolicyVersionFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<PolicyType> $types
     * @param list<ContentStatus> $statuses
     */
    public function __construct(
        public ?string $search = null,
        public array $types = [],
        public array $statuses = [],
        public ?CarbonImmutable $publishedFrom = null,
        public ?CarbonImmutable $publishedTo = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$publishedFrom, $publishedTo] = self::dateRangeFilter($input, 'published_at');

        return new self(
            search: self::stringFilter($input, 'search'),
            types: self::enumListFilter($input, 'types', PolicyType::class),
            statuses: self::enumListFilter($input, 'statuses', ContentStatus::class),
            publishedFrom: $publishedFrom,
            publishedTo: $publishedTo,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->types, $this->statuses, $this->publishedFrom ?? $this->publishedTo]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'types' => array_map(static fn (PolicyType $type): string => $type->value, $this->types),
            'statuses' => array_map(static fn (ContentStatus $status): string => $status->value, $this->statuses),
            'published_at' => array_filter(['from' => $this->publishedFrom?->toDateString(), 'to' => $this->publishedTo?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
