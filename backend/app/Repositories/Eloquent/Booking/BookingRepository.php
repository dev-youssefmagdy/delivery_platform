<?php

namespace App\Repositories\Eloquent\Booking;

use App\DTOs\Booking\BookingAdminFilterData;
use App\DTOs\Booking\BookingListFilterData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\OfferStatus;
use App\Enums\Catalog\BookingType;
use App\Enums\Dispatch\DispatchResponse;
use App\Models\Booking;
use App\Models\VehicleCategory;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Repositories\Eloquent\Concerns\FiltersByPhone;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<Booking>
 */
final class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{
    use FiltersByPhone;

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

    public function customerUuidOfBooking(string $bookingUuid): ?string
    {
        $uuid = DB::table('bookings')->join('users', 'users.id', '=', 'bookings.customer_id')->where('bookings.uuid', $bookingUuid)->value('users.uuid');

        return is_string($uuid) ? $uuid : null;
    }

    public function latestFeeBookingOfCustomer(int $customerId): ?Booking
    {
        return $this->query()->where('customer_id', $customerId)->whereNotNull('cancellation_fee_posted_at')->latest('cancellation_fee_posted_at')->first()
            ?? $this->query()->where('customer_id', $customerId)->latest('id')->first();
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

    public function completedCountForCustomer(int $customerId): int
    {
        return $this->query()->where('customer_id', $customerId)->whereIn('status', [BookingStatus::Completed->value, BookingStatus::CompletedPendingPayment->value])->count();
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
                // GPS evidence of the arrival holds the provider's coordinates at the pickup.
                'arrival_evidence' => null,
                'updated_at' => now(),
            ]);

            // Free text the customer wrote on the trip, and the public links to it, go with the account.
            DB::table('booking_ratings')->whereIn('booking_id', $ids)->where('rater_type', 'customer')->update(['comment' => null, 'updated_at' => now()]);
            DB::table('booking_reports')->whereIn('booking_id', $ids)->where('reporter_type', 'customer')->update(['description' => '[removed]', 'updated_at' => now()]);
            DB::table('booking_share_links')->whereIn('booking_id', $ids)->delete();

            DB::table('booking_stops')->whereIn('booking_id', $ids)->update([
                'address' => null,
                'metadata' => null,
                'point' => DB::raw(sprintf(self::ROUNDED_POINT, 'point')),
                'updated_at' => now(),
            ]);

