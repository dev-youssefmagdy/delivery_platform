<x-admin.layouts.app :title="__('admin.service_zones.title')">
    <x-admin.page-header
        :title="__('admin.service_zones.title')"
        :subtitle="__('admin.service_zones.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.service_zones.title')]]"
    >
        @can('create', \App\Models\ServiceZone::class)
            <x-admin.button :href="route('admin.service-zones.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="service-zones-table">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('admin.service_zones.search_placeholder')" />
        <x-admin.filters.remote-select name="city_ids" :label="__('admin.service_zones.fields.city')" lookup="cities" multiple />
        <x-admin.filters.enum name="statuses" :label="__('admin.service_zones.fields.status')" :enum="\App\Enums\Geo\ZoneStatus::class" multiple />
        <x-admin.filters.remote-select name="category_ids" :label="__('admin.service_zones.filters.category')" lookup="service-categories" multiple />
        <x-admin.filters.boolean name="restricted_pickup" :label="__('admin.service_zones.filters.restricted_pickup')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
