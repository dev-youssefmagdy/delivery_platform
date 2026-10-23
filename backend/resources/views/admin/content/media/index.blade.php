<x-admin.layouts.app :title="__('content.media.title')">
    <x-admin.page-header
        :title="__('content.media.title')"
        :subtitle="__('content.media.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('content.media.title')]]"
    >
        @can('create', \App\Models\ContentMedia::class)
            <x-admin.button :href="route('admin.content-media.create')" icon="upload">{{ __('content.media.upload') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Content\ContentMediaDataTable::TABLE_ID }}">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('content.media.search_placeholder')" />
        <x-admin.filters.date-range name="updated_at" :label="__('admin.common.created_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
