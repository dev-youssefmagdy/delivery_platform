<?php

namespace App\Services\Scope;

use App\DTOs\Booking\BookingAdjustmentData;
use App\DTOs\Scope\OfferScopeData;
use App\Enums\Booking\BookingAdjustmentType;
use App\Enums\Scope\CostTreatment;
use App\Enums\Scope\ScopeItem;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Support\Json\JsonSchemaValidator;

/**
 * Structured scope of moving / freight offers (SRS §7.4, §9.4, Plan P4.2).
 *
 * - Which bookings need a scope: the category's family is `moving` or `freight` (read from the booking's snapshot).
 * - What the customer asked for (`requestedFrom`): derived from the validated service form, stored on the booking and
 *   used verbatim by "accept the customer's fare" offers.
 * - A counter-offer's scope is checked against the JSON schema (`config/offer_scope.php`), must name the vehicle class of
 *   the offering vehicle and is stored canonicalised.
 * - `summary()` is the one human-readable rendering used by BOTH apps, so both sides see the identical accepted scope.
 * - `assertAdjustmentAllowed()` ties post-selection price changes to an item of the agreed scope.
 */
final class OfferScopeService
{
    public function __construct(
        private readonly JsonSchemaValidator $schemas,
        private readonly VehicleCapacityChecker $capacity,
    ) {}

    public function requiredFor(Booking $booking): bool
    {
        $kind = $booking->service_snapshot['category']['family']['kind'] ?? null;

        return in_array($kind, (array) config('offer_scope.required_families', []), true);
    }

    /**
     * @param array<string, mixed> $categorySnapshot the published category version's snapshot
     * @param array<string, mixed> $details validated service details
     * @param array<string, mixed> $cancellation the booking's resolved cancellation snapshot
     * @return array<string, mixed>|null null for categories that do not use scope offers
     */
    public function requestedFrom(array $categorySnapshot, array $details, int $stopsCount, array $cancellation): ?array
    {
        $kind = $categorySnapshot['category']['family']['kind'] ?? null;

        if (! in_array($kind, (array) config('offer_scope.required_families', []), true)) {
            return null;
        }

        $vehicles = (array) ($categorySnapshot['vehicle_categories'] ?? []);
        $floors = max(
            ($details['origin_elevator'] ?? false) ? 0 : (int) ($details['origin_floor'] ?? 0),
            ($details['destination_elevator'] ?? false) ? 0 : (int) ($details['destination_floor'] ?? 0),
        );
        $helpers = isset($details['helpers']) ? (int) $details['helpers'] : (($details['needs_loaders'] ?? false) ? 1 : 0);
        $equipment = array_values(array_filter((array) ($details['loading_equipment'] ?? []), static fn (mixed $item): bool => in_array($item, ['hand_trolley', 'forklift', 'crane', 'tail_lift', 'straps_blankets', 'pallet_jack'], true)));
        $defaults = (array) config('offer_scope.defaults', []);

        return OfferScopeData::fromValidated([
            'vehicle_class' => (string) ($vehicles[0]['code'] ?? 'any'),
            'helpers' => min(10, max(0, $helpers)),
            'packing' => ($details['packing'] ?? false) ? 'full_pack' : 'none',
            'stairs' => ['handling' => $floors > 0 ? 'included' : 'not_needed', 'included_floors' => min(40, $floors)],
            'assembly' => ($details['disassembly'] ?? false) ? 'both' : 'none',
            'loading_equipment' => $equipment,
            'waiting_allowance_minutes' => min(480, intdiv((int) ($cancellation['free_wait_seconds'] ?? 0), 60)),
            'extra_stops_included' => min(10, $stopsCount),
            'tolls' => (string) ($defaults['tolls'] ?? 'at_cost_with_evidence'),
            'parking' => (string) ($defaults['parking'] ?? 'at_cost_with_evidence'),
            'exclusions' => '',
        ], (int) config('offer_scope.version', 1))->toArray();
    }

