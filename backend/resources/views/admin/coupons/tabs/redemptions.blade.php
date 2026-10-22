{{-- Lazy tab fragment: redemptions of the coupon (the table initialises after the tab loads). --}}
<x-admin.filters-card for="{{ \App\DataTables\Admin\Coupon\CouponRedemptionsDataTable::TABLE_ID }}">
    <x-admin.filters.enum name="redemption_statuses" :label="__('admin.coupons.redemptions.status')" :enum="\App\Enums\Coupon\CouponRedemptionStatus::class" multiple />
    <x-admin.filters.date-range name="date_range" :label="__('admin.coupons.filters.date_range')" />
</x-admin.filters-card>
<x-admin.datatable :table="$dataTable" />
