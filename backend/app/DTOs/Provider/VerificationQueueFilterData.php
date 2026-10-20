<?php

namespace App\DTOs\Provider;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ProviderType;
use Illuminate\Http\Request;

/**
 * Filters of the verification queue (Plan Appendix C: status under_review / more_info, provider type, city,
 * waiting time > X hours, assigned officer).
 */
final readonly class VerificationQueueFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * Statuses that belong in the queue.
     *
     * @var list<ProviderStatus>
     */
    public const QUEUE_STATUSES = [ProviderStatus::UnderReview, ProviderStatus::MoreInformationRequired];

    /**
     * @param list<ProviderStatus> $statuses
     * @param list<ProviderType> $providerTypes
     * @param list<int> $cityIds
     * @param list<int> $assignedAdminIds
     */
    public function __construct(
        public array $statuses = [],
        public array $providerTypes = [],
        public array $cityIds = [],
        public ?int $waitingMoreThanHours = null,
        public array $assignedAdminIds = [],
        public ?bool $unassignedOnly = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        $hours = self::intFilter($input, 'waiting_hours');
        $statuses = array_values(array_filter(
            self::enumListFilter($input, 'statuses', ProviderStatus::class),
            static fn (ProviderStatus $status): bool => in_array($status, self::QUEUE_STATUSES, true),
        ));

        return new self(
            statuses: $statuses,
            providerTypes: self::enumListFilter($input, 'provider_types', ProviderType::class),
            cityIds: self::idListFilter($input, 'city_ids'),
            waitingMoreThanHours: $hours !== null && $hours >= 1 && $hours <= 8760 ? $hours : null,
            assignedAdminIds: self::idListFilter($input, 'assigned_admin_ids'),
            unassignedOnly: self::boolFilter($input, 'unassigned'),
        );
    }

    /**
     * @return list<ProviderStatus>
     */
    public function effectiveStatuses(): array
    {
        return $this->statuses === [] ? self::QUEUE_STATUSES : $this->statuses;
    }

    public function activeCount(): int
    {
        return self::countActive([$this->statuses, $this->providerTypes, $this->cityIds, $this->waitingMoreThanHours, $this->assignedAdminIds, $this->unassignedOnly]);
    }

    public function toArray(): array
    {
        return array_filter([
            'statuses' => array_map(static fn (ProviderStatus $status): string => $status->value, $this->statuses),
            'provider_types' => array_map(static fn (ProviderType $type): string => $type->value, $this->providerTypes),
            'city_ids' => $this->cityIds,
            'waiting_hours' => $this->waitingMoreThanHours,
            'assigned_admin_ids' => $this->assignedAdminIds,
            'unassigned' => $this->unassignedOnly === null ? null : ($this->unassignedOnly ? '1' : '0'),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
