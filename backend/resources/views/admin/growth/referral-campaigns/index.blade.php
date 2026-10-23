@php($table = \App\DataTables\Admin\Growth\ReferralCampaignsDataTable::TABLE_ID)
<x-admin.layouts.app :title="__('growth.referral.title')">
    <x-admin.page-header
        :title="__('growth.referral.title')"
        :subtitle="__('growth.referral.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('growth.referral.title')]]"
    >
        @can('create', \App\Models\ReferralCampaign::class)
            <x-admin.button :href="route('admin.referral-campaigns.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.enum name="audiences" :label="__('growth.fields.audience')" :enum="\App\Enums\Growth\ReferralAudience::class" multiple />
        <x-admin.filters.enum name="statuses" :label="__('growth.fields.status')" :enum="\App\Enums\Growth\CampaignStatus::class" multiple />
        <x-admin.filters.date-range name="starts" :label="__('growth.fields.starts_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
