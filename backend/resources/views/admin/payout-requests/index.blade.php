<x-admin.layouts.app :title="__('admin.payouts.title')">
    <x-admin.page-header
        :title="__('admin.payouts.title')"
        :subtitle="__('admin.payouts.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.payouts.title')]]"
    >
        @can('createBatch', \App\Models\PayoutRequest::class)
            <x-admin.button variant="secondary" icon="archive" data-modal-open="payout-batch-create">{{ __('admin.payouts.actions.create_batch') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Payout\PayoutRequestsDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="statuses" :label="__('admin.payouts.fields.status')" :enum="\App\Enums\Payout\PayoutStatus::class" multiple />
        <x-admin.filters.remote-select name="provider_ids" :label="__('admin.payouts.fields.provider')" lookup="finance-providers" multiple />
        <x-admin.filters.number-range name="amount" :label="__('admin.payouts.fields.amount')" step="0.01" min="0" :suffix="config('admin.default_currency')" />
        <x-admin.filters.date-range name="requested_at" :label="__('admin.payouts.fields.requested_at')" />
        <x-admin.filters.remote-select name="approver_ids" :label="__('admin.payouts.fields.approver')" lookup="admins" multiple />
        <x-admin.filters.text name="search" :label="__('admin.payouts.filters.search')" :placeholder="__('admin.payouts.filters.search_hint')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>

    @can('createBatch', \App\Models\PayoutRequest::class)
        <x-admin.action-modal id="payout-batch-create" :title="__('admin.batches.create_title')" :message="__('admin.batches.create_message')" :action="route('admin.payout-batches.store')" :submit="__('admin.payouts.actions.create_batch')" variant="primary" icon="archive" />
    @endcan
</x-admin.layouts.app>
