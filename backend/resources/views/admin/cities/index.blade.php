<x-admin.layouts.app :title="__('admin.cities.title')">
    <x-admin.page-header
        :title="__('admin.cities.title')"
        :subtitle="__('admin.cities.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.cities.title')]]"
    >
        @can('create', \App\Models\City::class)
            <x-admin.button :href="route('admin.cities.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="cities-table">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('admin.cities.search_placeholder')" />
        <x-admin.filters.remote-select name="country_ids" :label="__('admin.cities.fields.country')" lookup="countries" multiple />
        <x-admin.filters.boolean name="active" :label="__('admin.geo.fields.active')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
