<?php

namespace App\DTOs\Finance;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Finance\CashReconciliationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Cash reconciliation listing: status, provider and the period range.
 */
final readonly class CashReconciliationFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<CashReconciliationStatus> $statuses
     * @param list<int> $providerIds
     */
    public function __construct(
        public array $statuses = [],
        public array $providerIds = [],
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?int $providerId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'period');

        return new self(self::enumListFilter($input, 'statuses', CashReconciliationStatus::class), self::idListFilter($input, 'provider_ids'), $from, $to);
    }

    public function forProvider(int $providerId): self
    {
        return new self(providerId: $providerId);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses, $this->providerIds, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'statuses' => array_map(static fn (CashReconciliationStatus $status): string => $status->value, $this->statuses),
            'provider_ids' => $this->providerIds,
            'period' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== []);
    }
}
