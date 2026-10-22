<?php

namespace App\DTOs\Finance;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Finance\AdjustmentDirection;
use App\Enums\Finance\ApprovalStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Adjustment requests listing: status, direction, requester, account and created range.
 */
final readonly class AdjustmentFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<ApprovalStatus> $statuses
     * @param list<AdjustmentDirection> $directions
     * @param list<int> $requesterIds
     */
    public function __construct(
        public array $statuses = [],
        public array $directions = [],
        public array $requesterIds = [],
        public ?string $search = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?int $accountId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created_at');

        return new self(
            statuses: self::enumListFilter($input, 'statuses', ApprovalStatus::class),
            directions: self::enumListFilter($input, 'directions', AdjustmentDirection::class),
            requesterIds: self::idListFilter($input, 'requester_ids'),
            search: self::stringFilter($input, 'search', 120),
            from: $from,
            to: $to,
        );
    }

    public function forAccount(int $accountId): self
    {
        return new self(accountId: $accountId);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses, $this->directions, $this->requesterIds, $this->search, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'statuses' => array_map(static fn (ApprovalStatus $status): string => $status->value, $this->statuses),
            'directions' => array_map(static fn (AdjustmentDirection $direction): string => $direction->value, $this->directions),
            'requester_ids' => $this->requesterIds,
            'search' => $this->search,
            'created_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
