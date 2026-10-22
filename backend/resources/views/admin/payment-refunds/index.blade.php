<x-admin.layouts.app :title="__('admin.refunds.title')">
    <x-admin.page-header
        :title="__('admin.refunds.title')"
        :subtitle="__('admin.refunds.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.refunds.title')]]"
    />

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Payments\RefundsDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="statuses" :label="__('admin.refunds.fields.status')" :enum="\App\Enums\Payments\RefundStatus::class" multiple />
        <x-admin.filters.boolean name="dual_approval" :label="__('admin.refunds.fields.dual')" />
        <x-admin.filters.text name="search" :label="__('admin.refunds.filters.search')" :placeholder="__('admin.refunds.filters.search_hint')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.refunds.fields.created')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
