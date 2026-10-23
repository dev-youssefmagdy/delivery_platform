<?php

namespace App\DTOs\Safety;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Safety\IncidentCategory;
use App\Enums\Safety\IncidentPriority;
use App\Enums\Safety\IncidentSeverity;
use App\Enums\Safety\IncidentStatus;
use App\Enums\Safety\IncidentTrigger;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Safety Center queue (Plan Appendix C "Incidents"): severity, status, category, assigned officer, SLA breached,
 * date range, plus number, trigger, priority, booking code and the quick scopes `urgent`, `mine` and `unassigned`.
 */
final readonly class IncidentFilterData implements FilterData
{
    use ParsesFilterInput;

    public const SCOPE_URGENT = 'urgent';

    public const SCOPE_MINE = 'mine';

    public const SCOPE_UNASSIGNED = 'unassigned';

    /**
     * @param list<IncidentSeverity> $severities
     * @param list<IncidentStatus> $statuses
     * @param list<IncidentCategory> $categories
     * @param list<IncidentPriority> $priorities
     * @param list<int> $assignedAdminIds
     */
    public function __construct(
        public ?string $number = null,
        public array $severities = [],
        public array $statuses = [],
        public array $categories = [],
        public array $priorities = [],
        public array $assignedAdminIds = [],
        public ?IncidentTrigger $trigger = null,
        public ?bool $slaBreached = null,
        public ?string $bookingCode = null,
        public ?bool $legalHold = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?string $scope = null,
        public ?int $bookingId = null,
        public ?int $adminId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$from, $to] = self::dateRangeFilter($input, 'created');
        $scope = self::stringFilter($input, 'scope', 16);

        return new self(
            number: self::stringFilter($input, 'number', 20),
            severities: self::enumListFilter($input, 'severities', IncidentSeverity::class),
            statuses: self::enumListFilter($input, 'statuses', IncidentStatus::class),
            categories: self::enumListFilter($input, 'categories', IncidentCategory::class),
            priorities: self::enumListFilter($input, 'priorities', IncidentPriority::class),
            assignedAdminIds: self::idListFilter($input, 'assigned'),
            trigger: IncidentTrigger::tryFromValue(self::stringFilter($input, 'trigger', 16)),
            slaBreached: self::boolFilter($input, 'sla_breached'),
            bookingCode: self::stringFilter($input, 'booking', 40),
            legalHold: self::boolFilter($input, 'legal_hold'),
            from: $from,
            to: $to,
            scope: in_array($scope, [self::SCOPE_URGENT, self::SCOPE_MINE, self::SCOPE_UNASSIGNED], true) ? $scope : null,
            adminId: auth('admin')->id() === null ? null : (int) auth('admin')->id(),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([
            $this->number, $this->severities, $this->statuses, $this->categories, $this->priorities, $this->assignedAdminIds, $this->trigger,
            $this->slaBreached, $this->bookingCode, $this->legalHold, $this->from ?? $this->to, $this->scope,
        ]);
    }

    public function toArray(): array
    {
        return array_filter([
            'number' => $this->number,
            'severities' => array_map(static fn (IncidentSeverity $case): string => $case->value, $this->severities),
            'statuses' => array_map(static fn (IncidentStatus $case): string => $case->value, $this->statuses),
            'categories' => array_map(static fn (IncidentCategory $case): string => $case->value, $this->categories),
            'priorities' => array_map(static fn (IncidentPriority $case): string => $case->value, $this->priorities),
            'assigned' => $this->assignedAdminIds,
            'trigger' => $this->trigger?->value,
            'sla_breached' => $this->slaBreached === null ? null : ($this->slaBreached ? '1' : '0'),
            'booking' => $this->bookingCode,
            'legal_hold' => $this->legalHold === null ? null : ($this->legalHold ? '1' : '0'),
            'created' => array_filter(['from' => $this->from?->toDateString(), 'to' => $this->to?->toDateString()]),
            'scope' => $this->scope,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
