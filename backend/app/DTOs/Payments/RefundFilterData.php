<?php

namespace App\DTOs\Payments;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Payments\RefundStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Refunds listing: status, awaiting-approval flag, payment/booking search, created range.
 */
final readonly class RefundFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<RefundStatus> $statuses
     */
    public function __construct(
        public array $statuses = [],
        public ?bool $dualApproval = null,
        public ?string $search = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?int $paymentId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created_at');

        return new self(
            statuses: self::enumListFilter($input, 'statuses', RefundStatus::class),
            dualApproval: self::boolFilter($input, 'dual_approval'),
            search: self::stringFilter($input, 'search', 120),
            from: $from,
            to: $to,
        );
    }

    public function forPayment(int $paymentId): self
    {
        return new self(paymentId: $paymentId);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses, $this->dualApproval, $this->search, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'statuses' => array_map(static fn (RefundStatus $status): string => $status->value, $this->statuses),
            'dual_approval' => $this->dualApproval === null ? null : ($this->dualApproval ? '1' : '0'),
            'search' => $this->search,
            'created_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
