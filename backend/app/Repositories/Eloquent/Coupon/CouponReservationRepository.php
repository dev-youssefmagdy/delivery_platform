<?php

namespace App\Repositories\Eloquent\Coupon;

use App\DTOs\Coupon\CouponActivityFilterData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Coupon\CouponRedemptionStatus;
use App\Enums\Coupon\CouponReservationStatus;
use App\Enums\Finance\DiscountFunding;
use App\Models\CouponRedemption;
use App\Models\CouponReservation;
use App\Models\PromotionFundingEntry;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<CouponReservation>
 */
final class CouponReservationRepository extends BaseRepository implements CouponReservationRepositoryInterface
{
    public function __construct(CouponReservation $model)
    {
        parent::__construct($model);
    }

    public function forBooking(int $bookingId): ?CouponReservation
    {
        return $this->query()->with(['coupon', 'version'])->where('booking_id', $bookingId)->first();
    }

    public function lockForBooking(int $bookingId): ?CouponReservation
    {
        return $this->query()->where('booking_id', $bookingId)->lockForUpdate()->first();
    }

    public function countingForUser(int $couponId, int $userId): int
    {
        return $this->query()->where('coupon_id', $couponId)->where('user_id', $userId)
            ->whereIn('status', array_map(static fn (CouponReservationStatus $status): string => $status->value, CouponReservationStatus::counting()))
            ->count();
    }

    public function orphaned(): Collection
    {
        return $this->query()->with('booking')
            ->where('status', CouponReservationStatus::Reserved->value)
            ->whereHas('booking', static fn (Builder $query) => $query->whereIn('status', array_map(
                static fn (BookingStatus $status): string => $status->value,
                [BookingStatus::CancelledByCustomer, BookingStatus::CancelledByProvider, BookingStatus::CancelledByOperations, BookingStatus::ExpiredNoProvider],
            )))
            ->get();
    }

    public function redemptionFor(int $reservationId): ?CouponRedemption
    {
        return CouponRedemption::query()->where('reservation_id', $reservationId)->first();
    }

    public function createRedemption(array $attributes): CouponRedemption
    {
        return CouponRedemption::query()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateRedemption(CouponRedemption $redemption, array $attributes): CouponRedemption
    {
        $redemption->forceFill($attributes)->save();

        return $redemption;
    }

    public function addFunding(int $couponId, int $redemptionId, int $bookingId, DiscountFunding $funder, string $kind, int $amountMinor, string $currency): void
    {
        if ($amountMinor <= 0) {
            return;
        }

        PromotionFundingEntry::query()->create([
            'coupon_id' => $couponId, 'redemption_id' => $redemptionId, 'booking_id' => $bookingId, 'funder' => $funder,
            'kind' => $kind, 'amount_minor' => $amountMinor, 'currency' => $currency,
        ]);
    }

    public function linkFundingToEntry(int $bookingId, int $journalEntryId): void
    {
        PromotionFundingEntry::query()->where('booking_id', $bookingId)->where('kind', 'accrued')->whereNull('journal_entry_id')->update(['journal_entry_id' => $journalEntryId]);
    }

    public function redemptionsQuery(CouponActivityFilterData $filters): Builder
    {
        return CouponRedemption::query()
            ->with(['coupon:id,uuid,code,name', 'user:id,uuid,name,phone_e164', 'booking:id,uuid,code'])
            ->when($filters->couponId !== null, static fn (Builder $query) => $query->where('coupon_redemptions.coupon_id', $filters->couponId))
            ->when($filters->userId !== null, static fn (Builder $query) => $query->where('coupon_redemptions.user_id', $filters->userId))
            ->when($filters->redemptionStatuses !== [], static fn (Builder $query) => $query->whereIn('coupon_redemptions.status', array_map(static fn (CouponRedemptionStatus $status): string => $status->value, $filters->redemptionStatuses)))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('coupon_redemptions.redeemed_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('coupon_redemptions.redeemed_at', '<=', $filters->to?->endOfDay()));
    }

    public function reservationsQuery(CouponActivityFilterData $filters): Builder
    {
        return CouponReservation::query()
            ->with(['coupon:id,uuid,code,name,currency', 'booking:id,uuid,code', 'redemption'])
            ->when($filters->userId !== null, static fn (Builder $query) => $query->where('coupon_reservations.user_id', $filters->userId))
            ->when($filters->couponId !== null, static fn (Builder $query) => $query->where('coupon_reservations.coupon_id', $filters->couponId))
            ->when($filters->reservationStatuses !== [], static fn (Builder $query) => $query->whereIn('coupon_reservations.status', array_map(static fn (CouponReservationStatus $status): string => $status->value, $filters->reservationStatuses)))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('coupon_reservations.reserved_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('coupon_reservations.reserved_at', '<=', $filters->to?->endOfDay()));
    }

    public function performance(int $couponId): array
    {
        $row = DB::table('coupon_redemptions')->where('coupon_id', $couponId)
            ->selectRaw('count(*) as redemptions')
            ->selectRaw('coalesce(sum(case when status = ? then 1 else 0 end), 0) as reversed', [CouponRedemptionStatus::Reversed->value])
            ->selectRaw('coalesce(sum(case when status = ? then discount_minor else 0 end), 0) as discount_minor', [CouponRedemptionStatus::Committed->value])
            ->selectRaw('coalesce(sum(case when status = ? then platform_minor else 0 end), 0) as platform_minor', [CouponRedemptionStatus::Committed->value])
            ->selectRaw('coalesce(sum(case when status = ? then provider_minor else 0 end), 0) as provider_minor', [CouponRedemptionStatus::Committed->value])
            ->selectRaw('coalesce(sum(case when status = ? then partner_minor else 0 end), 0) as partner_minor', [CouponRedemptionStatus::Committed->value])
            ->selectRaw('count(distinct user_id) as customers')
            ->first();

        return [
            'redemptions' => (int) ($row->redemptions ?? 0),
            'reversed' => (int) ($row->reversed ?? 0),
            'discount_minor' => (int) ($row->discount_minor ?? 0),
            'platform_minor' => (int) ($row->platform_minor ?? 0),
            'provider_minor' => (int) ($row->provider_minor ?? 0),
            'partner_minor' => (int) ($row->partner_minor ?? 0),
            'customers' => (int) ($row->customers ?? 0),
        ];
    }

    public function dailyRedemptions(int $couponId, int $days): array
    {
        $rows = DB::table('coupon_redemptions')->where('coupon_id', $couponId)
            ->where('status', CouponRedemptionStatus::Committed->value)
            ->where('redeemed_at', '>=', now()->subDays($days)->startOfDay())
            ->selectRaw('date(redeemed_at) as day, count(*) as total, sum(discount_minor) as discount_minor')
            ->groupBy('day')->orderByDesc('day')->get();
        $result = [];

        foreach ($rows as $row) {
            $result[(string) $row->day] = ['count' => (int) $row->total, 'discount_minor' => (int) $row->discount_minor];
        }

        return $result;
    }
}
