<x-admin.layouts.app :title="__('admin.customers.title')">
    <x-admin.page-header
        :title="__('admin.customers.title')"
        :subtitle="__('admin.customers.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.customers.title')]]"
    >
        @can('export', \App\Models\User::class)
            <x-admin.button variant="secondary" icon="download" :data-export-url="route('admin.customers.export')" data-export-table="{{ \App\DataTables\Admin\Customers\CustomersDataTable::TABLE_ID }}">{{ __('admin.common.export') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Customers\CustomersDataTable::TABLE_ID }}">
        <x-admin.filters.text name="search" :label="__('admin.customers.filters.search')" :placeholder="__('admin.customers.filters.search_placeholder')" />
        <x-admin.filters.enum name="statuses" :label="__('admin.customers.fields.status')" :enum="\App\Enums\Identity\UserStatus::class" multiple />
        <x-admin.filters.date-range name="created_at" :label="__('admin.customers.fields.registered')" />
        <x-admin.filters.remote-select name="city_ids" :label="__('admin.customers.filters.city')" lookup="cities" multiple />
        <x-admin.filters.boolean name="deletion_requested" :label="__('admin.customers.fields.deletion_requested')" />
        <div class="min-w-0">
            <p class="form-label">{{ __('admin.customers.filters.active_booking') }}</p>
            <p class="text-sm text-slate-500">{{ __('admin.common.available_after', ['milestone' => 'P2.3', 'module' => __('admin.customers.tabs.bookings')]) }}</p>
        </div>
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
