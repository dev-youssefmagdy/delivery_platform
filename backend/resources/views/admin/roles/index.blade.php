<x-admin.layouts.app :title="__('admin.roles.title')">
    <x-admin.page-header
        :title="__('admin.roles.title')"
        :subtitle="__('admin.roles.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.roles.title')]]"
    >
        @can('create', \App\Models\Role::class)
            <x-admin.button :href="route('admin.roles.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="roles-table">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" />
        <x-admin.filters.boolean name="system" :label="__('admin.roles.filters.system')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
