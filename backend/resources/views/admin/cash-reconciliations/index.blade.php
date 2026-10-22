@php($key = (string) \Illuminate\Support\Str::uuid())
<x-admin.layouts.app :title="__('admin.cash.title')">
    <x-admin.page-header
        :title="__('admin.cash.title')"
        :subtitle="__('admin.cash.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.cash.title')]]"
    >
        @can('create', \App\Models\CashReconciliation::class)
            <x-admin.button icon="plus" data-modal-open="cash-open">{{ __('admin.cash.actions.open') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Finance\CashReconciliationsDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="statuses" :label="__('admin.cash.fields.status')" :enum="\App\Enums\Finance\CashReconciliationStatus::class" multiple />
        <x-admin.filters.remote-select name="provider_ids" :label="__('admin.cash.fields.provider')" lookup="finance-providers" multiple />
        <x-admin.filters.date-range name="period" :label="__('admin.cash.fields.period')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>

    @can('create', \App\Models\CashReconciliation::class)
        <x-admin.modal id="cash-open" :title="__('admin.cash.open_title')" size="md">
            <x-admin.form :action="route('admin.cash-reconciliations.store')" method="POST">
                <p class="mb-4 text-sm text-slate-600">{{ __('admin.cash.open_message') }}</p>
                <div class="space-y-4">
                    <input type="hidden" name="idempotency_key" value="{{ $key }}">
                    <x-admin.form.remote-select name="provider_id" lookup="finance-providers" :label="__('admin.cash.fields.provider')" required />
                    <x-admin.form.date name="period_from" :label="__('admin.cash.fields.period_from')" :max="now()->toDateString()" required />
                    <x-admin.form.date name="period_to" :label="__('admin.cash.fields.period_to')" :max="now()->toDateString()" required />
                    <x-admin.form.textarea name="notes" :label="__('admin.cash.fields.notes')" rows="2" maxlength="1000" />
                </div>
                <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
                    <x-admin.button variant="secondary" x-on:click="$dispatch('admin-modal:close', { id: 'cash-open' })">{{ __('admin.common.cancel') }}</x-admin.button>
                    <x-admin.button type="submit" icon="plus">{{ __('admin.cash.actions.open') }}</x-admin.button>
                </div>
            </x-admin.form>
        </x-admin.modal>
    @endcan
</x-admin.layouts.app>
