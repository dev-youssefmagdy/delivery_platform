<?php

namespace App\Services\Booking;

use App\DTOs\Booking\ValidatedBookingRequest;
use App\Enums\Settings\SettingKey;
use App\Services\Settings\SettingsService;

/**
 * Copies everything a booking was validated and priced with into JSON that never changes afterwards (Plan I4): the
 * localized names, the category version's policies and limits, the zone overrides, the settings that govern offers and
 * the fare policy values. Admin edits to any of these only affect future bookings.
 */
final class BookingSnapshotBuilder
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * The request-dependent columns of a booking: places, schedule, details, fare, estimate and the two snapshots.
     * Used for creation and again, in full, when the customer changes the request.
     *
     * @return array<string, mixed>
     */
    public function requestAttributes(ValidatedBookingRequest $request): array
    {
        return [
            'service_category_id' => $request->category->id,
            'service_category_version_id' => $request->version->id,
            'city_id' => $request->area->city?->id,
            'service_zone_id' => $request->area->zone?->id,
            'fare_policy_id' => $request->farePolicy?->id,
            'booking_type' => $request->bookingType,
            'pickup_point' => $request->pickup->point,
            'pickup_address' => $request->pickup->addressOrNull(),
            'dropoff_point' => $request->dropoff?->point,
            'dropoff_address' => $request->dropoff?->addressOrNull(),
            'scheduled_at' => $request->scheduledAt,
            'passengers' => $request->passengers,
            'luggage_count' => $request->luggageCount,
            'cargo_weight_kg' => $request->cargoWeightKg,
            'service_details' => $request->serviceDetails,
            'proposed_fare' => $request->proposedFare,
            'reference_estimate' => $request->guidance->toSnapshot(),
            'route_snapshot' => $request->route?->toArray(),
            'service_snapshot' => $this->serviceSnapshot($request),
            'policy_snapshot' => $this->policySnapshot($request),
            'payment_method' => $request->paymentMethod,
        ];
    }

    /**
     * Names and identity of what was booked (what lists and receipts show).
     *
     * @return array<string, mixed>
     */
    public function serviceSnapshot(ValidatedBookingRequest $request): array
    {
        /** @var array<string, mixed> $snapshot */
        $snapshot = $request->version->snapshot;
        $zone = $request->area->zone;
        $city = $request->area->city;

        return [
            'category' => $snapshot['category'] ?? [],
            'category_version' => ['id' => $request->version->id, 'uuid' => $request->version->uuid, 'version' => $request->version->version],
            'booking_type' => $request->bookingType->value,
            'vehicle_categories' => $snapshot['vehicle_categories'] ?? [],
            'zone' => $zone === null ? null : ['id' => $zone->id, 'name' => $zone->getTranslations('name')],
            'city' => $city === null ? null : ['id' => $city->id, 'name' => $city->getTranslations('name'), 'timezone' => $city->timezone],
        ];
    }

    /**
     * The rules the booking runs under.
     *
     * @return array<string, mixed>
     */
    public function policySnapshot(ValidatedBookingRequest $request): array
    {
        /** @var array<string, mixed> $snapshot */
        $snapshot = $request->version->snapshot;
        $payments = (array) ($snapshot['payments'] ?? []);

        return [
            'dispatch' => (array) ($snapshot['dispatch'] ?? []) + ['auto_redispatch' => $this->settings->bool(SettingKey::AutoRedispatchOnProviderCancel)],
            'offers' => [
                'max_per_provider' => $this->settings->int(SettingKey::MaxOffersPerProviderPerBooking),
                'max_revisions' => $this->settings->int(SettingKey::MaxOfferRevisions),
                'negotiation_enabled' => (bool) ($snapshot['pricing']['negotiation_enabled'] ?? true),
                'counter_offer_min_pct' => $snapshot['pricing']['counter_offer_min_pct'] ?? null,
                'counter_offer_max_pct' => $snapshot['pricing']['counter_offer_max_pct'] ?? null,
            ],
            'fare_bounds' => $request->fareBounds,
            'booking' => (array) ($snapshot['booking'] ?? []),
            'limits' => (array) ($snapshot['limits'] ?? []),
            'safety' => (array) ($snapshot['safety'] ?? []),
            'payments' => [
                'payment_methods' => $payments['payment_methods'] ?? [],
                'cash_allowed' => $payments['cash_allowed'] ?? true,
                'tipping_enabled' => $payments['tipping_enabled'] ?? false,
                'coupon_eligible' => $payments['coupon_eligible'] ?? false,
            ],
            'zone' => $request->area->zone === null ? null : [
                'id' => $request->area->zone->id,
                'restricted_pickup' => $request->area->zone->restricted_pickup,
                'overrides' => $request->availability->overrides,
            ],
            'fare_policy' => $request->farePolicy?->snapshot(),
            'stops_count' => count($request->stops),
            'captured_at' => $request->validatedAt->toIso8601String(),
        ];
    }
}
