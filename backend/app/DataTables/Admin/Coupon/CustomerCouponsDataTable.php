<?php

namespace App\DataTables\Admin\Coupon;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Coupon\CouponActivityFilterData;
use App\Models\CouponReservation;
use App\Models\User;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use App\Support\Coupon\CouponPresenter;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Yajra\DataTables\EloquentDataTable;

/**
 * Coupons tab of the customer page: the customer's coupon uses (reserved, committed, released, reversed) with the discount.
 * The customer comes from the `{customer}` route parameter, already authorized by the route.
 */
final class CustomerCouponsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'customer-coupons-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly CouponReservationRepositoryInterface $reservations)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<CouponReservation>
     */
    public function query(): Builder
    {
        $customer = $this->request()->route('customer');

        if (! $customer instanceof User) {
            throw new LogicException('The customer coupons tab needs a {customer} route parameter.');
        }

        return $this->reservations->reservationsQuery(CouponActivityFilterData::fromRequest($this->filterRequest())->forUser($customer->id));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'coupon_reservations.id'),
            $this->column('coupon', __('admin.coupons.customer_tab.coupon')),
            $this->column('booking', __('admin.coupons.customer_tab.booking')),
            $this->column('status', __('admin.coupons.customer_tab.status')),
            $this->column('discount', __('admin.coupons.customer_tab.discount')),
            $this->column('reserved_at', __('admin.coupons.customer_tab.at'), orderable: true, name: 'coupon_reservations.reserved_at'),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'coupon', 'booking', 'status', 'discount', 'reserved_at']);
        $presenter = app(CouponPresenter::class);

        $table->addColumn('coupon', static fn (CouponReservation $row): string => '<a href="'.e(route('admin.coupons.show', $row->coupon)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e($row->coupon->code ?? $row->coupon->name).'</a>');
        $this->rawColumn('coupon');
        $table->addColumn('booking', static fn (CouponReservation $row): string => $row->booking === null ? '—' : '<a href="'.e(route('admin.bookings.show', $row->booking->uuid)).'" class="ltr-only text-brand-700 hover:underline">'.e($row->booking->code).'</a>');
        $this->rawColumn('booking');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('discount', static fn (CouponReservation $row): string => e($presenter->money($row->redemption?->discount_minor ?? $row->hold_minor, $row->currency)).($row->redemption === null ? ' <span class="text-xs text-slate-500">'.e(__('admin.coupons.customer_tab.held')).'</span>' : ''));
        $this->rawColumn('discount');
        $this->dateColumn($table, 'reserved_at');
    }
}
