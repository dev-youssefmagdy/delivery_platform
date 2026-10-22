<?php

namespace App\Services\Booking;

use App\DTOs\Booking\BookingLocationData;
use App\DTOs\Booking\CreateBookingData;
use App\DTOs\Booking\TransitionContext;
use App\DTOs\Booking\UpdateBookingRequestData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\OfferInvalidationReason;
use App\Events\Booking\BookingEnteredSearch;
use App\Events\Realtime\BookingRequestChanged;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenActionException;
use App\Models\Booking;
use App\Models\BookingStop;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingStopRepositoryInterface;
use App\Services\Courier\BookingContactService;
use App\Services\Dispatch\EventSequencer;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * PATCH /bookings/{booking}/request (SRS §6.5): while the booking is still searching the customer may change the
 * route, details, schedule or the proposed fare. The whole request is validated again as a new request (against the
 * booking's own category version and today's zone / fare rules), every active offer is invalidated
 * (`invalidated_request_changed`), the search window restarts and dispatch starts over so providers see the new
 * request. The change is recorded as a status-less event with what changed (never the old addresses).
 */
final class UpdateBookingRequestService
{
    public function __construct(
        private readonly BookingRequestValidator $validator,
        private readonly BookingSnapshotBuilder $snapshots,
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingStopRepositoryInterface $stops,
        private readonly BookingOfferRepositoryInterface $offers,
        private readonly BookingTransitionService $transitions,
        private readonly EventSequencer $sequencer,
        private readonly BookingContactService $contacts,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws ConflictException `booking_not_editable` once a provider was selected or the search ended,
     *                           `booking_request_changed_meanwhile` when another change got in first
     */
    public function update(User $customer, string $bookingUuid, UpdateBookingRequestData $data): Booking
    {
        $code = $customer->status->blockCode(strict: true);

        if ($code !== null) {
            throw ForbiddenActionException::make($code);
        }

        $booking = $this->bookings->findOfCustomer($bookingUuid, $customer->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);
        $booking->load(['stops', 'serviceCategoryVersion']);

        if (! $booking->status->isSearching()) {
            throw ConflictException::make('booking_not_editable');
        }

        $now = CarbonImmutable::now();
        $validated = $this->validator->validate($this->merged($booking, $data), $now, $booking->serviceCategoryVersion);
        $changed = $this->changedFields($data);

        return $this->db->transaction(function () use ($customer, $booking, $validated, $changed, $now): Booking {
            $locked = $this->bookings->lockById($booking->id);

            if (! $locked instanceof Booking) {
                throw (new ModelNotFoundException)->setModel(Booking::class);
            }

            if (! $locked->status->isSearching()) {
                throw ConflictException::make('booking_not_editable');
            }

            if ($locked->request_version !== $booking->request_version) {
                throw ConflictException::make('booking_request_changed_meanwhile');
            }

            $this->offers->invalidateActive($locked, OfferInvalidationReason::RequestChanged);

            $ttl = (int) ($locked->policy_snapshot['dispatch']['search_ttl_seconds'] ?? 0);
            $this->bookings->forceUpdate($locked, $this->snapshots->requestAttributes($validated) + [
                'request_version' => $locked->request_version + 1,
                'lock_version' => $locked->lock_version + 1,
                'search_expires_at' => $now->addSeconds($ttl),
            ]);
            $this->contacts->store($locked, $validated->contacts);
            $this->stops->replaceFor($locked, array_map(static fn (BookingLocationData $stop): array => ['point' => $stop->point, 'address' => $stop->addressOrNull()], $validated->stops));

            $context = TransitionContext::customer($customer, 'request_changed');

            if ($locked->status === BookingStatus::OffersReceived) {
                $locked = $this->transitions->transition($locked, BookingStatus::SearchingOffers, $context);
            }

            $this->transitions->annotate($locked, 'request_changed', $context, ['changed' => $changed, 'request_version' => $locked->request_version]);
            BookingRequestChanged::dispatch($locked->uuid, $this->sequencer->next($locked->uuid), ['request_version' => $locked->request_version, 'changed' => $changed]);
            BookingEnteredSearch::dispatch($locked->id, restart: true);

            return $locked->refresh()->load(['stops', 'statusEvents']);
        });
    }

    /**
     * The booking's current request with the customer's changes applied: what gets validated as a whole.
     */
    private function merged(Booking $booking, UpdateBookingRequestData $data): CreateBookingData
    {
        $stops = $data->hasStops
            ? $data->stops
            : $booking->stops->map(static fn (BookingStop $stop): BookingLocationData => new BookingLocationData($stop->point, (array) ($stop->address ?? [])))->all();

        return new CreateBookingData(
            serviceCategoryId: $booking->service_category_id,
            bookingType: $booking->booking_type,
            pickup: $data->pickup ?? new BookingLocationData($booking->pickup_point, (array) ($booking->pickup_address ?? [])),
            dropoff: $data->dropoff ?? ($booking->dropoff_point === null ? null : new BookingLocationData($booking->dropoff_point, (array) ($booking->dropoff_address ?? []))),
            stops: array_values($stops),
            scheduledAt: $data->scheduledAt ?? ($booking->scheduled_at === null ? null : CarbonImmutable::instance($booking->scheduled_at)),
            passengers: $data->passengers ?? $booking->passengers,
            // The stored contacts are part of the request: they are validated again with it and kept unless the customer sends new ones.
            serviceDetails: $data->hasServiceDetails ? $data->serviceDetails : $booking->service_details + $this->contacts->asFormAnswers($booking),
            proposedFareMinor: $data->proposedFareMinor ?? $booking->proposed_fare_minor,
            currency: $data->currency,
            paymentMethod: $booking->payment_method,
        );
    }

    /**
     * @return list<string>
     */
    private function changedFields(UpdateBookingRequestData $data): array
    {
        return array_values(array_filter([
            $data->pickup !== null ? 'pickup' : null,
            $data->dropoff !== null ? 'dropoff' : null,
            $data->hasStops ? 'stops' : null,
            $data->scheduledAt !== null ? 'scheduled_at' : null,
            $data->passengers !== null ? 'passengers' : null,
            $data->hasServiceDetails ? 'service_details' : null,
            $data->proposedFareMinor !== null ? 'proposed_fare' : null,
        ]));
    }
}
