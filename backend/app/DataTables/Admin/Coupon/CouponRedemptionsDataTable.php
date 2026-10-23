<?php

namespace App\DataTables\Admin\Coupon;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Coupon\CouponActivityFilterData;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\Support\Auth\PhoneNumber;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Yajra\DataTables\EloquentDataTable;

/**
 * Redemptions tab of the coupon page (read-only): who used the coupon on which booking, the discount and who funded it.
 * The coupon comes from the `{coupon}` route parameter, already authorized by the route.
 */
class CouponRedemptionsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'coupon-redemptions-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly CouponReservationRepositoryInterface $reservations)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return static::TABLE_ID;
    }

    /**
     * @return Builder<CouponRedemption>
     */
    public function query(): Builder
    {
        $coupon = $this->request()->route('coupon');

        if (! $coupon instanceof Coupon) {
            throw new LogicException('The redemptions tab needs a {coupon} route parameter.');
        }

        return $this->reservations->redemptionsQuery(CouponActivityFilterData::fromRequest($this->filterRequest())->forCoupon($coupon->id));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'coupon_redemptions.id'),
            $this->column('customer', __('admin.coupons.redemptions.customer')),
            $this->column('booking', __('admin.coupons.redemptions.booking')),
            $this->column('fare', __('admin.coupons.redemptions.fare')),
            $this->column('discount', __('admin.coupons.redemptions.discount'), orderable: true, name: 'coupon_redemptions.discount_minor'),
            $this->column('funding', __('admin.coupons.redemptions.funding')),
            $this->column('status', __('admin.coupons.redemptions.status')),
            $this->column('redeemed_at', __('admin.coupons.redemptions.redeemed_at'), orderable: true, name: 'coupon_redemptions.redeemed_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'customer', 'booking', 'fare', 'discount', 'funding', 'status', 'redeemed_at', 'actions']);
        $money = fn (int $minor, string $currency): string => app(MoneyFormatter::class)->format(Money::of($minor, app(CurrencyRegistry::class)->get($currency)));

        $this->userColumn($table, 'customer', static fn (CouponRedemption $row): array => [
            $row->user->name ?? __('admin.customers.unnamed'),
            $row->user->phone_e164 === null ? null : PhoneNumber::mask($row->user->phone_e164),
        ]);
        $table->addColumn('booking', static fn (CouponRedemption $row): string => '<a href="'.e(route('admin.bookings.show', $row->booking->uuid)).'" class="ltr-only text-brand-700 hover:underline">'.e($row->booking->code).'</a>');
        $this->rawColumn('booking');
        $table->addColumn('fare', static fn (CouponRedemption $row): string => $money($row->fare_minor, $row->currency));
        $table->addColumn('discount', static fn (CouponRedemption $row): string => $money($row->discount_minor, $row->currency));
        $table->addColumn('funding', static fn (CouponRedemption $row): string => implode(' · ', array_filter([
            $row->platform_minor > 0 ? __('enums.discount_funding.platform').' '.$money($row->platform_minor, $row->currency) : null,
            $row->provider_minor > 0 ? __('enums.discount_funding.provider').' '.$money($row->provider_minor, $row->currency) : null,
            $row->partner_minor > 0 ? __('enums.discount_funding.partner').' '.$money($row->partner_minor, $row->currency) : null,
        ])));
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'redeemed_at');

        $this->actionsColumn($table, static fn (CouponRedemption $row): array => [TableAction::view(route('admin.bookings.show', $row->booking->uuid), $row->booking)]);
    }
}
