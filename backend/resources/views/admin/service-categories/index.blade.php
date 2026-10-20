<x-admin.layouts.app :title="__('admin.service_categories.title')">
    <x-admin.page-header
        :title="__('admin.service_categories.title')"
        :subtitle="__('admin.service_categories.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.service_categories.title')]]"
    >
        @can('create', \App\Models\ServiceCategory::class)
            <x-admin.button :href="route('admin.service-categories.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="service-categories-table">
        <x-admin.filters.text name="search" :label="__('admin.common.search')" :placeholder="__('admin.service_categories.search_placeholder')" />
        <x-admin.filters.select name="family" :label="__('admin.service_categories.fields.family')" :options="$familyOptions" />
        <x-admin.filters.boolean name="active" :label="__('admin.catalog.fields.active')" />
        <x-admin.filters.enum name="booking_type" :label="__('admin.service_categories.fields.allowed_booking_types')" :enum="\App\Enums\Catalog\BookingType::class" />
        <x-admin.filters.boolean name="negotiation_enabled" :label="__('admin.service_categories.fields.negotiation_enabled')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
