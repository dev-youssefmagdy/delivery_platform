<x-admin.layouts.app :title="__('admin.countries.title')">
    <x-admin.page-header
        :title="__('admin.countries.title')"
        :subtitle="__('admin.countries.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.countries.title')]]"
    >
        @can('create', \App\Models\Country::class)
            <x-admin.button :href="route('admin.countries.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="countries-table">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('admin.countries.search_placeholder')" />
        <x-admin.filters.boolean name="active" :label="__('admin.countries.fields.active')" />
        <x-admin.filters.select name="currency" :label="__('admin.countries.fields.currency')" :options="$currencyOptions" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.common.created_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