            return count($ids);
        });
    }

    public function adminDatatableQuery(BookingAdminFilterData $filters): Builder
    {
        return $this->query()
            ->select(['bookings.id', 'bookings.uuid', 'bookings.code', 'bookings.customer_id', 'bookings.provider_id', 'bookings.service_category_id', 'bookings.city_id', 'bookings.service_zone_id', 'bookings.booking_type', 'bookings.status', 'bookings.proposed_fare_minor', 'bookings.agreed_fare_minor', 'bookings.currency', 'bookings.payment_method', 'bookings.payment_status', 'bookings.service_snapshot', 'bookings.support_flagged_at', 'bookings.search_expires_at', 'bookings.created_at'])
            ->with(['customer:id,uuid,name,phone_e164', 'provider.user:id,uuid,name,phone_e164', 'city'])
            ->when($filters->code !== null, static fn (Builder $query) => $query->where('bookings.code', 'like', '%'.addcslashes(strtoupper((string) $filters->code), '%_\\').'%'))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn('bookings.status', array_map(static fn (BookingStatus $status): string => $status->value, $filters->statuses)))
            ->when($filters->serviceCategoryIds !== [], static fn (Builder $query) => $query->whereIn('bookings.service_category_id', $filters->serviceCategoryIds))
            ->when($filters->bookingTypes !== [], static fn (Builder $query) => $query->whereIn('bookings.booking_type', array_map(static fn (BookingType $type): string => $type->value, $filters->bookingTypes)))
            ->when($filters->cityIds !== [], static fn (Builder $query) => $query->whereIn('bookings.city_id', $filters->cityIds))
            ->when($filters->zoneIds !== [], static fn (Builder $query) => $query->whereIn('bookings.service_zone_id', $filters->zoneIds))
            ->when($filters->paymentStatuses !== [], static fn (Builder $query) => $query->whereIn('bookings.payment_status', array_map(static fn ($status): string => $status->value, $filters->paymentStatuses)))
            ->when($filters->paymentMethods !== [], static fn (Builder $query) => $query->whereIn('bookings.payment_method', array_map(static fn ($method): string => $method->value, $filters->paymentMethods)))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('bookings.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('bookings.created_at', '<=', $filters->to?->endOfDay()))
            ->when($filters->customerPhone !== null, fn (Builder $query) => $query->whereHas('customer', fn (Builder $inner) => $this->applyPhoneFilter($inner, (string) $filters->customerPhone)))
            ->when($filters->providerPhone !== null, fn (Builder $query) => $query->whereHas('provider.user', fn (Builder $inner) => $this->applyPhoneFilter($inner, (string) $filters->providerPhone)))
            ->when($filters->providerId !== null, static fn (Builder $query) => $query->where('bookings.provider_id', $filters->providerId))
            ->when($filters->custodyStatuses !== [], static fn (Builder $query) => $query->whereIn('bookings.custody_status', array_map(static fn ($status): string => $status->value, $filters->custodyStatuses)))
            ->when($filters->parcelSizes !== [], static fn (Builder $query) => $query->whereIn('bookings.parcel_size', array_map(static fn ($size): string => $size->value, $filters->parcelSizes)))
            ->when($filters->cargoTypes !== [], static function (Builder $query) use ($filters): void {
                $values = array_map(static fn ($type): string => $type->value, $filters->cargoTypes);
                $query->where(static fn (Builder $inner) => $inner->whereIn('bookings.service_details->cargo_type', $values)->orWhereIn('bookings.service_details->parcel_category', $values));
            })
            ->when($filters->vehicleCategoryIds !== [], static function (Builder $query) use ($filters): void {
                $codes = VehicleCategory::query()->whereIn('id', $filters->vehicleCategoryIds)->pluck('code')->all();
                $query->where(static fn (Builder $inner) => $inner->whereIn('bookings.agreed_scope->vehicle_class', $codes)->orWhereIn('bookings.requested_scope->vehicle_class', $codes));
            })
            ->when($filters->weightMin !== null, static fn (Builder $query) => $query->where('bookings.cargo_weight_kg', '>=', $filters->weightMin))
            ->when($filters->weightMax !== null, static fn (Builder $query) => $query->where('bookings.cargo_weight_kg', '<=', $filters->weightMax))
            ->when($filters->isFragile !== null, static fn (Builder $query) => $query->where('bookings.is_fragile', $filters->isFragile))
            ->when($filters->hasTicket !== null, static function (Builder $query) use ($filters): void {
                $ticket = static fn ($sub) => $sub->select(DB::raw(1))->from('support_tickets')->whereColumn('support_tickets.booking_id', 'bookings.id');

                $filters->hasTicket ? $query->whereExists($ticket) : $query->whereNotExists($ticket);
            })
            ->when($filters->hasIncident !== null, static function (Builder $query) use ($filters): void {
                // Safety cases raised from the apps (P5.1); reports filed before it are tickets (`source = report`).
                $incident = static fn ($sub) => $sub->select(DB::raw(1))->from('safety_incidents')->whereColumn('safety_incidents.booking_id', 'bookings.id');

                $filters->hasIncident
                    ? $query->where(static fn (Builder $inner) => $inner->whereNotNull('bookings.support_flagged_at')->orWhereExists($incident))
                    : $query->whereNull('bookings.support_flagged_at')->whereNotExists($incident);
            });
    }

    public function noOfferQueueQuery(CarbonInterface $searchingBefore, CarbonInterface $expiredSince): Builder
    {
        return $this->query()
            ->select(['bookings.id', 'bookings.uuid', 'bookings.code', 'bookings.customer_id', 'bookings.service_category_id', 'bookings.city_id', 'bookings.booking_type', 'bookings.status', 'bookings.proposed_fare_minor', 'bookings.currency', 'bookings.service_snapshot', 'bookings.search_expires_at', 'bookings.created_at', 'bookings.updated_at'])
            ->selectRaw('(select count(*) from booking_dispatch_recipients r where r.booking_id = bookings.id) as recipients_count')
            ->selectRaw('(select count(*) from booking_offers o where o.booking_id = bookings.id) as offers_count')
            ->with(['customer:id,uuid,name,phone_e164', 'city'])
            ->where(static fn (Builder $query) => $query
                ->where(static fn (Builder $searching) => $searching
                    ->whereIn('bookings.status', BookingStatus::searchingValues())
                    ->whereNull('bookings.provider_id')
                    ->where('bookings.created_at', '<=', $searchingBefore)
                    ->whereNotExists(static fn ($sub) => $sub->select(DB::raw(1))->from('booking_offers')->whereColumn('booking_offers.booking_id', 'bookings.id')->whereIn('booking_offers.status', OfferStatus::activeValues())))
                ->orWhere(static fn (Builder $expired) => $expired
                    ->where('bookings.status', BookingStatus::ExpiredNoProvider->value)
                    ->where('bookings.updated_at', '>=', $expiredSince)));
    }

    public function jobHistoryForProvider(ProviderProfile $profile, int $perPage): LengthAwarePaginator
    {
        return $this->query()
            ->with(['customer:id,uuid,name', 'stops'])
            ->where('provider_id', $profile->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function completedTotalsForProvider(int $providerProfileId): array
    {
        return DB::table('bookings')
            ->where('provider_id', $providerProfileId)
            ->whereIn('status', [BookingStatus::Completed->value, BookingStatus::CompletedPendingPayment->value])
            ->groupBy('currency')
            ->selectRaw('currency, count(*) as jobs, coalesce(sum(final_fare_minor), 0) as gross_minor')
            ->get()
            ->map(static fn (object $row): array => ['currency' => (string) $row->currency, 'jobs' => (int) $row->jobs, 'gross_minor' => (int) $row->gross_minor])
            ->all();
    }

    public function findForAdmin(string $uuid): ?Booking
    {
        return $this->query()
            ->with(['customer', 'provider.user', 'serviceCategory', 'city', 'serviceZone', 'stops'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @return list<string>
     */
    private function assignedValues(): array
    {
        return array_map(static fn (BookingStatus $status): string => $status->value, BookingStatus::assigned());
    }

    public function abuseSignalTimes(\App\Enums\Common\ActorType $party, int $partyId, CarbonInterface $since): array
    {
        $justified = array_map(static fn (\App\Enums\Booking\CancellationReason $reason): string => $reason->value, \App\Services\Booking\CancellationFeeCalculator::justifiedForProvider());
        $driverFault = [\App\Enums\Booking\CancellationReason::DriverAskedToCancel->value, \App\Enums\Booking\CancellationReason::DriverNoShow->value];

        $query = $this->query()->whereNotNull('cancelled_at')->where('cancelled_at', '>=', $since);

        if ($party === \App\Enums\Common\ActorType::Customer) {
            $query->where('customer_id', $partyId)->whereNotNull('provider_id')->where(static fn (Builder $inner) => $inner
                ->where(static fn (Builder $own) => $own->where('cancelled_by_type', \App\Enums\Common\ActorType::Customer->value)->whereNotIn('cancellation_reason_code', $driverFault))
                ->orWhere('cancellation_reason_code', \App\Enums\Booking\CancellationReason::CustomerNoShow->value));
        } else {
            $query->where('provider_id', $partyId)->where(static fn (Builder $inner) => $inner
                ->where(static fn (Builder $own) => $own->where('cancelled_by_type', \App\Enums\Common\ActorType::Provider->value)->where('cancelled_by_id', $partyId)->whereNotIn('cancellation_reason_code', $justified))
                ->orWhere('cancellation_reason_code', \App\Enums\Booking\CancellationReason::DriverNoShow->value));
        }

        return $query->orderBy('cancelled_at')->pluck('cancelled_at')
            ->map(static fn (mixed $at): \Carbon\CarbonImmutable => \Carbon\CarbonImmutable::parse((string) $at, 'UTC'))
            ->values()
            ->all();
    }
}
