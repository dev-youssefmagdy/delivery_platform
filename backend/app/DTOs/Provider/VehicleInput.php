<?php

namespace App\DTOs\Provider;

/**
 * Validated vehicle fields from the driver app. On create everything is present; on update null means "unchanged".
 */
final readonly class VehicleInput
{
    /**
     * @param list<string>|null $capabilities VehicleCapability values
     */
    public function __construct(
        public ?int $vehicleCategoryId = null,
        public ?string $make = null,
        public ?string $model = null,
        public ?int $year = null,
        public ?string $color = null,
        public ?string $plate = null,
        public ?int $passengerCapacity = null,
        public ?string $payloadKg = null,
        public ?string $volumeM3 = null,
        public ?array $capabilities = null,
    ) {}
}
