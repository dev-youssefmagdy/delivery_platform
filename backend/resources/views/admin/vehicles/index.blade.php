<x-admin.layouts.app :title="__('admin.vehicles.title')">
    <x-admin.page-header
        :title="__('admin.vehicles.title')"
        :subtitle="__('admin.vehicles.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.vehicles.title')]]"
    />

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Provider\VehiclesDataTable::TABLE_ID }}">
        <x-admin.filters.text name="plate" :label="__('admin.vehicles.fields.plate')" :placeholder="__('admin.vehicles.filters.plate_placeholder')" />
        <x-admin.filters.remote-select name="vehicle_category_ids" :label="__('admin.vehicles.fields.category')" lookup="vehicle-categories" multiple />
        <x-admin.filters.enum name="statuses" :label="__('admin.vehicles.fields.status')" :enum="\App\Enums\Provider\VehicleStatus::class" multiple />
        <x-admin.filters.remote-select name="provider_ids" :label="__('admin.vehicles.fields.provider')" lookup="providers" multiple />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
