<?php

namespace App\DTOs\Payout;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Payout\PayoutBatchStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Payout batches listing: status, reference search and created range.
 */
final readonly class PayoutBatchFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<PayoutBatchStatus> $statuses
     */
    public function __construct(
        public array $statuses = [],
        public ?string $search = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created_at');

        return new self(self::enumListFilter($input, 'statuses', PayoutBatchStatus::class), self::stringFilter($input, 'search', 60), $from, $to);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses, $this->search, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'statuses' => array_map(static fn (PayoutBatchStatus $status): string => $status->value, $this->statuses),
            'search' => $this->search,
            'created_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
