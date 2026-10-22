{{-- Lazy tab fragment: the append-only log of attempts to use the coupon. --}}
<x-admin.filters-card for="{{ \App\DataTables\Admin\Coupon\CouponAttemptsDataTable::TABLE_ID }}">
    <x-admin.filters.enum name="attempt_results" :label="__('admin.coupons.attempts.result')" :enum="\App\Enums\Coupon\CouponAttemptResult::class" multiple />
    <x-admin.filters.date-range name="date_range" :label="__('admin.coupons.filters.date_range')" />
</x-admin.filters-card>
<x-admin.datatable :table="$dataTable" />
