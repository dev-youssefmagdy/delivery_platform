<?php

namespace App\DTOs\Reviews;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the report and appeal tabs of the Reviews module: the status (values of the table's own status enum) and a date range.
 */
final readonly class ModerationQueueFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<string> $statuses
     */
    public function __construct(
        public array $statuses = [],
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created');
        $statuses = $input['statuses'] ?? [];

        return new self(
            statuses: array_values(array_filter(array_map(static fn (mixed $value): string => is_string($value) ? mb_substr($value, 0, 16) : '', is_array($statuses) ? $statuses : [$statuses]), static fn (string $value): bool => preg_match('/^[a-z_]+$/', $value) === 1)),
            from: $from,
            to: $to,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'statuses' => $this->statuses,
            'created' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== []);
    }
}
