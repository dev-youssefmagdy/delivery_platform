<?php

namespace App\Http\Resources\Api\V1\Booking;

use App\DTOs\Booking\OffersSummary;
use App\Enums\Booking\BookingAction;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\ActorType;
use App\Enums\Pricing\PriceFactor;
use App\Enums\Settings\SettingKey;
use App\Http\Resources\Api\V1\Catalog\TranslationMap;
use App\Http\Resources\Api\V1\Offer\CustomerOfferResource;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Models\BookingStatusEvent;
use App\Models\BookingStop;
use App\Services\Courier\BookingContactService;
use App\Services\Courier\DeliveryStateService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;
use stdClass;

/**
 * Customer view of a booking (GET /bookings/{booking}, POST /bookings). Everything comes from the stored booking and
 * its snapshots; the customer's own addresses are shown, provider data is added by the offer / trip stages (P2.4+) and
 * never appears here before then. `allowed_actions` and `offers` are computed by the server and passed in.
 *
 * @property Booking $resource
 */
class BookingResource extends JsonResource
{
    /**
     * @param list<BookingAction> $allowedActions
     */
    public function __construct(Booking $booking, private readonly array $allowedActions = [], private readonly ?OffersSummary $offers = null, private readonly ?BookingOffer $selectedOffer = null)
    {
        parent::__construct($booking);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $booking = $this->resource;

        return BookingSummaryResource::summary($booking) + [
            'pickup' => self::place($booking->pickup_point->lat, $booking->pickup_point->lng, $booking->pickup_address),
            'dropoff' => $booking->dropoff_point === null ? null : self::place($booking->dropoff_point->lat, $booking->dropoff_point->lng, $booking->dropoff_address),
            'stops' => $booking->stops->map(static fn (BookingStop $stop): array => ['position' => $stop->position] + self::place($stop->point->lat, $stop->point->lng, $stop->address))->values()->all(),
            'scheduled_at' => $booking->scheduled_at?->toIso8601ZuluString(),
            'passengers' => $booking->passengers,
            'service_details' => $booking->service_details === [] ? new stdClass : $booking->service_details,
            'reference_estimate' => self::estimate($booking->reference_estimate),
            'route' => $booking->route_snapshot === null ? null : [
                'distance_m' => $booking->route_snapshot['distance_m'] ?? null,
                'duration_s' => $booking->route_snapshot['duration_s'] ?? null,
                'polyline' => $booking->route_snapshot['polyline'] ?? null,
            ],
            'search_expires_at' => $booking->search_expires_at?->toIso8601ZuluString(),
            'request_version' => $booking->request_version,
            'offers' => ($this->offers ?? new OffersSummary)->toArray(),
            'provider' => $this->selectedOffer === null ? null : (new CustomerOfferResource($this->selectedOffer))->resolve()['provider'],
            'vehicle' => $this->selectedOffer === null ? null : (new CustomerOfferResource($this->selectedOffer))->resolve()['vehicle'],
            'agreed_fare' => $booking->agreed_fare_minor === null ? null : ['amount_minor' => $booking->agreed_fare_minor, 'currency' => $booking->currency],
            'agreed_scope' => ($booking->agreed_scope ?? []) === [] ? new stdClass : $booking->agreed_scope,
            'trip_pin' => $this->tripPin($booking),
            'trip' => $this->trip($booking),
            // Parcel contacts the customer typed (phone masked) and the custody / proof / attempt state (P4.1); null for rides.
            'contacts' => app(BookingContactService::class)->forCustomer($booking),
            'delivery' => app(DeliveryStateService::class)->build($booking),
            'cancellation' => $this->cancellation($booking),
            'final_fare' => $booking->final_fare_minor === null ? null : ['amount_minor' => $booking->final_fare_minor, 'currency' => $booking->currency],
            // Approved fare adjustments (P3.5) so far, signed; the running total is the agreed fare plus this.
            'adjustments_total' => $booking->agreed_fare_minor === null ? null : ['amount_minor' => $booking->adjustments_total_minor, 'currency' => $booking->currency],
            'current_total' => $booking->agreed_fare_minor === null ? null : ['amount_minor' => max(0, $booking->agreed_fare_minor + $booking->adjustments_total_minor), 'currency' => $booking->currency],
            'receipt' => $this->receipt($booking),
            'allowed_actions' => array_map(static fn (BookingAction $action): string => $action->value, $this->allowedActions),
            'timeline' => $booking->statusEvents->map(static fn (BookingStatusEvent $event): array => self::timelineEntry($event))->values()->all(),
        ];
    }

