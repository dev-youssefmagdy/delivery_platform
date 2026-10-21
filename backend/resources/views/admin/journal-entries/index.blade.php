<x-admin.layouts.app :title="__('admin.ledger.entries.title')">
    <x-admin.page-header
        :title="__('admin.ledger.entries.title')"
        :subtitle="__('admin.ledger.entries.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.ledger.entries.title')]]"
    />

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Finance\JournalEntriesDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="types" :label="__('admin.ledger.entries.fields.type')" :enum="\App\Enums\Finance\JournalEntryType::class" multiple />
        <x-admin.filters.select name="reference" :label="__('admin.ledger.entries.fields.reference')" :options="['booking' => __('admin.ledger.entries.reference_booking')]" />
        <x-admin.filters.text name="search" :label="__('admin.ledger.entries.filters.search')" :placeholder="__('admin.ledger.entries.filters.search_hint')" />
        <x-admin.filters.text name="account_uuid" :label="__('admin.ledger.entries.filters.account')" />
        <x-admin.filters.boolean name="reversals" :label="__('admin.ledger.entries.filters.reversals')" />
        <x-admin.filters.date-range name="posted_at" :label="__('admin.ledger.entries.fields.posted')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
