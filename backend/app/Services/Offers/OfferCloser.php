<?php

namespace App\Services\Offers;

use App\Enums\Booking\OfferStatus;
use App\Models\Booking;
use App\Models\BookingOffer;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The one place that ends offers in bulk: one conditional update (only still-active rows change, so running it twice
 * changes nothing), then one event per offer that really ended. Callers hold the booking's row lock and have locked
 * the offers they pass in.
 */
final class OfferCloser
{
    public function __construct(
        private readonly OfferRepositoryInterface $offers,
        private readonly OfferNotifier $notifier,
    ) {}

    /**
     * @param Collection<int, BookingOffer> $offers
     * @return int offers ended
     */
    public function close(Booking $booking, Collection $offers, OfferStatus $status, string $reason, ?CarbonImmutable $now = null): int
    {
        $open = $offers->filter(static fn (BookingOffer $offer): bool => $offer->status->isActive())->values();

        if ($open->isEmpty()) {
            return 0;
        }

        $closed = $this->offers->close($open->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all(), $status, $reason, $now ?? CarbonImmutable::now());

        foreach ($open as $offer) {
            $offer->forceFill(['status' => $status, 'active_marker' => null, 'close_reason' => $reason]);
            $this->notifier->closed($booking, $offer, $status);
        }

        return $closed;
    }
}
