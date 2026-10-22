<x-admin.layouts.app :title="__('admin.push_campaigns.title')">
    <x-admin.page-header
        :title="__('admin.push_campaigns.title')"
        :subtitle="__('admin.push_campaigns.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.push_campaigns.title')]]"
    >
        @can('create', \App\Models\PushCampaign::class)
            <x-admin.button :href="route('admin.push-campaigns.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Notification\PushCampaignsDataTable::TABLE_ID }}">
        <x-admin.filters.text name="name" :label="__('admin.push_campaigns.fields.name')" />
        <x-admin.filters.enum name="statuses" :label="__('admin.push_campaigns.fields.status')" :enum="\App\Enums\Notification\CampaignStatus::class" multiple />
        <x-admin.filters.enum name="audiences" :label="__('admin.push_campaigns.fields.audience')" :enum="\App\Enums\Notification\CampaignAudience::class" multiple />
        <x-admin.filters.date-range name="created" :label="__('admin.common.created_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
