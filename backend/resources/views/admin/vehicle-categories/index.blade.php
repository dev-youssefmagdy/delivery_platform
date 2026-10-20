<x-admin.layouts.app :title="__('admin.vehicle_categories.title')">
    <x-admin.page-header
        :title="__('admin.vehicle_categories.title')"
        :subtitle="__('admin.vehicle_categories.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.vehicle_categories.title')]]"
    >
        @can('create', \App\Models\VehicleCategory::class)
            <x-admin.button :href="route('admin.vehicle-categories.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="vehicle-categories-table">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('admin.vehicle_categories.search_placeholder')" />
        <x-admin.filters.boolean name="active" :label="__('admin.catalog.fields.active')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