    /**
     * The PIN the customer tells the driver at pickup: visible only while the driver is on the way or waiting, and
     * gone once the driver has entered it correctly.
     */
    private function tripPin(Booking $booking): ?string
    {
        if ($booking->pin_encrypted === null || $booking->pin_verified_at !== null || ! in_array($booking->status, [BookingStatus::ProviderSelected, BookingStatus::ProviderHeadingToPickup, BookingStatus::ProviderArrived], true)) {
            return null;
        }

        return Crypt::decryptString($booking->pin_encrypted);
    }

    /**
     * Progress of the trip after selection. The provider's live position is available (on the customer's own channel)
     * only while the provider serves the booking, from selection up to completion.
     *
     * @return array<string, mixed>
     */
    private function trip(Booking $booking): array
    {
        return [
            'selected_at' => $booking->provider_selected_at?->toIso8601ZuluString(),
            'heading_at' => $booking->heading_at?->toIso8601ZuluString(),
            'arrived_at' => $booking->arrived_at?->toIso8601ZuluString(),
            'free_wait_ends_at' => $booking->free_wait_ends_at?->toIso8601ZuluString(),
            'started_at' => $booking->started_at?->toIso8601ZuluString(),
            'completed_at' => $booking->completed_at?->toIso8601ZuluString(),
            'pin' => [
                'required' => (bool) ($booking->policy_snapshot['safety']['pin_required'] ?? false),
                'verified' => $booking->pin_verified_at !== null,
                'locked' => $booking->pin_locked_at !== null,
            ],
            'provider_location_visible' => $booking->status->isAssigned(),
            'support_flagged' => $booking->support_flagged_at !== null,
            'rating_window_ends_at' => $booking->completed_at?->toImmutable()->addHours(app(SettingsService::class)->int(SettingKey::RatingWindowHours))->toIso8601ZuluString(),
        ];
    }

    /**
     * How a cancelled booking ended and what the cancellation cost, as decided and snapshotted at the time.
     *
     * @return array<string, mixed>|null
     */
    private function cancellation(Booking $booking): ?array
    {
        if ($booking->cancelled_at === null) {
            return null;
        }

        return [
            'by' => $booking->cancelled_by_type,
            'reason_code' => $booking->cancellation_reason_code,
            'fee' => ['amount_minor' => (int) ($booking->cancellation_fee_minor ?? 0), 'currency' => $booking->currency],
            'basis' => $booking->cancellation_decision['basis'] ?? null,
            'cancelled_at' => $booking->cancelled_at->toIso8601ZuluString(),
        ];
    }

