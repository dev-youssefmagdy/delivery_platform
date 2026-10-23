<x-admin.layouts.app :title="__('content.categories.title')">
    <x-admin.page-header
        :title="__('content.categories.title')"
        :subtitle="__('content.categories.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('content.categories.title')]]"
    >
        @can('create', \App\Models\ContentCategory::class)
            <x-admin.button :href="route('admin.content-categories.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Content\ContentCategoriesDataTable::TABLE_ID }}">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('content.categories.search_placeholder')" />
        <x-admin.filters.enum name="category_kind" :label="__('content.fields.category_kind')" :enum="\App\Enums\Content\CategoryKind::class" />
        <x-admin.filters.boolean name="active" :label="__('content.fields.active')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
