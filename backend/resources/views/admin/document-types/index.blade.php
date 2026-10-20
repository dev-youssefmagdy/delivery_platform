<x-admin.layouts.app :title="__('admin.document_types.title')">
    <x-admin.page-header
        :title="__('admin.document_types.title')"
        :subtitle="__('admin.document_types.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.document_types.title')]]"
    >
        @can('create', \App\Models\DocumentType::class)
            <x-admin.button :href="route('admin.document-types.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Provider\DocumentTypesDataTable::TABLE_ID }}">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('admin.document_types.search_placeholder')" />
        <x-admin.filters.enum name="applies_to" :label="__('admin.document_types.fields.applies_to')" :enum="\App\Enums\Provider\DocumentSubject::class" multiple />
        <x-admin.filters.boolean name="active" :label="__('admin.catalog.fields.active')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
