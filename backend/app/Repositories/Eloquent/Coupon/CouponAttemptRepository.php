<?php

namespace App\Repositories\Eloquent\Coupon;

use App\DTOs\Coupon\CouponActivityFilterData;
use App\Enums\Coupon\CouponAttemptResult;
use App\Models\CouponAttempt;
use App\Repositories\Contracts\Coupon\CouponAttemptRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<CouponAttempt>
 */
final class CouponAttemptRepository extends BaseRepository implements CouponAttemptRepositoryInterface
{
    public function __construct(CouponAttempt $model)
    {
        parent::__construct($model);
    }

    public function record(array $attributes): CouponAttempt
    {
        return CouponAttempt::query()->create($attributes);
    }

    public function datatableQuery(CouponActivityFilterData $filters): Builder
    {
        return $this->query()
            ->with(['coupon:id,uuid,code,name', 'user:id,uuid,name,phone_e164'])
            ->when($filters->couponId !== null, static fn (Builder $query) => $query->where('coupon_attempts.coupon_id', $filters->couponId))
            ->when($filters->userId !== null, static fn (Builder $query) => $query->where('coupon_attempts.user_id', $filters->userId))
            ->when($filters->attemptResults !== [], static fn (Builder $query) => $query->whereIn('coupon_attempts.result', array_map(static fn (CouponAttemptResult $result): string => $result->value, $filters->attemptResults)))
            ->when($filters->from !== null, static fn (Builder $query) => $query->where('coupon_attempts.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $query) => $query->where('coupon_attempts.created_at', '<=', $filters->to?->endOfDay()));
    }

    public function counts(int $couponId): array
    {
        $row = DB::table('coupon_attempts')->where('coupon_id', $couponId)
            ->selectRaw('count(*) as total')
            ->selectRaw('coalesce(sum(case when result in (?, ?) then 1 else 0 end), 0) as rejected', [CouponAttemptResult::Rejected->value, CouponAttemptResult::ReservationFailed->value])
            ->first();

        return ['total' => (int) ($row->total ?? 0), 'rejected' => (int) ($row->rejected ?? 0)];
    }

    public function rejectionsByReason(int $couponId): array
    {
        return DB::table('coupon_attempts')->where('coupon_id', $couponId)
            ->whereIn('result', [CouponAttemptResult::Rejected->value, CouponAttemptResult::ReservationFailed->value])
            ->selectRaw('reason_code, count(*) as total')->groupBy('reason_code')->orderByDesc('total')
            ->pluck('total', 'reason_code')->map(static fn ($total): int => (int) $total)->all();
    }
}
