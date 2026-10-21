<x-admin.layouts.app :title="__('admin.fare_policies.title')">
    <x-admin.page-header
        :title="__('admin.fare_policies.title')"
        :subtitle="__('admin.fare_policies.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.fare_policies.title')]]"
    >
        @can('create', \App\Models\FarePolicy::class)
            <x-admin.button :href="route('admin.fare-policies.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Pricing\FarePoliciesDataTable::TABLE_ID }}">
        <x-admin.filters.remote-select name="category_ids" :label="__('admin.fare_policies.fields.service')" lookup="service-categories" multiple />
        <x-admin.filters.remote-select name="zone_ids" :label="__('admin.fare_policies.fields.zone')" lookup="zones" multiple />
        <x-admin.filters.boolean name="active" :label="__('admin.fare_policies.fields.enabled')" />
        <x-admin.filters.date-range name="effective_on" :label="__('admin.fare_policies.filters.effective_on')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
