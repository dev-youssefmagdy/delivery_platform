<?php

namespace App\Services\Offers;

use App\DTOs\Provider\EligibilityResult;
use App\Enums\Provider\EligibilityScope;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Repositories\Contracts\Dispatch\DispatchRecipientRepositoryInterface;
use App\Services\Provider\ProviderEligibilityService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * What a provider may read about bookings: the open requests it was dispatched (masked cards, staged disclosure,
 * Plan I10), its own offers, and, only after the customer chose it, the full booking.
 */
final class ProviderRequestService
{
    private const LIMIT = 50;

    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly OfferRepositoryInterface $offers,
        private readonly DispatchRecipientRepositoryInterface $recipients,
        private readonly ProviderEligibilityService $eligibility,
    ) {}

    /**
     * Requests the provider can still answer: dispatched to it, searching, window open, and only while the provider
     * itself is eligible to work (an ineligible provider gets an empty list and the typed reasons).
     *
     * @return array{requests: Collection<int, Booking>, offers: Collection<int, BookingOffer>, eligibility: EligibilityResult}
     */
    public function available(ProviderProfile $provider): array
    {
        $result = $this->eligibility->check($provider, null, EligibilityScope::Working);

        if (! $result->isEligible()) {
            return ['requests' => new Collection, 'offers' => new Collection, 'eligibility' => $result];
        }

        $requests = $this->bookings->openRequestsForProvider($provider->id, CarbonImmutable::now(), self::LIMIT);

        foreach ($requests as $request) {
            $this->recipients->markSeen($request->id, $provider->id);
        }

        return [
            'requests' => $requests,
            'offers' => $this->offers->activeOfProviderOn($provider->id, $requests->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all()),
            'eligibility' => $result,
        ];
    }

    /**
     * @return Collection<int, BookingOffer>
     */
    public function offersOf(ProviderProfile $provider): Collection
    {
        return $this->offers->forProvider($provider->id, 100);
    }

    /**
     * Full booking details; only for the provider the customer selected (anybody else: 404, no existence oracle).
     */
    public function assigned(ProviderProfile $provider, string $bookingUuid): Booking
    {
        $booking = $this->bookings->findAssignedToProvider($bookingUuid, $provider->id) ?? throw (new ModelNotFoundException)->setModel(Booking::class);

        return $booking->load(['customer', 'stops']);
    }
}
