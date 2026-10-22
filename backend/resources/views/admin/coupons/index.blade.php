<x-admin.layouts.app :title="__('admin.coupons.title')">
    <x-admin.page-header
        :title="__('admin.coupons.title')"
        :subtitle="__('admin.coupons.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.coupons.title')]]"
    >
        @can('export', \App\Models\Coupon::class)
            <x-admin.button variant="secondary" icon="download" :data-export-url="route('admin.coupons.export')" data-export-table="{{ \App\DataTables\Admin\Coupon\CouponsDataTable::TABLE_ID }}">{{ __('admin.common.export') }}</x-admin.button>
        @endcan
        @can('create', \App\Models\Coupon::class)
            <x-admin.button :href="route('admin.coupons.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Coupon\CouponsDataTable::TABLE_ID }}">
        <x-admin.filters.text name="code" :label="__('admin.coupons.fields.code')" />
        <x-admin.filters.enum name="statuses" :label="__('admin.coupons.fields.status')" :enum="\App\Enums\Coupon\CouponStatus::class" multiple />
        <x-admin.filters.enum name="discount_types" :label="__('admin.coupons.fields.discount_type')" :enum="\App\Enums\Coupon\DiscountType::class" multiple />
        <x-admin.filters.enum name="funding_sources" :label="__('admin.coupons.fields.funding_source')" :enum="\App\Enums\Coupon\FundingSource::class" multiple />
        <x-admin.filters.remote-select name="family_ids" :label="__('admin.coupons.fields.family')" lookup="service-families" multiple />
        <x-admin.filters.date name="active_on" :label="__('admin.coupons.filters.active_on')" />
        <x-admin.filters.date-range name="date_range" :label="__('admin.coupons.filters.date_range')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
