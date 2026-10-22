{{-- Lazy tab fragment of the customer page: the customer's coupon uses. --}}
<x-admin.filters-card for="{{ \App\DataTables\Admin\Coupon\CustomerCouponsDataTable::TABLE_ID }}">
    <x-admin.filters.enum name="reservation_statuses" :label="__('admin.coupons.customer_tab.status')" :enum="\App\Enums\Coupon\CouponReservationStatus::class" multiple />
    <x-admin.filters.date-range name="date_range" :label="__('admin.coupons.filters.date_range')" />
</x-admin.filters-card>
<x-admin.datatable :table="$dataTable" />
