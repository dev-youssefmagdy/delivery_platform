<?php

namespace App\Services\Coupon;

use App\DTOs\Coupon\CouponData;
use App\Enums\Common\AuditAction;
use App\Enums\Coupon\CouponStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\Coupon;
use App\Models\CouponVersion;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * Admin configuration of coupons (SRS §11.1, §16.8): audited writes with optimistic locking and immutable versions.
 *
 * VERSIONING (ADR-120): the financial terms (discount type / value, cap, minimum fare, funding, refund policy) are frozen per
 * version. Changing them on a coupon that is not a draft adds a version, bumps `coupons.version` and requires a reason; a
 * reservation or redemption keeps the version it was made under, so a live edit never changes a trip in flight or a
 * completed booking. Eligibility and limit edits (scope, schedule, caps, budget, dates) apply to new uses at once. A coupon is
 * never deleted: it is archived (CouponLifecycleService).
 */
final class CouponService
{
    private const MAX_MINOR = 10_000_000_000;

    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(CouponData $data, Admin $actor): Coupon
    {
        $this->assertValid($data, null);

        return $this->db->transaction(function () use ($data, $actor): Coupon {
            /** @var Coupon $coupon */
            $coupon = $this->coupons->create($data->attributes() + [
                'uuid' => (string) Str::uuid(),
                'code' => $data->code,
                'currency' => $data->currency,
                'status' => CouponStatus::Draft,
                'version' => 1,
                'created_by_id' => $actor->id,
            ]);
            $this->coupons->syncScopes($coupon, $data->scopes);
            $this->addVersion($coupon, $data, 1, null, $actor);
            $this->audit->record(AuditAction::Created, $coupon, $actor, after: $this->snapshot($coupon->refresh()->load('scopes')));

            return $coupon;
        });
    }

    public function update(Coupon $coupon, CouponData $data, Admin $actor): Coupon
    {
        $this->assertValid($data, $coupon);

        return $this->db->transaction(function () use ($coupon, $data, $actor): Coupon {
            $locked = $this->coupons->lockById($coupon->id);

            if (! $locked instanceof Coupon) {
                throw (new ModelNotFoundException)->setModel(Coupon::class);
            }

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if ($locked->status === CouponStatus::Archived) {
                throw BusinessRuleViolation::make('coupon_archived_locked');
            }

            $locked->load('scopes');
            $current = $this->coupons->currentVersion($locked) ?? throw new \LogicException('A coupon always has a current version.');
            $termsChanged = ! $current->termsObject()->equals($data->terms());

            if ($termsChanged && $locked->status->isLive() && $data->reason === null) {
                throw BusinessRuleViolation::make('coupon_change_reason_required', 'reason');
            }

            $before = $this->snapshot($locked);
            $attributes = $data->attributes() + ['lock_version' => $locked->lock_version + 1];

            if ($termsChanged) {
                $attributes['version'] = $locked->version + 1;
            }

            $this->coupons->update($locked, $attributes);
            $this->coupons->syncScopes($locked, $data->scopes);

            if ($termsChanged) {
                $this->addVersion($locked, $data, $locked->version, $data->reason, $actor);
            }

            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $this->snapshot($locked->refresh()->load('scopes')), $data->reason);

            return $locked;
        });
    }

    private function addVersion(Coupon $coupon, CouponData $data, int $version, ?string $reason, Admin $actor): CouponVersion
    {
        return $this->coupons->addVersion($coupon, [
            'version' => $version,
            'terms' => $data->terms()->toSnapshot(),
            'reason' => $reason,
            'created_by_id' => $actor->id,
        ]);
    }

    private function assertValid(CouponData $data, ?Coupon $existing): void
    {
        if ($data->code !== null && $this->coupons->codeExists($data->code, $existing?->id)) {
            throw BusinessRuleViolation::make('coupon_code_taken', 'code');
        }

        if ($data->startsAt !== null && $data->endsAt !== null && $data->endsAt->lessThanOrEqualTo($data->startsAt)) {
            throw BusinessRuleViolation::make('coupon_window_invalid', 'ends_at');
        }

        if ($data->discountValue <= 0 || $data->discountValue > ($data->discountType->value === 'percentage' ? 10_000 : self::MAX_MINOR)) {
            throw BusinessRuleViolation::make('coupon_value_invalid', $data->discountType->value === 'percentage' ? 'percent' : 'fixed_minor');
        }

        if ($data->code === null && $data->perCustomerLimit === null) {
            throw BusinessRuleViolation::make('coupon_automatic_needs_customer_limit', 'per_customer_limit');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Coupon $coupon): array
    {
        return $coupon->auditSnapshot() + ['scope_rows' => $coupon->scopes->map(static fn ($scope): string => $scope->type->value.':'.$scope->ref_id)->sort()->values()->all()];
    }
}
