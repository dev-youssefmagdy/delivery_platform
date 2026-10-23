@php($table = \App\DataTables\Admin\System\FailedJobsDataTable::TABLE_ID)
<x-admin.layouts.app :title="__('system.queues.title')">
    <x-admin.page-header
        :title="__('system.queues.title')"
        :subtitle="__('system.queues.subtitle', ['count' => $total])"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('system.health.title'), 'url' => route('admin.system.health')], ['label' => __('system.queues.title')]]"
    />

    <x-admin.alert type="info" class="mb-6">{{ __('system.queues.notice') }}</x-admin.alert>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.text name="queue" :label="__('system.queues.fields.queue')" />
        <x-admin.filters.date-range name="failed" :label="__('system.queues.fields.failed_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
