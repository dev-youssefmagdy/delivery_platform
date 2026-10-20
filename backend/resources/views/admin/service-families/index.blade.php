<x-admin.layouts.app :title="__('admin.service_families.title')">
    <x-admin.page-header
        :title="__('admin.service_families.title')"
        :subtitle="__('admin.service_families.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.service_families.title')]]"
    >
        @can('create', \App\Models\ServiceFamily::class)
            <x-admin.button :href="route('admin.service-families.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="service-families-table">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('admin.service_families.search_placeholder')" />
        <x-admin.filters.enum name="kinds" :label="__('admin.service_families.fields.kind')" :enum="\App\Enums\Catalog\ServiceFamilyKind::class" multiple />
        <x-admin.filters.boolean name="active" :label="__('admin.catalog.fields.active')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
