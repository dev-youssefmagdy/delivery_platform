<?php

namespace App\Services\Coupon;

use App\Enums\Common\AuditAction;
use App\Enums\Coupon\CouponStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\Coupon;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Coupon status changes (SRS §11.1): the one place the CouponStatus graph is enforced. Admin actions (publish, pause,
 * resume, archive) carry an actor and, for pause / archive, a mandatory reason; the scheduled `coupons:sync-status` command
 * calls `system` with no actor (scheduled -> active, active -> expired / exhausted, exhausted -> active). Every change is audited.
 */
final class CouponLifecycleService
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Draft -> scheduled (start in the future) or active (start reached).
     */
    public function publish(Coupon $coupon, Admin $actor): Coupon
    {
        return $this->change($coupon, fn (Coupon $locked): CouponStatus => $this->openingStatus($locked), null, $actor, true);
    }

    public function pause(Coupon $coupon, string $reason, Admin $actor): Coupon
    {
        return $this->change($coupon, static fn (): CouponStatus => CouponStatus::Paused, $reason, $actor);
    }

    /**
     * Paused -> active (or scheduled when the start is still ahead).
     */
    public function resume(Coupon $coupon, Admin $actor): Coupon
    {
        return $this->change($coupon, fn (Coupon $locked): CouponStatus => $this->openingStatus($locked), null, $actor, true);
    }

    public function archive(Coupon $coupon, string $reason, Admin $actor): Coupon
    {
        return $this->change($coupon, static fn (): CouponStatus => CouponStatus::Archived, $reason, $actor);
    }

    /**
     * A transition made by the scheduler: same graph, no actor.
     */
    public function system(Coupon $coupon, CouponStatus $to, string $reason): Coupon
    {
        return $this->change($coupon, static fn (): CouponStatus => $to, $reason, null);
    }

    /**
     * @param callable(Coupon): CouponStatus $target
     */
    private function change(Coupon $coupon, callable $target, ?string $reason, ?Admin $actor, bool $needsFuturePeriod = false): Coupon
    {
        return $this->db->transaction(function () use ($coupon, $target, $reason, $actor, $needsFuturePeriod): Coupon {
            $locked = $this->coupons->lockById($coupon->id);

            if (! $locked instanceof Coupon) {
                throw (new ModelNotFoundException)->setModel(Coupon::class);
            }

            $from = $locked->status;
            $to = $target($locked);

            if ($from === $to) {
                return $locked;
            }

            if (! $from->canTransitionTo($to)) {
                throw ConflictException::make('coupon_transition_invalid', context: ['from' => $from->value, 'to' => $to->value]);
            }

            if ($needsFuturePeriod && $locked->ends_at !== null && $locked->ends_at->lessThanOrEqualTo(CarbonImmutable::now())) {
                throw BusinessRuleViolation::make('coupon_period_over');
            }

            $this->coupons->update($locked, ['status' => $to, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, ['status' => $from->value], ['status' => $to->value], $reason);

            return $locked;
        });
    }

    private function openingStatus(Coupon $coupon): CouponStatus
    {
        return $coupon->starts_at !== null && $coupon->starts_at->greaterThan(CarbonImmutable::now()) ? CouponStatus::Scheduled : CouponStatus::Active;
    }
}
