@php($table = \App\DataTables\Admin\Growth\IncentiveCampaignsDataTable::TABLE_ID)
<x-admin.layouts.app :title="__('growth.incentive.title')">
    <x-admin.page-header
        :title="__('growth.incentive.title')"
        :subtitle="__('growth.incentive.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('growth.incentive.title')]]"
    >
        @can('create', \App\Models\IncentiveCampaign::class)
            <x-admin.button :href="route('admin.incentive-campaigns.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.enum name="statuses" :label="__('growth.fields.status')" :enum="\App\Enums\Growth\CampaignStatus::class" multiple />
        <x-admin.filters.date-range name="starts" :label="__('growth.fields.starts_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
