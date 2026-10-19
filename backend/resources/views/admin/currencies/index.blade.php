<x-admin.layouts.app :title="__('admin.currencies.title')">
    <x-admin.page-header
        :title="__('admin.currencies.title')"
        :subtitle="__('admin.currencies.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.currencies.title')]]"
    >
        @can('create', \App\Models\Currency::class)
            <x-admin.button :href="route('admin.currencies.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="currencies-table">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('admin.currencies.search_placeholder')" />
        <x-admin.filters.boolean name="active" :label="__('admin.currencies.fields.active')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.common.created_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
