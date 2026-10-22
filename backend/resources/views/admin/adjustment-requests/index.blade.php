@php($key = (string) \Illuminate\Support\Str::uuid())
<x-admin.layouts.app :title="__('admin.adjustments.title')">
    <x-admin.page-header
        :title="__('admin.adjustments.title')"
        :subtitle="__('admin.adjustments.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.adjustments.title')]]"
    >
        @can('create', \App\Models\FinancialAdjustmentRequest::class)
            <x-admin.button icon="plus" data-modal-open="adjustment-create">{{ __('admin.adjustments.actions.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Finance\AdjustmentRequestsDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="statuses" :label="__('admin.adjustments.fields.status')" :enum="\App\Enums\Finance\ApprovalStatus::class" multiple />
        <x-admin.filters.enum name="directions" :label="__('admin.adjustments.fields.direction')" :enum="\App\Enums\Finance\AdjustmentDirection::class" multiple />
        <x-admin.filters.remote-select name="requester_ids" :label="__('admin.adjustments.fields.requested_by')" lookup="admins" multiple />
        <x-admin.filters.text name="search" :label="__('admin.adjustments.filters.search')" :placeholder="__('admin.adjustments.filters.search_hint')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.adjustments.fields.created')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>

    @can('create', \App\Models\FinancialAdjustmentRequest::class)
        <x-admin.action-modal id="adjustment-create" :title="__('admin.adjustments.create_title')" :message="__('admin.adjustments.create_message')" :action="route('admin.adjustment-requests.store')" :submit="__('admin.adjustments.actions.create')" variant="warning" icon="banknotes">
            <input type="hidden" name="idempotency_key" value="{{ $key }}">
            <x-admin.form.remote-select name="ledger_account_id" lookup="wallets" :label="__('admin.adjustments.fields.wallet')" :placeholder="__('admin.adjustments.wallet_placeholder')" required />
            <x-admin.form.enum-select name="direction" :enum="\App\Enums\Finance\AdjustmentDirection::class" :label="__('admin.adjustments.fields.direction')" required />
            <x-admin.form.money name="amount_minor" :label="__('admin.adjustments.fields.amount')" :currency="config('admin.default_currency')" required />
            <x-admin.form.input name="reference" :label="__('admin.adjustments.fields.reference')" maxlength="120" dir="ltr" />
        </x-admin.action-modal>
    @endcan
</x-admin.layouts.app>
