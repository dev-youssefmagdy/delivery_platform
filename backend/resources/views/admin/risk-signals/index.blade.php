@php($table = \App\DataTables\Admin\Risk\RiskSignalsDataTable::TABLE_ID)
<x-admin.layouts.app :title="__('reputation.admin.risk.title')">
    <x-admin.page-header
        :title="__('reputation.admin.risk.title')"
        :subtitle="__('reputation.admin.risk.subtitle', ['active' => $active])"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('reputation.admin.risk.title')]]"
    />

    <x-admin.alert type="info" class="mb-6">{{ __('reputation.admin.risk.internal_notice') }}</x-admin.alert>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.enum name="types" :label="__('reputation.admin.fields.type')" :enum="\App\Enums\Risk\RiskSignalType::class" multiple />
        <x-admin.filters.enum name="statuses" :label="__('reputation.admin.fields.status')" :enum="\App\Enums\Risk\RiskSignalStatus::class" multiple />
        <x-admin.filters.enum name="subject_type" :label="__('reputation.admin.fields.subject_type')" :enum="\App\Enums\Risk\RiskSubjectType::class" />
        <x-admin.filters.number-range-single name="min_score" :label="__('reputation.admin.fields.min_score')" />
        <x-admin.filters.date-range name="detected" :label="__('reputation.admin.fields.detected')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
