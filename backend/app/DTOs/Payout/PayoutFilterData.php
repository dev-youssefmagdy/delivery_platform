<?php

namespace App\DTOs\Payout;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Payout\PayoutStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Payout requests listing (Plan Appendix C): status, provider, amount range, requested range, approver.
 * `batchId` / `providerId` are set by code (batch page, provider tab, batch export), never read from the request.
 */
final readonly class PayoutFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<PayoutStatus> $statuses
     * @param list<int> $providerIds
     * @param list<int> $approverIds
     */
    public function __construct(
        public array $statuses = [],
        public array $providerIds = [],
        public ?int $amountMinMinor = null,
        public ?int $amountMaxMinor = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public array $approverIds = [],
        public ?string $search = null,
        public ?int $batchId = null,
        public ?int $providerId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'requested_at');
        $amount = is_array($input['amount'] ?? null) ? $input['amount'] : [];

        return new self(
            statuses: self::enumListFilter($input, 'statuses', PayoutStatus::class),
            providerIds: self::idListFilter($input, 'provider_ids'),
            amountMinMinor: self::minorUnitsFilter($amount, 'min'),
            amountMaxMinor: self::minorUnitsFilter($amount, 'max'),
            from: $from,
            to: $to,
            approverIds: self::idListFilter($input, 'approver_ids'),
            search: self::stringFilter($input, 'search', 120),
        );
    }

    public function forBatch(int $batchId): self
    {
        return new self(batchId: $batchId);
    }

    public function forProvider(int $providerId): self
    {
        return new self(providerId: $providerId);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses, $this->providerIds, $this->amountMinMinor, $this->amountMaxMinor, $this->from ?? $this->to, $this->approverIds, $this->search]);
    }

    public function toArray(): array
    {
        return array_filter([
            'statuses' => array_map(static fn (PayoutStatus $status): string => $status->value, $this->statuses),
            'provider_ids' => $this->providerIds,
            'amount' => array_filter([
                'min' => $this->amountMinMinor === null ? null : self::decimalFromMinor($this->amountMinMinor),
                'max' => $this->amountMaxMinor === null ? null : self::decimalFromMinor($this->amountMaxMinor),
            ], static fn (mixed $value): bool => $value !== null),
            'requested_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
            'approver_ids' => $this->approverIds,
            'search' => $this->search,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
