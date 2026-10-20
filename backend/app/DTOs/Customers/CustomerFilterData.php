<?php

namespace App\DTOs\Customers;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Identity\UserStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Customers listing (Plan Appendix C: uuid/phone/name, status, registered range, city,
 * has active booking, deletion requested). "Has active booking" is not offered until bookings exist (P2.3);
 * "city" matches customers with a saved place inside a zone of that city (ADR-76).
 */
final readonly class CustomerFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<UserStatus> $statuses
     * @param list<int> $cityIds
     */
    public function __construct(
        public ?string $search = null,
        public array $statuses = [],
        public ?CarbonImmutable $registeredFrom = null,
        public ?CarbonImmutable $registeredTo = null,
        public array $cityIds = [],
        public ?bool $deletionRequested = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created_at');

        return new self(
            search: self::stringFilter($input, 'search', 100),
            statuses: self::enumListFilter($input, 'statuses', UserStatus::class),
            registeredFrom: $from,
            registeredTo: $to,
            cityIds: self::idListFilter($input, 'city_ids'),
            deletionRequested: self::boolFilter($input, 'deletion_requested'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->search, $this->statuses, $this->registeredFrom ?? $this->registeredTo, $this->cityIds, $this->deletionRequested]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'statuses' => array_map(static fn (UserStatus $status): string => $status->value, $this->statuses),
            'created_at' => array_filter(['from' => $this->registeredFrom?->toDateString(), 'to' => $this->registeredTo?->toDateString()]),
            'city_ids' => $this->cityIds,
            'deletion_requested' => $this->deletionRequested === null ? null : ($this->deletionRequested ? '1' : '0'),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
