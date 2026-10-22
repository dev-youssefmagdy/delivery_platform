<?php

namespace App\DTOs\Scope;

/**
 * The canonical structured scope of an offer (SRS §7.4). `toArray()` has a fixed key order and defaults, so two equal
 * scopes serialise identically (revision replays, the agreement snapshot and the summary both apps render).
 */
final readonly class OfferScopeData
{
    /**
     * @param list<string> $loadingEquipment
     * @param array{handling: string, included_floors: int} $stairs
     */
    public function __construct(
        public int $version,
        public string $vehicleClass,
        public int $helpers,
        public string $packing,
        public array $stairs,
        public string $assembly,
        public array $loadingEquipment,
        public int $waitingAllowanceMinutes,
        public int $extraStopsIncluded,
        public string $tolls,
        public string $parking,
        public string $exclusions = '',
    ) {}

    /**
     * @param array<string, mixed> $scope an array that passed the JSON schema
     */
    public static function fromValidated(array $scope, int $version): self
    {
        $equipment = array_values(array_unique(array_map('strval', (array) ($scope['loading_equipment'] ?? []))));
        sort($equipment);
        $stairs = (array) ($scope['stairs'] ?? []);
        $handling = (string) ($stairs['handling'] ?? 'not_needed');

        return new self(
            $version,
            (string) $scope['vehicle_class'],
            (int) $scope['helpers'],
            (string) $scope['packing'],
            ['handling' => $handling, 'included_floors' => $handling === 'not_needed' ? 0 : (int) ($stairs['included_floors'] ?? 0)],
            (string) $scope['assembly'],
            $equipment,
            (int) $scope['waiting_allowance_minutes'],
            (int) $scope['extra_stops_included'],
            (string) $scope['tolls'],
            (string) $scope['parking'],
            trim((string) ($scope['exclusions'] ?? '')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'vehicle_class' => $this->vehicleClass,
            'helpers' => $this->helpers,
            'packing' => $this->packing,
            'stairs' => $this->stairs,
            'assembly' => $this->assembly,
            'loading_equipment' => $this->loadingEquipment,
            'waiting_allowance_minutes' => $this->waitingAllowanceMinutes,
            'extra_stops_included' => $this->extraStopsIncluded,
            'tolls' => $this->tolls,
            'parking' => $this->parking,
            'exclusions' => $this->exclusions,
        ];
    }
}
