<x-admin.layouts.app :title="__('admin.cancellation_policies.title')">
    <x-admin.page-header
        :title="__('admin.cancellation_policies.title')"
        :subtitle="__('admin.cancellation_policies.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.cancellation_policies.title')]]"
    >
        @can('create', \App\Models\CancellationPolicy::class)
            <x-admin.button :href="route('admin.cancellation-policies.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Booking\CancellationPoliciesDataTable::TABLE_ID }}">
        <x-admin.filters.text name="name" :label="__('admin.cancellation_policies.fields.name')" />
        <x-admin.filters.remote-select name="category_ids" :label="__('admin.cancellation_policies.fields.service')" lookup="service-categories" multiple />
        <x-admin.filters.remote-select name="zone_ids" :label="__('admin.cancellation_policies.fields.zone')" lookup="zones" multiple />
        <x-admin.filters.boolean name="active" :label="__('admin.cancellation_policies.fields.enabled')" />
        <x-admin.filters.date-range name="effective_on" :label="__('admin.cancellation_policies.filters.effective_on')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
