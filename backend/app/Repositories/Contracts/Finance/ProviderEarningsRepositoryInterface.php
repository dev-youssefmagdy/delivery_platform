<?php

namespace App\Repositories\Contracts\Finance;

use App\Models\Booking;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read side of a provider's earnings: the settled bookings (those with a stored price breakdown) of one provider.
 */
interface ProviderEarningsRepositoryInterface
{
    /**
     * Settled bookings completed in [from, to), oldest first, with only the columns earnings need.
     *
     * @return Collection<int, Booking>
     */
    public function settledBetween(int $providerId, CarbonInterface $from, CarbonInterface $to, ?string $currency = null): Collection;

    /**
     * @return LengthAwarePaginator<int, Booking>
     */
    public function transactionsPage(int $providerId, ?CarbonInterface $from, ?CarbonInterface $to, int $perPage): LengthAwarePaginator;
}
