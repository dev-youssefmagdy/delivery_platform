<?php

namespace App\DTOs\Provider;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Provider\VehicleStatus;
use Illuminate\Http\Request;

/**
 * Filters of the Vehicles listing (Plan Appendix C: plate, vehicle category, status, provider).
 */
final readonly class VehicleFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<int> $vehicleCategoryIds
     * @param list<VehicleStatus> $statuses
     * @param list<int> $providerIds provider profile ids
     */
    public function __construct(
        public ?string $plate = null,
        public array $vehicleCategoryIds = [],
        public array $statuses = [],
        public array $providerIds = [],
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(
            plate: self::stringFilter($input, 'plate', 20),
            vehicleCategoryIds: self::idListFilter($input, 'vehicle_category_ids'),
            statuses: self::enumListFilter($input, 'statuses', VehicleStatus::class),
            providerIds: self::idListFilter($input, 'provider_ids'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->plate, $this->vehicleCategoryIds, $this->statuses, $this->providerIds]);
    }

    public function toArray(): array
    {
        return array_filter([
            'plate' => $this->plate,
            'vehicle_category_ids' => $this->vehicleCategoryIds,
            'statuses' => array_map(static fn (VehicleStatus $status): string => $status->value, $this->statuses),
            'provider_ids' => $this->providerIds,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