    /**
     * Receipt basics once the trip is finished (SRS §6.7): the agreed and final amounts, payment state, times and the
     * planned route size and, with a coupon, the server-computed discount and what the customer pays. The discount is never taken
     * from the client (Plan I12).
     *
     * @return array<string, mixed>|null
     */
    private function receipt(Booking $booking): ?array
    {
        if (! in_array($booking->status, [BookingStatus::Completed, BookingStatus::CompletedPendingPayment, BookingStatus::Disputed], true)) {
            return null;
        }

        return [
            'booking_code' => $booking->code,
            'agreed_fare' => ['amount_minor' => $booking->agreed_fare_minor, 'currency' => $booking->currency],
            'final_fare' => ['amount_minor' => $booking->final_fare_minor, 'currency' => $booking->currency],
            'payment' => ['method' => $booking->payment_method->value, 'status' => $booking->payment_status->value],
            'coupon_applied' => $booking->coupon_reservation_id !== null && (int) ($booking->price_breakdown['discount_minor'] ?? 0) > 0,
            'discount' => ['amount_minor' => (int) ($booking->price_breakdown['discount_minor'] ?? 0), 'currency' => $booking->currency],
            'customer_payable' => isset($booking->price_breakdown['customer_payable_minor']) ? ['amount_minor' => (int) $booking->price_breakdown['customer_payable_minor'], 'currency' => $booking->currency] : null,
            'started_at' => $booking->started_at?->toIso8601ZuluString(),
            'completed_at' => $booking->completed_at?->toIso8601ZuluString(),
            'duration_s' => $booking->started_at !== null && $booking->completed_at !== null ? (int) $booking->started_at->diffInSeconds($booking->completed_at) : null,
            'planned_distance_m' => $booking->route_snapshot['distance_m'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed>|null $address
     * @return array{lat: float, lng: float, address: array<string, mixed>|null}
     */
    private static function place(float $lat, float $lng, ?array $address): array
    {
        return ['lat' => round($lat, 6), 'lng' => round($lng, 6), 'address' => $address];
    }

    /**
     * Stored estimate (locale independent) to the API shape with localised factor labels.
     *
     * @param array<string, mixed>|null $estimate
     * @return array<string, mixed>|null
     */
    private static function estimate(?array $estimate): ?array
    {
        if ($estimate === null) {
            return null;
        }

        if (! ($estimate['available'] ?? false)) {
            return ['available' => false, 'reason' => $estimate['reason'] ?? null];
        }

        $locale = app()->getLocale();

        return [
            'available' => true,
            'currency' => $estimate['currency'],
            'low_minor' => $estimate['low_minor'],
            'recommended_minor' => $estimate['recommended_minor'],
            'high_minor' => $estimate['high_minor'],
            'formula_version' => $estimate['formula_version'],
            'factors' => array_map(static function (array $factor) use ($locale): array {
                $custom = is_array($factor['label'] ?? null) ? ($factor['label'][$locale] ?? $factor['label']['ar'] ?? null) : null;

                return [
                    'code' => $factor['code'],
                    'label' => $custom ?? (PriceFactor::tryFrom((string) $factor['code'])?->label() ?? (string) $factor['code']),
                    'amount_minor' => $factor['amount_minor'],
                    'quantity' => $factor['quantity'] ?? null,
                    'unit' => $factor['unit'] ?? null,
                ];
            }, (array) ($estimate['factors'] ?? [])),
            'notice' => (string) __('api.pricing.guidance_notice'),
        ];
    }

    /**
     * A status change or note as the customer may see it: no actor ids, and a reason only for codes the customer or
     * the system wrote (an operations or provider reason can be internal).
     *
     * @return array<string, mixed>
     */
    private static function timelineEntry(BookingStatusEvent $event): array
    {
        $visibleReason = in_array($event->actor_type, [ActorType::Customer->value, ActorType::System->value], true) ? $event->reason : null;

        return [
            'type' => $event->from_status === $event->to_status ? 'note' : 'status',
            // Custody steps of a parcel (picked_up, in_transit, arrived_dropoff, attempt_failed, returning, delivered, returned).
            'custody' => $event->from_status === $event->to_status && str_starts_with((string) $event->reason, 'custody_') ? substr((string) $event->reason, 8) : null,
            'status' => $event->to_status->value,
            'previous_status' => $event->from_status?->value,
            'reason' => $visibleReason,
            'actor' => $event->actor_type,
            'at' => $event->created_at->toIso8601ZuluString(),
            'sequence' => $event->sequence,
        ];
    }

    /**
     * @param array<string, mixed> $names
     * @return array<string, mixed>|stdClass
     */
    public static function names(array $names): array|stdClass
    {
        return TranslationMap::of($names);
    }
}
