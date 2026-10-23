<x-admin.layouts.app :title="__('content.app_links.title')">
    <x-admin.page-header
        :title="__('content.app_links.title')"
        :subtitle="__('content.app_links.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('content.app_links.title')]]"
    >
        @can('create', \App\Models\ContentAppLink::class)
            <x-admin.button :href="route('admin.content-app-links.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Content\ContentAppLinksDataTable::TABLE_ID }}">
        <x-admin.filters.boolean name="active" :label="__('content.fields.active')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
