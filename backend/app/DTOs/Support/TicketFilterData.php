<?php

namespace App\DTOs\Support;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Support\TicketCategory;
use App\Enums\Support\TicketGroup;
use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Ticket inbox (Plan Appendix C): number, status, priority, category, assigned agent/group, SLA breached,
 * linked booking and date range, plus the `scope` quick filters "my tickets" (`mine`) and `unassigned`.
 */
final readonly class TicketFilterData implements FilterData
{
    use ParsesFilterInput;

    public const SCOPE_MINE = 'mine';

    public const SCOPE_UNASSIGNED = 'unassigned';

    /**
     * @param list<TicketStatus> $statuses
     * @param list<TicketPriority> $priorities
     * @param list<TicketCategory> $categories
     * @param list<TicketGroup> $groups
     * @param list<int> $assignedAdminIds
     */
    public function __construct(
        public ?string $number = null,
        public array $statuses = [],
        public array $priorities = [],
        public array $categories = [],
        public array $groups = [],
        public array $assignedAdminIds = [],
        public ?bool $slaBreached = null,
        public ?string $bookingCode = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?string $scope = null,
        public ?int $userId = null,
        public ?int $bookingId = null,
        public ?bool $hasBooking = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created');
        $scope = self::stringFilter($input, 'scope', 16);

        return new self(
            number: self::stringFilter($input, 'number', 20),
            statuses: self::enumListFilter($input, 'statuses', TicketStatus::class),
            priorities: self::enumListFilter($input, 'priorities', TicketPriority::class),
            categories: self::enumListFilter($input, 'categories', TicketCategory::class),
            groups: self::enumListFilter($input, 'groups', TicketGroup::class),
            assignedAdminIds: self::idListFilter($input, 'assigned'),
            slaBreached: self::boolFilter($input, 'sla_breached'),
            bookingCode: self::stringFilter($input, 'booking', 40),
            from: $from,
            to: $to,
            scope: in_array($scope, [self::SCOPE_MINE, self::SCOPE_UNASSIGNED], true) ? $scope : null,
        );
    }

    /**
     * A listing locked to one customer (the Customers tab), whatever the request says.
     */
    public function forUser(int $userId): self
    {
        return new self(
            $this->number, $this->statuses, $this->priorities, $this->categories, $this->groups, $this->assignedAdminIds,
            $this->slaBreached, $this->bookingCode, $this->from, $this->to, $this->scope, $userId, $this->bookingId, $this->hasBooking,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([
            $this->number, $this->statuses, $this->priorities, $this->categories, $this->groups, $this->assignedAdminIds,
            $this->slaBreached, $this->bookingCode, $this->from ?? $this->to, $this->scope,
        ]);
    }

    public function toArray(): array
    {
        return array_filter([
            'number' => $this->number,
            'statuses' => array_map(static fn (TicketStatus $case): string => $case->value, $this->statuses),
            'priorities' => array_map(static fn (TicketPriority $case): string => $case->value, $this->priorities),
            'categories' => array_map(static fn (TicketCategory $case): string => $case->value, $this->categories),
            'groups' => array_map(static fn (TicketGroup $case): string => $case->value, $this->groups),
            'assigned' => $this->assignedAdminIds,
            'sla_breached' => $this->slaBreached === null ? null : ($this->slaBreached ? '1' : '0'),
            'booking' => $this->bookingCode,
            'created' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
            'scope' => $this->scope,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
