<x-admin.layouts.app :title="__('admin.ledger.accounts.title')">
    <x-admin.page-header
        :title="__('admin.ledger.accounts.title')"
        :subtitle="__('admin.ledger.accounts.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.ledger.accounts.title')]]"
    >
        @can('viewAny', \App\Models\JournalEntry::class)
            <x-admin.button variant="secondary" icon="clipboard" :href="route('admin.journal-entries.index')">{{ __('admin.ledger.entries.menu') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Finance\LedgerAccountsDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="types" :label="__('admin.ledger.accounts.fields.type')" :enum="\App\Enums\Finance\LedgerAccountType::class" multiple />
        <x-admin.filters.text name="currency" :label="__('admin.ledger.accounts.fields.currency')" placeholder="EGP" />
        <x-admin.filters.boolean name="negative_only" :label="__('admin.ledger.accounts.filters.negative_only')" />
        <x-admin.filters.number-range name="balance" :label="__('admin.ledger.accounts.fields.balance')" />
        <x-admin.filters.text name="uuid" :label="__('admin.ledger.accounts.filters.uuid')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
