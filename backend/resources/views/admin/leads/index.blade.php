@php($table = \App\DataTables\Admin\Lead\LeadsDataTable::TABLE_ID)
<x-admin.layouts.app :title="__('leads.title')">
    <x-admin.page-header
        :title="__('leads.title')"
        :subtitle="__('leads.subtitle', ['waiting' => $waiting])"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('leads.title')]]"
    />

    <x-admin.alert type="info" class="mb-6">{{ __('leads.public_notice') }}</x-admin.alert>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.remote-select name="city_ids" :label="__('leads.fields.city')" lookup="cities" multiple />
        <x-admin.filters.remote-select name="category_ids" :label="__('leads.fields.service')" lookup="service-categories" multiple />
        <x-admin.filters.enum name="statuses" :label="__('leads.fields.status')" :enum="\App\Enums\Lead\LeadStatus::class" multiple />
        <x-admin.filters.date-range name="created" :label="__('leads.fields.created')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