    /**
     * The scope an offer carries, or null for a category without scope offers.
     *
     * @param array<string, mixed>|null $given what the provider sent (counter-offer only)
     * @return array<string, mixed>|null
     *
     * @throws BusinessRuleViolation `offer_scope_required`, `offer_scope_invalid`, `offer_scope_vehicle_mismatch`
     */
    public function forOffer(Booking $booking, Vehicle $vehicle, bool $isCounterOffer, ?array $given): ?array
    {
        if (! $this->requiredFor($booking)) {
            // Categories without scope offers keep the optional free-form summary of P2.4.
            return $isCounterOffer ? $given : null;
        }

        if (! $isCounterOffer) {
            $requested = (array) ($booking->requested_scope ?? []);

            if ($requested === []) {
                throw BusinessRuleViolation::make('offer_scope_required', 'scope');
            }

            return $requested;
        }

        if ($given === null || $given === []) {
            throw BusinessRuleViolation::make('offer_scope_required', 'scope');
        }

        return $this->validated($given, $vehicle);
    }

    /**
     * @param array<string, mixed> $scope
     * @return array<string, mixed>
     */
    public function validated(array $scope, Vehicle $vehicle): array
    {
        $schema = (array) config('offer_scope.schema');
        $errors = $this->schemas->validate($scope, $schema);

        if ($errors !== []) {
            $first = (string) array_key_first($errors);

            throw BusinessRuleViolation::make('offer_scope_invalid', 'scope'.($first === '' ? '' : '.'.$first), ['scope_errors' => $errors]);
        }

        $vehicle->loadMissing('vehicleCategory');

        if ($scope['vehicle_class'] !== $vehicle->vehicleCategory->code) {
            throw BusinessRuleViolation::make('offer_scope_vehicle_mismatch', 'scope.vehicle_class');
        }

        return OfferScopeData::fromValidated($scope, (int) config('offer_scope.version', 1))->toArray();
    }

    /**
     * The offering vehicle must carry the load (SRS §10.1): weight and volume of the booking against payload and volume.
     *
     * @throws BusinessRuleViolation `offer_vehicle_capacity_insufficient`
     */
    public function assertCapacity(Booking $booking, Vehicle $vehicle): void
    {
        $short = $this->capacity->shortfallsForBooking($vehicle, $booking);

        if ($short !== []) {
            throw BusinessRuleViolation::make('offer_vehicle_capacity_insufficient', 'vehicle', ['shortfalls' => $short]);
        }
    }

    /**
     * The one rendering of a scope both apps show: a list of `{key, label, value}` lines in a fixed order, translated.
     *
     * @param array<string, mixed>|null $scope
     * @return list<array{key: string, label: string, value: string}>
     */
    public function summary(?array $scope): array
    {
        if ($scope === null || $scope === []) {
            return [];
        }

        $stairs = (array) ($scope['stairs'] ?? []);
        $equipment = array_map(static fn (string $item): string => (string) __("scope.equipment.{$item}"), (array) ($scope['loading_equipment'] ?? []));
        $lines = [
            ['vehicle_class', (string) ($scope['vehicle_class'] ?? '')],
            ['helpers', (string) ($scope['helpers'] ?? 0)],
            ['packing', (string) __('scope.packing.'.($scope['packing'] ?? 'none'))],
            ['stairs', (string) __('scope.stairs.'.($stairs['handling'] ?? 'not_needed'), ['floors' => (int) ($stairs['included_floors'] ?? 0)])],
            ['assembly', (string) __('scope.assembly.'.($scope['assembly'] ?? 'none'))],
            ['loading_equipment', $equipment === [] ? (string) __('scope.none') : implode(', ', $equipment)],
            ['waiting_allowance', (string) __('scope.minutes', ['minutes' => (int) ($scope['waiting_allowance_minutes'] ?? 0)])],
            ['extra_stops', (string) ($scope['extra_stops_included'] ?? 0)],
            ['tolls', (string) __('scope.treatment.'.($scope['tolls'] ?? 'excluded'))],
            ['parking', (string) __('scope.treatment.'.($scope['parking'] ?? 'excluded'))],
        ];

        if (trim((string) ($scope['exclusions'] ?? '')) !== '') {
            $lines[] = ['exclusions', (string) $scope['exclusions']];
        }

        return array_map(static fn (array $line): array => ['key' => $line[0], 'label' => (string) __("scope.label.{$line[0]}"), 'value' => $line[1]], $lines);
    }

