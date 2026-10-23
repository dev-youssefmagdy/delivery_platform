<x-admin.layouts.app :title="__('content.pages.title')">
    <x-admin.page-header
        :title="__('content.pages.title')"
        :subtitle="__('content.pages.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('content.pages.title')]]"
    >
        @can('create', \App\Models\ContentPage::class)
            <x-admin.button :href="route('admin.content-pages.create', array_filter(['kind' => request()->input('filters.kinds.0')]))" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Content\ContentPagesDataTable::TABLE_ID }}">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('content.pages.search_placeholder')" />
        <x-admin.filters.enum name="kinds" :label="__('content.fields.kind')" :enum="\App\Enums\Content\PageKind::class" multiple />
        <x-admin.filters.enum name="statuses" :label="__('content.fields.status')" :enum="\App\Enums\Content\ContentWorkflowStatus::class" multiple />
        <x-admin.filters.enum name="completeness" :label="__('content.fields.locales')" :enum="\App\Enums\Content\LocaleCompleteness::class" />
        <x-admin.filters.date-range name="updated_at" :label="__('admin.common.updated_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
