<?php

namespace App\DTOs\Geography;

use App\Http\Requests\Admin\Geography\UpdateZoneAvailabilityRequest;

/**
 * The whole availability matrix of one zone as submitted by the admin form.
 */
final readonly class ZoneAvailabilityData
{
    /**
     * @param list<ZoneCategoryAvailabilityData> $rows
     */
    public function __construct(public array $rows, public ?int $lockVersion = null) {}

    public static function fromRequest(UpdateZoneAvailabilityRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $rows = [];

        foreach ((array) ($data['categories'] ?? []) as $categoryId => $row) {
            $row = (array) $row;
            $int = static fn (string $key): ?int => isset($row[$key]) && $row[$key] !== '' ? (int) $row[$key] : null;
            $steps = trim((string) ($row['dispatch_radius_steps_km'] ?? ''));
            $from = trim((string) ($row['hours_from'] ?? ''));
            $to = trim((string) ($row['hours_to'] ?? ''));

            $rows[] = new ZoneCategoryAvailabilityData(
                serviceCategoryId: (int) $categoryId,
                active: filter_var($row['active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                minFareMinor: $int('min_fare_minor'),
                maxFareMinor: $int('max_fare_minor'),
                radiusStepsKm: $steps === '' ? [] : array_map(static fn (string $part): float => (float) trim($part), explode(',', $steps)),
                hours: $from !== '' && $to !== '' ? ['from' => $from, 'to' => $to] : null,
            );
        }

        return new self($rows, isset($data['lock_version']) ? (int) $data['lock_version'] : null);
    }
}
