@php($table = \App\DataTables\Admin\Growth\ReferralRewardsDataTable::TABLE_ID)
<x-admin.layouts.app :title="__('growth.rewards.title')">
    <x-admin.page-header
        :title="__('growth.rewards.title')"
        :subtitle="__('growth.rewards.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('growth.rewards.title')]]"
    >

    </x-admin.page-header>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.remote-select name="campaign_ids" :label="__('growth.fields.campaign')" lookup="referral-campaigns" multiple />
        <x-admin.filters.enum name="recipients" :label="__('growth.rewards.fields.recipient')" :enum="\App\Enums\Growth\RewardRecipient::class" multiple />
        <x-admin.filters.enum name="statuses" :label="__('growth.fields.status')" :enum="\App\Enums\Growth\CreditStatus::class" multiple />
        <x-admin.filters.date-range name="granted" :label="__('growth.rewards.fields.granted')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