    /**
     * An adjustment of a scoped booking names the scope item it changes, and the agreed scope decides whether the item can
     * be charged at all (SRS §9.4): tolls / parking only "at cost with evidence", stairs only beyond the included floors.
     *
     * @return array<string, mixed> details to store (the item and the agreed value it refers to)
     *
     * @throws BusinessRuleViolation `adjustment_scope_item_required`, `adjustment_scope_item_invalid`, `adjustment_within_scope`
     */
    public function assertAdjustmentAllowed(Booking $booking, BookingAdjustmentData $data): array
    {
        $scope = (array) ($booking->agreed_scope ?? []);
        $needsScope = in_array($data->type, [BookingAdjustmentType::ExtraHelper, BookingAdjustmentType::Stairs], true);

        if ($scope === []) {
            if ($needsScope) {
                throw BusinessRuleViolation::make('adjustment_type_not_allowed', 'type');
            }

            return [];
        }

        $allowed = (array) config("offer_scope.adjustment_items.{$data->type->value}", []);
        $given = $data->details['scope_item'] ?? null;
        $item = $given === null ? (count($allowed) === 1 ? $allowed[0] : null) : (string) $given;

        if ($item === null) {
            throw BusinessRuleViolation::make('adjustment_scope_item_required', 'details.scope_item');
        }

        if (! in_array($item, $allowed, true) || ScopeItem::tryFrom($item) === null) {
            throw BusinessRuleViolation::make('adjustment_scope_item_invalid', 'details.scope_item');
        }

        $agreed = match ($item) {
            'helpers' => $scope['helpers'] ?? null,
            'stairs' => $scope['stairs']['included_floors'] ?? null,
            'extra_stops' => $scope['extra_stops_included'] ?? null,
            'waiting_allowance' => $scope['waiting_allowance_minutes'] ?? null,
            'tolls' => $scope['tolls'] ?? null,
            'parking' => $scope['parking'] ?? null,
            'packing' => $scope['packing'] ?? null,
            'assembly' => $scope['assembly'] ?? null,
            default => $scope['loading_equipment'] ?? null,
        };

        if (in_array($item, ['tolls', 'parking'], true) && $agreed !== CostTreatment::AtCostWithEvidence->value) {
            throw BusinessRuleViolation::make('adjustment_within_scope', 'details.scope_item', ['scope_item' => $item]);
        }

        if ($item === 'stairs' && $data->type === BookingAdjustmentType::Stairs) {
            $floors = (int) ($data->details['floors'] ?? 0);

            if ($floors < 1) {
                throw BusinessRuleViolation::make('adjustment_floors_required', 'details.floors');
            }

            if (($scope['stairs']['handling'] ?? 'not_needed') === 'included' && $floors <= (int) $agreed) {
                throw BusinessRuleViolation::make('adjustment_within_scope', 'details.floors', ['scope_item' => $item]);
            }
        }

        if ($data->type === BookingAdjustmentType::ExtraHelper) {
            $count = (int) ($data->details['helpers'] ?? 1);

            if ($count < 1 || $count > 5 || (int) $agreed + $count > 10) {
                throw BusinessRuleViolation::make('adjustment_helpers_invalid', 'details.helpers');
            }
        }

        return ['scope_item' => $item, 'agreed_value' => $agreed];
    }
}
