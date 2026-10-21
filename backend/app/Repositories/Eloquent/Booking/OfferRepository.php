<?php

namespace App\Repositories\Eloquent\Booking;

use App\DTOs\Booking\OfferFilterData;
use App\DTOs\Operations\OfferStats;
use App\Enums\Booking\OfferAnomaly;
use App\Enums\Booking\OfferStatus;
use App\Models\BookingOffer;
use App\Models\BookingOfferVersion;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Support\Money\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function releaseWinner(int $bookingId, CarbonInterface $now): int
    {
        return $this->query()->where('booking_id', $bookingId)->where('winner_marker', 1)->update([
            'winner_marker' => null,
            'close_reason' => 'provider_cancelled',
            'updated_at' => $now,
        ]);
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

    public function adminDatatableQuery(OfferFilterData $filters): Builder
    {
        return $this->query()
            ->join('bookings', 'bookings.id', '=', 'booking_offers.booking_id')
            ->leftJoin('booking_offer_versions as current_version', 'current_version.id', '=', 'booking_offers.current_version_id')
            ->select('booking_offers.*')
            ->selectRaw('bookings.code as booking_code, bookings.uuid as booking_uuid, bookings.currency as booking_currency, bookings.proposed_fare_minor as customer_fare_minor, current_version.amount_minor as amount_minor, current_version.version as version_number')
            ->selectRaw($this->anomalyExpression().' as anomaly')
            ->with(['providerProfile.user:id,uuid,name,phone_e164'])
            ->when($filters->bookingCode !== null, static fn (Builder $query) => $query->where('bookings.code', 'like', '%'.addcslashes(strtoupper((string) $filters->bookingCode), '%_\\').'%'))
            ->when($filters->providerIds !== [], static fn (Builder $query) => $query->whereIn('booking_offers.provider_profile_id', $filters->providerIds))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('booking_offers.status', array_map(static fn (OfferStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->kinds !== [], static fn (Builder $query) => $query->whereIn('booking_offers.kind', array_map(static fn ($kind): string => $kind->value, $filters->kinds)))
            ->when($filters->amountMinMinor !== null, static fn (Builder $query) => $query->where('current_version.amount_minor', '>=', $filters->amountMinMinor))
            ->when($filters->amountMaxMinor !== null, static fn (Builder $query) => $query->where('current_version.amount_minor', '<=', $filters->amountMaxMinor))
            ->when($filters->anomaly instanceof OfferAnomaly, fn (Builder $query) => $query->whereRaw('('.$this->anomalyExpression().') = ?', [$filters->anomaly?->value]))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('booking_offers.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('booking_offers.created_at', '<=', $filters->to?->endOfDay()));
    }

    public function statsFor(OfferFilterData $filters): OfferStats
    {
        $base = DB::query()->fromSub($this->adminDatatableQuery($filters)->reorder()->toBase(), 'monitored');

        $row = (clone $base)->selectRaw(
            'count(*) as submitted, '
            .'coalesce(sum(status = ?), 0) as selected, coalesce(sum(status = ?), 0) as expired, coalesce(sum(status = ?), 0) as withdrawn, '
            .'coalesce(sum(anomaly is not null), 0) as flagged, count(distinct booking_id) as bookings_with_offers',
            [OfferStatus::AcceptedByCustomer->value, OfferStatus::Expired->value, OfferStatus::WithdrawnByProvider->value],
        )->first();

        $bookings = DB::table('bookings')
            ->when($filters->from !== null, static fn ($query) => $query->where('created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn ($query) => $query->where('created_at', '<=', $filters->to?->endOfDay()))
            ->count();

        return new OfferStats(
            submitted: (int) ($row->submitted ?? 0),
            selected: (int) ($row->selected ?? 0),
            expired: (int) ($row->expired ?? 0),
            withdrawn: (int) ($row->withdrawn ?? 0),
            flagged: (int) ($row->flagged ?? 0),
            bookingsWithOffers: (int) ($row->bookings_with_offers ?? 0),
            bookingsTotal: $bookings,
        );
    }

    public function allForBookingWithVersions(int $bookingId): Collection
    {
        return $this->query()
            ->where('booking_id', $bookingId)
            ->with(['versions', 'providerProfile.user:id,uuid,name,phone_e164', 'vehicle.vehicleCategory'])
            ->orderBy('id')
            ->get();
    }

    /**
     * SQL of the anomaly flag: the offer's current amount against `low_minor` / `high_minor` of the reference estimate
     * stored on the booking. Ratios come from config (floats formatted here, never from user input).
     */
    private function anomalyExpression(): string
    {
        $low = number_format((float) config('booking.operations.anomaly_low_ratio', 0.5), 4, '.', '');
        $high = number_format((float) config('booking.operations.anomaly_high_ratio', 2.0), 4, '.', '');
        $refLow = "CAST(JSON_UNQUOTE(JSON_EXTRACT(bookings.reference_estimate, '\$.low_minor')) AS UNSIGNED)";
        $refHigh = "CAST(JSON_UNQUOTE(JSON_EXTRACT(bookings.reference_estimate, '\$.high_minor')) AS UNSIGNED)";

        return "CASE WHEN current_version.amount_minor IS NULL THEN NULL "
            ."WHEN {$refLow} IS NOT NULL AND current_version.amount_minor < {$refLow} * {$low} THEN '".OfferAnomaly::VeryLow->value."' "
            ."WHEN {$refHigh} IS NOT NULL AND current_version.amount_minor > {$refHigh} * {$high} THEN '".OfferAnomaly::VeryHigh->value."' "
            .'ELSE NULL END';
    }
}
