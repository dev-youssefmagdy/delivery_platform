<?php

namespace App\DataTables\Admin\Coupon;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Coupon\CouponActivityFilterData;
use App\Models\Coupon;
use App\Models\CouponAttempt;
use App\Repositories\Contracts\Coupon\CouponAttemptRepositoryInterface;
use App\Support\Auth\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Yajra\DataTables\EloquentDataTable;

/**
 * Attempts tab of the coupon page (read-only, append-only log): every validation, booking-time reservation and admin test,
 * with the result and the explanation code. The coupon comes from the `{coupon}` route parameter.
 */
final class CouponAttemptsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'coupon-attempts-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly CouponAttemptRepositoryInterface $attempts)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<CouponAttempt>
     */
    public function query(): Builder
    {
        $coupon = $this->request()->route('coupon');

        if (! $coupon instanceof Coupon) {
            throw new LogicException('The attempts tab needs a {coupon} route parameter.');
        }

        return $this->attempts->datatableQuery(CouponActivityFilterData::fromRequest($this->filterRequest())->forCoupon($coupon->id));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'coupon_attempts.id'),
            $this->column('customer', __('admin.coupons.attempts.customer')),
            $this->column('context', __('admin.coupons.attempts.context')),
            $this->column('result', __('admin.coupons.attempts.result')),
            $this->column('reason', __('admin.coupons.attempts.reason')),
            $this->column('discount', __('admin.coupons.attempts.discount')),
            $this->column('created_at', __('admin.coupons.attempts.at'), orderable: true, name: 'coupon_attempts.created_at'),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'customer', 'context', 'result', 'reason', 'discount', 'created_at']);

        $this->userColumn($table, 'customer', static fn (CouponAttempt $row): ?array => $row->user === null ? null : [
            $row->user->name ?? __('admin.customers.unnamed'),
            $row->user->phone_e164 === null ? null : PhoneNumber::mask($row->user->phone_e164),
        ]);
        $table->addColumn('context', static fn (CouponAttempt $row): string => e($row->context->label()));
        $this->enumBadgeColumn($table, 'result');
        $table->addColumn('reason', static fn (CouponAttempt $row): string => e($row->reason()?->label() ?? $row->reason_code));
        $table->addColumn('discount', static fn (CouponAttempt $row): string => $row->discount_minor === null ? '—' : e(app(\App\Support\Coupon\CouponPresenter::class)->money($row->discount_minor, $row->coupon?->currency ?? 'EGP')));
        $this->rawColumn('discount');
        $this->dateColumn($table, 'created_at');
    }
}
