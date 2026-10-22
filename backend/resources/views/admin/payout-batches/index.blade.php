<x-admin.layouts.app :title="__('admin.batches.title')">
    <x-admin.page-header
        :title="__('admin.batches.title')"
        :subtitle="__('admin.batches.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.batches.title')]]"
    />

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Payout\PayoutBatchesDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="statuses" :label="__('admin.batches.fields.status')" :enum="\App\Enums\Payout\PayoutBatchStatus::class" multiple />
        <x-admin.filters.text name="search" :label="__('admin.batches.fields.reference')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.batches.fields.created')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
