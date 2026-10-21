<x-admin.layouts.app :title="__('admin.commission_rules.title')">
    <x-admin.page-header
        :title="__('admin.commission_rules.title')"
        :subtitle="__('admin.commission_rules.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.commission_rules.title')]]"
    >
        @can('create', \App\Models\CommissionRule::class)
            <x-admin.button :href="route('admin.commission-rules.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Finance\CommissionRulesDataTable::TABLE_ID }}">
        <x-admin.filters.remote-select name="category_ids" :label="__('admin.commission_rules.fields.service')" lookup="service-categories" multiple />
        <x-admin.filters.remote-select name="zone_ids" :label="__('admin.commission_rules.fields.zone')" lookup="zones" multiple />
        <x-admin.filters.enum name="types" :label="__('admin.commission_rules.fields.type')" :enum="\App\Enums\Finance\CommissionType::class" multiple />
        <x-admin.filters.boolean name="active" :label="__('admin.commission_rules.fields.enabled')" />
        <x-admin.filters.date-range name="effective_on" :label="__('admin.commission_rules.filters.effective_on')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
