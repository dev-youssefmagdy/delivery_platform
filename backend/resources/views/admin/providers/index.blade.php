<x-admin.layouts.app :title="__('admin.providers.title')">
    <x-admin.page-header
        :title="__('admin.providers.title')"
        :subtitle="__('admin.providers.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.providers.title')]]"
    />

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Provider\ProvidersDataTable::TABLE_ID }}">
        <x-admin.filters.text name="search" :label="__('admin.providers.filters.search')" :placeholder="__('admin.providers.filters.search_placeholder')" />
        <x-admin.filters.enum name="statuses" :label="__('admin.providers.fields.status')" :enum="\App\Enums\Provider\ProviderStatus::class" multiple />
        <x-admin.filters.enum name="provider_types" :label="__('admin.providers.fields.provider_type')" :enum="\App\Enums\Provider\ProviderType::class" multiple />
        <x-admin.filters.remote-select name="service_category_ids" :label="__('admin.providers.filters.service_category')" lookup="service-categories" multiple />
        <x-admin.filters.remote-select name="city_ids" :label="__('admin.providers.fields.city')" lookup="cities" multiple />
        <x-admin.filters.boolean name="online" :label="__('admin.providers.filters.online_now')" />
        <x-admin.filters.number-range-single name="expiring_within_days" :label="__('admin.providers.filters.expiring_within')" :suffix="__('admin.providers.filters.days')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
