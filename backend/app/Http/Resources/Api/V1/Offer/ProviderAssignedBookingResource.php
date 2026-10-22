<?php

namespace App\Http\Resources\Api\V1\Offer;

use App\Enums\Booking\ProviderBookingAction;
use App\Enums\Common\ActorType;
use App\Enums\Settings\SettingKey;
use App\Http\Resources\Api\V1\Booking\BookingResource;
use App\Models\Booking;
use App\Models\BookingStop;
use App\Services\Booking\CancellationPolicyResolver;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

/**
 * The booking as its SELECTED provider sees it (staged disclosure, Plan I10): full addresses and stops, the service
 * details, the customer's name and phone, the agreed fare and scope. This resource is only built for the provider
 * whose offer was accepted; before selection a provider gets `ProviderRequestCardResource` and nothing else.
 * Needs `customer` and `stops` loaded.
 *
 * @property Booking $resource
 */
class ProviderAssignedBookingResource extends JsonResource
{
    /**
     * @param list<ProviderBookingAction> $allowedActions
     */
    public function __construct(Booking $booking, private readonly array $allowedActions = [])
    {
        parent::__construct($booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $booking = $this->resource;
        $category = (array) ($booking->service_snapshot['category'] ?? []);

        return [
            'id' => $booking->uuid,
            'code' => $booking->code,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'booking_type' => $booking->booking_type->value,
            'service' => [
                'id' => $booking->service_category_id,
                'code' => $category['code'] ?? null,
                'name' => BookingResource::names((array) ($category['name'] ?? [])),
            ],
            'customer' => $this->customer($booking),
            'pickup' => $this->place($booking->pickup_point->lat, $booking->pickup_point->lng, $booking->pickup_address),
            'dropoff' => $booking->dropoff_point === null ? null : $this->place($booking->dropoff_point->lat, $booking->dropoff_point->lng, $booking->dropoff_address),
            'stops' => $booking->stops->map(fn (BookingStop $stop): array => ['position' => $stop->position] + $this->place($stop->point->lat, $stop->point->lng, $stop->address))->values()->all(),
            'scheduled_at' => $booking->scheduled_at?->toIso8601ZuluString(),
            'passengers' => $booking->passengers,
            'service_details' => $booking->service_details === [] ? new stdClass : $booking->service_details,
            'route' => $booking->route_snapshot === null ? null : [
                'distance_m' => $booking->route_snapshot['distance_m'] ?? null,
                'duration_s' => $booking->route_snapshot['duration_s'] ?? null,
                'polyline' => $booking->route_snapshot['polyline'] ?? null,
            ],
            'agreed_fare' => $booking->agreed_fare_minor === null ? null : ['amount_minor' => $booking->agreed_fare_minor, 'currency' => $booking->currency],
            'agreed_scope' => ($booking->agreed_scope ?? []) === [] ? new stdClass : $booking->agreed_scope,
            'payment' => ['method' => $booking->payment_method->value, 'status' => $booking->payment_status->value],
            'pin_required' => (bool) ($booking->policy_snapshot['safety']['pin_required'] ?? false),
            'selected_at' => $booking->provider_selected_at?->toIso8601ZuluString(),
            'trip' => $this->trip($booking),
            'cancellation_terms' => $this->terms($booking),
            'final_fare' => $booking->final_fare_minor === null ? null : ['amount_minor' => $booking->final_fare_minor, 'currency' => $booking->currency],
            // Approved fare adjustments (P3.5) so far, signed; the running total is the agreed fare plus this.
            'adjustments_total' => $booking->agreed_fare_minor === null ? null : ['amount_minor' => $booking->adjustments_total_minor, 'currency' => $booking->currency],
            'current_total' => $booking->agreed_fare_minor === null ? null : ['amount_minor' => max(0, $booking->agreed_fare_minor + $booking->adjustments_total_minor), 'currency' => $booking->currency],
            'allowed_actions' => array_map(static fn (ProviderBookingAction $action): string => $action->value, $this->allowedActions),
        ];
    }

    /**
     * The customer as the assigned provider sees them: name, rating and, per the phone-exposure setting, the real
     * number or only a masked one (a call relay arrives with the communication milestone).
     *
     * @return array<string, mixed>
     */
    private function customer(Booking $booking): array
    {
        $customer = $booking->customer;
        $direct = app(SettingsService::class)->bool(SettingKey::DirectPhoneExposure);
        $phone = (string) $customer->phone_e164;

        return [
            'name' => $customer->name,
            'phone' => $direct ? $phone : null,
            'phone_masked' => $phone === '' ? null : substr($phone, 0, 3).str_repeat('•', max(0, strlen($phone) - 5)).substr($phone, -2),
            'contact_mode' => $direct ? 'direct' : 'masked',
            'rating' => ProviderIdentity::rating((float) $customer->rating_avg, (int) $customer->rating_count),
        ];
    }

    /**
     * Progress and verification state of the trip (what the driver app needs to draw the next step).
     *
     * @return array<string, mixed>
     */
    private function trip(Booking $booking): array
    {
        $required = (bool) ($booking->policy_snapshot['safety']['pin_required'] ?? false);
        $max = app(SettingsService::class)->int(SettingKey::RidePinMaxAttempts);

        return [
            'heading_at' => $booking->heading_at?->toIso8601ZuluString(),
            'arrived_at' => $booking->arrived_at?->toIso8601ZuluString(),
            'arrival_verified' => $booking->arrival_evidence === null ? null : (bool) ($booking->arrival_evidence['verified'] ?? false),
            'free_wait_ends_at' => $booking->free_wait_ends_at?->toIso8601ZuluString(),
            'started_at' => $booking->started_at?->toIso8601ZuluString(),
            'completed_at' => $booking->completed_at?->toIso8601ZuluString(),
            'pin' => [
                'required' => $required,
                'verified' => $booking->pin_verified_at !== null,
                'locked' => $booking->pin_locked_at !== null,
                'attempts_remaining' => $required && $booking->pin_verified_at === null ? max(0, $max - $booking->pin_attempts) : null,
            ],
            'support_flagged' => $booking->support_flagged_at !== null,
        ];
    }

    /**
     * What the provider is told about cancelling this booking: free waiting time, the reasons it may use and what its
     * own cancellation costs at each stage (from the policy the booking was created under).
     *
     * @return array<string, mixed>
     */
    private function terms(Booking $booking): array
    {
        $policy = (array) ($booking->policy_snapshot['cancellation'] ?? CancellationPolicyResolver::defaults());
        $rules = array_values(array_filter((array) ($policy['rules'] ?? []), static fn (mixed $rule): bool => ($rule['actor'] ?? null) === ActorType::Provider->value));

        return [
            'free_wait_seconds' => (int) ($policy['free_wait_seconds'] ?? 0),
            'reasons' => array_values((array) ($policy['provider_reasons'] ?? [])),
            'provider_fees' => array_map(static fn (array $rule): array => [
                'stage' => $rule['stage'],
                'fee_type' => $rule['fee_type'],
                'value' => (int) $rule['value'],
                'cap_minor' => $rule['cap_minor'] ?? null,
            ], $rules),
            'redispatch' => (bool) ($booking->policy_snapshot['dispatch']['auto_redispatch'] ?? false),
        ];
    }

    /**
     * @param array<string, mixed>|null $address
     * @return array{lat: float, lng: float, address: array<string, mixed>|null}
     */
    private function place(float $lat, float $lng, ?array $address): array
    {
        return ['lat' => round($lat, 6), 'lng' => round($lng, 6), 'address' => $address];
    }
}
