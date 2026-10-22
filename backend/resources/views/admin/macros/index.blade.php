<x-admin.layouts.app :title="__('admin.macros.title')">
    <x-admin.page-header
        :title="__('admin.macros.title')"
        :subtitle="__('admin.macros.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.macros.title')]]"
    >
        @can('create', \App\Models\SupportMacro::class)
            <x-admin.button :href="route('admin.macros.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Support\SupportMacrosDataTable::TABLE_ID }}">
        <x-admin.filters.text name="title" :label="__('admin.macros.fields.title')" />
        <x-admin.filters.enum name="categories" :label="__('admin.macros.fields.category')" :enum="\App\Enums\Support\TicketCategory::class" multiple />
        <x-admin.filters.boolean name="active" :label="__('admin.macros.fields.active')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
