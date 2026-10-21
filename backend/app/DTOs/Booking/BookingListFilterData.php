<?php

namespace App\DTOs\Booking;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Booking\BookingStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of a customer's booking history (API) and of the Bookings tab of the Customers admin module.
 */
final readonly class BookingListFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<BookingStatus> $statuses
     * @param list<int> $serviceCategoryIds
     */
    public function __construct(
        public array $statuses = [],
        public array $serviceCategoryIds = [],
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?bool $inFlight = null,
    ) {}

    /**
     * Reads `filters[...]`; the API sends the same keys at the top level (`status[]`, `service_category_id`, `from`, `to`).
     */
    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request) + $request->query();
        $statuses = $input['statuses'] ?? $input['status'] ?? [];
        $categories = $input['service_category_ids'] ?? $input['service_category_id'] ?? [];
        $normalised = [
            'statuses' => is_array($statuses) ? $statuses : [$statuses],
            'service_category_ids' => is_array($categories) ? $categories : [$categories],
            'from' => $input['from'] ?? null,
            'to' => $input['to'] ?? null,
            'in_flight' => $input['in_flight'] ?? null,
        ];

        return new self(
            statuses: self::enumListFilter($normalised, 'statuses', BookingStatus::class),
            serviceCategoryIds: self::idListFilter($normalised, 'service_category_ids'),
            from: self::dateFilter($normalised, 'from'),
            to: self::dateFilter($normalised, 'to'),
            inFlight: self::boolFilter($normalised, 'in_flight'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses, $this->serviceCategoryIds, $this->from ?? $this->to, $this->inFlight]);
    }

    public function toArray(): array
    {
        return array_filter([
            'statuses' => array_map(static fn (BookingStatus $status): string => $status->value, $this->statuses),
            'service_category_ids' => $this->serviceCategoryIds,
            'from' => $this->from?->toDateString(),
            'to' => $this->to?->toDateString(),
            'in_flight' => $this->inFlight === null ? null : ($this->inFlight ? '1' : '0'),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
