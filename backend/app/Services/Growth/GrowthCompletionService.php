<?php

namespace App\Services\Growth;

use App\Enums\Booking\BookingStatus;
use App\Enums\Growth\ReferralAudience;
use App\Enums\System\AppTarget;
use App\Enums\System\FeatureFlagKey;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Growth\GrowthDataRepositoryInterface;
use Illuminate\Database\DatabaseManager;

/**
 * The one hook of a completed booking for all growth features. It runs once per booking (the `growth_processed_bookings` marker is
 * written in the same transaction as the effects, so a failure rolls both back and the queue retries), and only for features whose
 * flag is on for that booking's city. Nothing is counted retroactively for a feature that was off at completion time.
 */
final class GrowthCompletionService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly GrowthDataRepositoryInterface $data,
        private readonly GrowthFeatures $features,
        private readonly ReferralMilestoneService $referrals,
        private readonly LoyaltyService $loyalty,
        private readonly IncentiveService $incentives,
        private readonly DatabaseManager $db,
    ) {}

    public function handle(int $bookingId): void
    {
        $booking = $this->bookings->find($bookingId);

        if (! $booking instanceof Booking || $booking->status !== BookingStatus::Completed) {
            return;
        }

        $cityId = $booking->city_id;
        $customerReferrals = $this->features->referrals(ReferralAudience::Customer, $cityId);
        $providerReferrals = $this->features->referrals(ReferralAudience::Provider, $cityId);
        $loyalty = $this->features->enabled(FeatureFlagKey::Loyalty, AppTarget::Customer, $cityId);
        $incentives = $this->features->enabled(FeatureFlagKey::Incentives, AppTarget::Provider, $cityId);

        if (! ($customerReferrals || $providerReferrals || $loyalty || $incentives)) {
            return;
        }

        $this->db->transaction(function () use ($booking, $customerReferrals, $providerReferrals, $loyalty, $incentives): void {
            if (! $this->data->markBookingProcessed($booking->id)) {
                return;
            }

            if ($customerReferrals || $providerReferrals) {
                $this->referrals->onCompleted($booking, $customerReferrals, $providerReferrals);
            }

            if ($loyalty) {
                $this->loyalty->onCompleted($booking->customer_id);
            }

            if ($incentives) {
                $this->incentives->onCompleted($booking);
            }
        });
    }
}
