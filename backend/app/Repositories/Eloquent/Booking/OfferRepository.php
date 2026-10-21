<?php

namespace App\Repositories\Eloquent\Booking;

use App\Enums\Booking\OfferStatus;
use App\Models\BookingOffer;
use App\Models\BookingOfferVersion;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Support\Money\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<BookingOffer>
 */
final class OfferRepository extends BaseRepository implements OfferRepositoryInterface
{
    public function __construct(BookingOffer $model)
    {
        parent::__construct($model);
    }

    public function createOffer(array $attributes): BookingOffer
    {
        $offer = new BookingOffer($attributes);
        $offer->save();

        return $offer;
    }

    public function addVersion(BookingOffer $offer, Money $amount, ?string $note, ?array $scope, ?string $idempotencyKey): BookingOfferVersion
    {
        $next = (int) BookingOfferVersion::query()->where('offer_id', $offer->id)->max('version') + 1;

        $version = new BookingOfferVersion([
            'offer_id' => $offer->id,
            'version' => $next,
            'amount' => $amount,
            'note' => $note,
            'scope' => $scope,
            'idempotency_key' => $idempotencyKey,
        ]);
        $version->save();

        return $version;
    }

    public function applyState(BookingOffer $offer, array $attributes): BookingOffer
    {
        $offer->forceFill($attributes)->save();

        return $offer;
    }

    public function lockOffer(int $id): ?BookingOffer
    {
        return $this->query()->whereKey($id)->lockForUpdate()->first();
    }

    public function lockActiveForBooking(int $bookingId): Collection
    {
        return $this->query()->where('booking_id', $bookingId)->whereIn('status', OfferStatus::activeValues())->orderBy('id')->lockForUpdate()->get();
    }

    public function lockActiveFor(int $bookingId, int $providerProfileId): ?BookingOffer
    {
        return $this->query()->where('booking_id', $bookingId)->where('provider_profile_id', $providerProfileId)
            ->whereIn('status', OfferStatus::activeValues())->lockForUpdate()->first();
    }

    public function countForProvider(int $bookingId, int $providerProfileId): int
    {
        return $this->query()->where('booking_id', $bookingId)->where('provider_profile_id', $providerProfileId)->count();
    }

    public function findVersionByKey(int $providerProfileId, string $idempotencyKey): ?BookingOfferVersion
    {
        return BookingOfferVersion::query()
            ->where('idempotency_key', $idempotencyKey)
            ->whereIn('offer_id', $this->query()->where('provider_profile_id', $providerProfileId)->select('id'))
            ->first();
    }

    public function accept(BookingOffer $offer, CarbonInterface $now): bool
    {
        try {
            $rows = $this->query()
                ->whereKey($offer->id)
                ->whereIn('status', OfferStatus::activeValues())
                ->where('expires_at', '>', $now)
                ->update([
                    'status' => OfferStatus::AcceptedByCustomer->value,
                    'active_marker' => null,
                    'winner_marker' => 1,
                    'closed_at' => $now,
                    'close_reason' => 'accepted',
                    'updated_at' => $now,
                ]);
        } catch (QueryException $exception) {
            // The unique winner index refused a second winner: the booking was claimed by another transaction.
            if (str_contains($exception->getMessage(), 'booking_offers_one_winner_per_booking')) {
                return false;
            }

            throw $exception;
        }

        return $rows === 1;
    }

    public function close(array $ids, OfferStatus $status, string $reason, CarbonInterface $now): int
    {
        if ($ids === []) {
            return 0;
        }

        return $this->query()->whereIn('id', $ids)->whereIn('status', OfferStatus::activeValues())->update([
            'status' => $status->value,
            'active_marker' => null,
            'closed_at' => $now,
            'close_reason' => $reason,
            'updated_at' => $now,
        ]);
    }

    public function expiredIds(CarbonInterface $now, int $limit): array
    {
        return array_map('intval', $this->query()
            ->whereIn('status', OfferStatus::activeValues())
            ->where('expires_at', '<=', $now)
            ->orderBy('expires_at')
            ->limit($limit)
            ->pluck('id')
            ->all());
    }

    public function activeBookingIdsForProvider(int $providerProfileId): array
    {
        return array_map('intval', $this->query()
            ->where('provider_profile_id', $providerProfileId)
            ->whereIn('status', OfferStatus::activeValues())
            ->distinct()
            ->pluck('booking_id')
            ->all());
    }

    public function openForCustomer(int $bookingId, CarbonInterface $now): Collection
    {
        return $this->query()
            ->where('booking_offers.booking_id', $bookingId)
            ->whereIn('booking_offers.status', OfferStatus::activeValues())
            ->where('booking_offers.expires_at', '>', $now)
            ->join('booking_offer_versions as current_version', 'current_version.id', '=', 'booking_offers.current_version_id')
            ->select('booking_offers.*')
            ->orderBy('current_version.amount_minor')
            ->orderBy('booking_offers.id')
            ->with(['currentVersion', 'providerProfile.user', 'vehicle.vehicleCategory'])
            ->get();
    }

    public function openAmountsExcept(int $bookingId, int $exceptProviderProfileId, CarbonInterface $now): array
    {
        return array_map('intval', $this->query()
            ->where('booking_offers.booking_id', $bookingId)
            ->where('booking_offers.provider_profile_id', '!=', $exceptProviderProfileId)
            ->whereIn('booking_offers.status', OfferStatus::activeValues())
            ->where('booking_offers.expires_at', '>', $now)
            ->join('booking_offer_versions as current_version', 'current_version.id', '=', 'booking_offers.current_version_id')
            ->pluck('current_version.amount_minor')
            ->all());
    }

    public function openSummary(int $bookingId, CarbonInterface $now): array
    {
        $row = $this->query()
            ->where('booking_offers.booking_id', $bookingId)
            ->whereIn('booking_offers.status', OfferStatus::activeValues())
            ->where('booking_offers.expires_at', '>', $now)
            ->join('booking_offer_versions as current_version', 'current_version.id', '=', 'booking_offers.current_version_id')
            ->selectRaw('COUNT(*) as offer_count, MIN(current_version.amount_minor) as lowest_minor')
            ->first();

        return [
            'count' => (int) ($row?->getAttribute('offer_count') ?? 0),
            'lowest_minor' => $row?->getAttribute('lowest_minor') === null ? null : (int) $row->getAttribute('lowest_minor'),
        ];
    }

    public function forProvider(int $providerProfileId, int $limit): Collection
    {
        return $this->query()
            ->where('provider_profile_id', $providerProfileId)
            ->with(['currentVersion', 'booking'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function activeOfProviderOn(int $providerProfileId, array $bookingIds): Collection
    {
        if ($bookingIds === []) {
            return new Collection;
        }

        return $this->query()
            ->where('provider_profile_id', $providerProfileId)
            ->whereIn('booking_id', $bookingIds)
            ->whereIn('status', OfferStatus::activeValues())
            ->with(['currentVersion', 'booking'])
            ->get()
            ->keyBy('booking_id');
    }

    public function loadCard(BookingOffer $offer): BookingOffer
    {
        return $offer->load(['currentVersion', 'providerProfile.user', 'vehicle.vehicleCategory']);
    }
}
