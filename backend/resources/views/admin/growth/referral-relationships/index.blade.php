@php($table = \App\DataTables\Admin\Growth\ReferralRelationshipsDataTable::TABLE_ID)
<x-admin.layouts.app :title="__('growth.relationships.title')">
    <x-admin.page-header
        :title="__('growth.relationships.title')"
        :subtitle="__('growth.relationships.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('growth.relationships.title')]]"
    >

    </x-admin.page-header>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.remote-select name="campaign_ids" :label="__('growth.fields.campaign')" lookup="referral-campaigns" multiple />
        <x-admin.filters.enum name="audiences" :label="__('growth.fields.audience')" :enum="\App\Enums\Growth\ReferralAudience::class" multiple />
        <x-admin.filters.enum name="statuses" :label="__('growth.fields.status')" :enum="\App\Enums\Growth\RelationshipStatus::class" multiple />
        <x-admin.filters.boolean name="flagged" :label="__('growth.fields.flagged')" />
        <x-admin.filters.date-range name="created" :label="__('growth.fields.created')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
