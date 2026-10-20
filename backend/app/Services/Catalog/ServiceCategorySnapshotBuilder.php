<?php

namespace App\Services\Catalog;

use App\Models\ServiceCategory;
use App\Models\VehicleCategory;

/**
 * Builds the immutable snapshot written on publish (category configuration, resolved policies, linked vehicle
 * categories and form schema), and fingerprints snapshots so the admin can tell whether the draft differs from
 * the published version.
 */
final class ServiceCategorySnapshotBuilder
{
    public function __construct(private readonly CategoryPolicyResolver $policies) {}

    /**
     * @param list<array<string, mixed>> $schemaFields canonical fields of the draft schema
     * @return array<string, mixed>
     */
    public function build(ServiceCategory $category, array $schemaFields): array
    {
        $category->loadMissing(['family', 'vehicleCategories']);
        $family = $category->family;
        $policy = $this->policies->resolve($category);

        return [
            'category' => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->getTranslations('name'),
                'description' => $category->getTranslations('description'),
                'icon' => $category->icon,
                'map_marker' => $category->map_marker,
                'sort_order' => $category->sort_order,
                'family' => [
                    'id' => $family->id,
                    'code' => $family->code,
                    'kind' => $family->kind->value,
                    'name' => $family->getTranslations('name'),
                    'icon' => $family->icon,
                ],
            ],
            'booking' => [
                'allowed_booking_types' => $category->allowed_booking_types,
                'supports_stops' => $category->supports_stops,
                'max_stops' => $category->max_stops,
                'service_hours' => $category->service_hours ?? [],
            ],
            'pricing' => [
                'negotiation_enabled' => $category->negotiation_enabled,
                'reference_estimate_enabled' => $category->reference_estimate_enabled,
                'min_customer_fare_minor' => $category->min_customer_fare_minor,
                'max_customer_fare_minor' => $category->max_customer_fare_minor,
                'counter_offer_min_pct' => $category->counter_offer_min_pct,
                'counter_offer_max_pct' => $category->counter_offer_max_pct,
            ],
            'dispatch' => [
                'offer_ttl_seconds' => $policy->offerTtlSeconds,
                'search_ttl_seconds' => $policy->searchTtlSeconds,
                'dispatch_radius_steps_km' => $policy->dispatchRadiusStepsKm,
                'dispatch_batch_size' => $policy->dispatchBatchSize,
            ],
            'limits' => [
                'max_passengers' => $category->max_passengers,
                'max_luggage' => $category->max_luggage,
                'max_weight_kg' => $category->max_weight_kg,
                'max_volume_m3' => $category->max_volume_m3,
            ],
            'safety' => [
                'pin_required' => $policy->pinRequired,
                'pickup_proof_required' => $category->pickup_proof_required,
                'delivery_proof_types' => $category->delivery_proof_types,
                'recipient_otp_required' => $category->recipient_otp_required,
            ],
            'payments' => [
                'payment_methods' => $category->payment_methods,
                'cash_allowed' => $category->cash_allowed,
                'tipping_enabled' => $category->tipping_enabled,
                'coupon_eligible' => $category->coupon_eligible,
            ],
            'vehicle_categories' => $category->vehicleCategories
                ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                ->map(static fn (VehicleCategory $vehicle): array => [
                    'id' => $vehicle->id,
                    'code' => $vehicle->code,
                    'name' => $vehicle->getTranslations('name'),
                    'icon' => $vehicle->icon,
                    'map_marker' => $vehicle->map_marker,
                    'passenger_capacity' => $vehicle->passenger_capacity,
                    'luggage_capacity' => $vehicle->luggage_capacity,
                    'max_payload_kg' => $vehicle->max_payload_kg,
                    'max_volume_m3' => $vehicle->max_volume_m3,
                ])
                ->values()
                ->all(),
            'form_schema' => ['fields' => $schemaFields],
        ];
    }

    /**
     * Key-order independent hash (MySQL JSON columns reorder object keys).
     *
     * @param array<string, mixed> $snapshot
     */
    public function fingerprint(array $snapshot): string
    {
        /** @var mixed $normalised */
        $normalised = json_decode((string) json_encode($snapshot), true);

        return hash('sha256', (string) json_encode(self::sortKeys($normalised)));
    }

    private static function sortKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $value = array_map(self::sortKeys(...), $value);

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
