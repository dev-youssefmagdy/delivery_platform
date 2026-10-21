<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The provider's own job history for the driver app (`GET /provider/bookings`), newest first: every booking the
 * provider holds or held to its end. Replaces the app's earlier workaround of building history from selected offers.
 */
final class ProviderJobHistoryService
{
    public function __construct(private readonly BookingRepositoryInterface $bookings) {}

    /**
     * @return LengthAwarePaginator<int, Booking>
     */
    public function page(ProviderProfile $profile, ?int $perPage = null): LengthAwarePaginator
    {
        return $this->bookings->jobHistoryForProvider($profile, max(1, min(50, $perPage ?? (int) config('booking.operations.history_per_page', 20))));
    }
}
