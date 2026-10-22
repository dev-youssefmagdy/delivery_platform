<?php

namespace App\DTOs\Booking;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Booking\BookingAdjustmentStatus;
use App\Enums\Booking\BookingAdjustmentType;
use App\Enums\Common\ActorType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the booking adjustments listing: status, type, who asked, a booking code / adjustment uuid and a created range.
 * `bookingId` pins the listing to one booking (the tab on the booking page).
 */
final readonly class BookingAdjustmentFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<BookingAdjustmentStatus> $statuses
     * @param list<BookingAdjustmentType> $types
     * @param list<ActorType> $requesters
     */
    public function __construct(
        public array $statuses = [],
        public array $types = [],
        public array $requesters = [],
        public ?string $search = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?int $bookingId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created_at');

        return new self(
            statuses: self::enumListFilter($input, 'statuses', BookingAdjustmentStatus::class),
            types: self::enumListFilter($input, 'types', BookingAdjustmentType::class),
            requesters: self::enumListFilter($input, 'requesters', ActorType::class),
            search: self::stringFilter($input, 'search', 120),
            from: $from,
            to: $to,
        );
    }

    public function forBooking(int $bookingId): self
    {
        return new self($this->statuses, $this->types, $this->requesters, $this->search, $this->from, $this->to, $bookingId);
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses, $this->types, $this->requesters, $this->search, $this->from ?? $this->to]);
    }

    public function toArray(): array
    {
        return array_filter([
            'statuses' => array_map(static fn (BookingAdjustmentStatus $status): string => $status->value, $this->statuses),
            'types' => array_map(static fn (BookingAdjustmentType $type): string => $type->value, $this->types),
            'requesters' => array_map(static fn (ActorType $actor): string => $actor->value, $this->requesters),
            'search' => $this->search,
            'created_at' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
