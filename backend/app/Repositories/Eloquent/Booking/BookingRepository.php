<?php

namespace App\Repositories\Eloquent\Booking;

use App\DTOs\Booking\BookingListFilterData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Catalog\BookingType;
use App\Enums\Dispatch\DispatchResponse;
use App\Models\Booking;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Booking>
 */
final class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{
    private const ROUNDED_POINT = "ST_GeomFromText(CONCAT('POINT(', ROUND(ST_Longitude(%1\$s), 2), ' ', ROUND(ST_Latitude(%1\$s), 2), ')'), 4326, 'axis-order=long-lat')";

    public function __construct(Booking $model)
    {
        parent::__construct($model);
    }

    public function insertDraft(array $attributes): Booking
    {
        $booking = new Booking($attributes);
        $booking->forceFill(['status' => BookingStatus::Draft]);
        $booking->save();

        return $booking;
    }

    public function forceUpdate(Booking $booking, array $attributes): Booking
    {
        $booking->forceFill($attributes)->save();

        return $booking;
    }

    public function codeExists(string $code): bool
    {
        return $this->query()->where('code', $code)->exists();
    }

    public function findOfCustomer(string $uuid, int $customerId): ?Booking
    {
        return $this->query()->where('uuid', $uuid)->where('customer_id', $customerId)->first();
    }

    public function findByUuidForUpdate(string $uuid): ?Booking
    {
        return $this->query()->where('uuid', $uuid)->lockForUpdate()->first();
    }

    public function findByIdempotencyKey(int $customerId, string $key): ?Booking
    {
        return $this->query()->where('customer_id', $customerId)->where('idempotency_key', $key)->first();
    }

    public function customerQuery(int $customerId, BookingListFilterData $filters): Builder
    {
        return $this->query()
            ->where('customer_id', $customerId)
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('status', array_map(static fn (BookingStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->serviceCategoryIds !== [], static fn (Builder $query) => $query->whereIn('service_category_id', $filters->serviceCategoryIds))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('created_at', '<=', $filters->to?->endOfDay()))
            ->when($filters->inFlight !== null, static fn (Builder $query) => $filters->inFlight
                ? $query->whereIn('status', BookingStatus::inFlightValues())
                : $query->whereNotIn('status', BookingStatus::inFlightValues()))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function datatableQueryForCustomer(User $customer): Builder
    {
        return $this->query()
            ->select(['id', 'uuid', 'code', 'customer_id', 'service_category_id', 'booking_type', 'status', 'proposed_fare_minor', 'currency', 'payment_method', 'payment_status', 'service_snapshot', 'created_at'])
            ->where('customer_id', $customer->id);
    }

    public function inFlightOnDemandCount(int $customerId, ?int $exceptId = null): int
    {
        return $this->query()
            ->where('customer_id', $customerId)
            ->where('booking_type', BookingType::OnDemand->value)
            ->whereIn('status', BookingStatus::inFlightValues())
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->count();
    }

    public function searchesExpiredBefore(CarbonInterface $now, int $limit): array
    {
        return array_map('intval', $this->query()
            ->whereIn('status', array_map(static fn (BookingStatus $status): string => $status->value, BookingStatus::searching()))
            ->where('search_expires_at', '<=', $now)
            ->orderBy('search_expires_at')
            ->limit($limit)
            ->pluck('id')
            ->all());
    }

    public function openRequestsForProvider(int $providerProfileId, CarbonInterface $now, int $limit): Collection
    {
        return $this->query()
            ->join('booking_dispatch_recipients as recipient', 'recipient.booking_id', '=', 'bookings.id')
            ->where('recipient.provider_profile_id', $providerProfileId)
            ->whereIn('recipient.response', [DispatchResponse::Pending->value, DispatchResponse::Offered->value])
            ->whereIn('bookings.status', BookingStatus::searchingValues())
            ->where('bookings.search_expires_at', '>', $now)
            ->select('bookings.*')
            ->selectRaw('recipient.distance_m as recipient_distance_m, recipient.notified_at as recipient_notified_at')
            ->orderByDesc('recipient.notified_at')
            ->orderByDesc('bookings.id')
            ->limit($limit)
            ->get();
    }

    public function findAssignedToProvider(string $uuid, int $providerProfileId): ?Booking
    {
        return $this->query()->where('uuid', $uuid)->where('provider_id', $providerProfileId)->first();
    }

    public function scheduledDueBefore(CarbonInterface $opensBefore, int $limit): array
    {
        return array_map('intval', $this->query()
            ->where('status', BookingStatus::Draft->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $opensBefore)
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->pluck('id')
            ->all());
    }

    public function inFlightCountForCategory(int $serviceCategoryId): int
    {
        return $this->query()->where('service_category_id', $serviceCategoryId)->whereIn('status', BookingStatus::inFlightValues())->count();
    }

    public function inFlightCountForZone(int $serviceZoneId): int
    {
        return $this->query()->where('service_zone_id', $serviceZoneId)->whereIn('status', BookingStatus::inFlightValues())->count();
    }

    public function inFlightCountForCustomer(int $customerId): int
    {
        return $this->query()->where('customer_id', $customerId)->whereIn('status', BookingStatus::inFlightValues())->count();
    }

    public function assignedCountForProvider(int $providerProfileId): int
    {
        return $this->query()->where('provider_id', $providerProfileId)->whereIn('status', $this->assignedValues())->count();
    }

    public function currentForProvider(int $providerProfileId): ?Booking
    {
        return $this->query()
            ->where('provider_id', $providerProfileId)
            ->whereIn('status', $this->assignedValues())
            ->orderByDesc('id')
            ->first();
    }

    public function exportForCustomer(int $customerId): Collection
    {
        return $this->query()->with('stops')->where('customer_id', $customerId)->orderBy('id')->get();
    }

    public function scrubPersonalDataForCustomer(int $customerId): int
    {
        return DB::transaction(function () use ($customerId): int {
            $ids = $this->query()->where('customer_id', $customerId)->pluck('id')->all();

            if ($ids === []) {
                return 0;
            }

            DB::table('bookings')->whereIn('id', $ids)->update([
                'pickup_address' => null,
                'dropoff_address' => null,
                'service_details' => '{}',
                'pickup_point' => DB::raw(sprintf(self::ROUNDED_POINT, 'pickup_point')),
                'dropoff_point' => DB::raw('IF(dropoff_point IS NULL, NULL, '.sprintf(self::ROUNDED_POINT, 'dropoff_point').')'),
                'route_snapshot' => DB::raw("JSON_REMOVE(route_snapshot, '\$.polyline')"),
                'updated_at' => now(),
            ]);

            DB::table('booking_stops')->whereIn('booking_id', $ids)->update([
                'address' => null,
                'metadata' => null,
                'point' => DB::raw(sprintf(self::ROUNDED_POINT, 'point')),
                'updated_at' => now(),
            ]);

            return count($ids);
        });
    }

    /**
     * @return list<string>
     */
    private function assignedValues(): array
    {
        return array_map(static fn (BookingStatus $status): string => $status->value, BookingStatus::assigned());
    }
}
