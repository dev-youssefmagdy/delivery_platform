<?php

namespace App\Services\Booking;

use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Enums\Settings\SettingKey;
use App\Exceptions\ConflictException;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;

/**
 * Scheduled bookings wait in `draft` until their search opens (`scheduled_search_lead_minutes` before the time), so
 * providers are not asked days ahead and the search window does not expire before the trip is near. Idempotent: a
 * booking that was cancelled or released meanwhile is skipped.
 */
final class ScheduledBookingReleaseService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingTransitionService $transitions,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return int bookings that started searching
     */
    public function releaseDue(?int $limit = null): int
    {
        $now = CarbonImmutable::now();
        $opensBefore = $now->addMinutes($this->settings->int(SettingKey::ScheduledSearchLeadMinutes));
        $released = 0;

        foreach ($this->bookings->scheduledDueBefore($opensBefore, $limit ?? (int) config('booking.sweep_batch', 200)) as $id) {
            $booking = $this->bookings->find($id);

            if (! $booking instanceof Booking || $booking->status !== BookingStatus::Draft) {
                continue;
            }

            try {
                $this->transitions->transition($booking, BookingStatus::SearchingOffers, TransitionContext::system('scheduled_release'));
                $released++;
            } catch (ConflictException) {
                continue;
            }
        }

        return $released;
    }
}
