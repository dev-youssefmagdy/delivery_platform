<?php

namespace App\DTOs\Geography;

/**
 * One row of the zone availability matrix: is the category enabled in the zone, and with which overrides.
 */
final readonly class ZoneCategoryAvailabilityData
{
    /**
     * @param list<float> $radiusStepsKm empty = inherit the category/platform dispatch radius
     * @param array{from: string, to: string}|null $hours daily window (every day); null = no override
     */
    public function __construct(
        public int $serviceCategoryId,
        public bool $active,
        public ?int $minFareMinor = null,
        public ?int $maxFareMinor = null,
        public array $radiusStepsKm = [],
        public ?array $hours = null,
    ) {}

    /**
     * @return array<string, mixed>|null null when there is no override at all
     */
    public function overrides(): ?array
    {
        $overrides = array_filter([
            'min_fare_minor' => $this->minFareMinor,
            'max_fare_minor' => $this->maxFareMinor,
            'dispatch_radius_steps_km' => $this->radiusStepsKm === [] ? null : $this->radiusStepsKm,
            'hours' => $this->hours,
        ], static fn (mixed $value): bool => $value !== null);

        return $overrides === [] ? null : $overrides;
    }
}
