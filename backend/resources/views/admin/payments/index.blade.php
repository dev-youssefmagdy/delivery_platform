<x-admin.layouts.app :title="__('admin.payments.title')">
    <x-admin.page-header
        :title="__('admin.payments.title')"
        :subtitle="__('admin.payments.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.payments.title')]]"
    />

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Payments\PaymentsDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="statuses" :label="__('admin.payments.fields.status')" :enum="\App\Enums\Payments\PaymentStatus::class" multiple />
        <x-admin.filters.enum name="kinds" :label="__('admin.payments.fields.kind')" :enum="\App\Enums\Payments\PaymentKind::class" multiple />
        <x-admin.filters.enum name="methods" :label="__('admin.payments.fields.method')" :enum="\App\Enums\Payments\PaymentMethodType::class" multiple />
        <x-admin.filters.enum name="gateways" :label="__('admin.payments.fields.gateway')" :enum="\App\Enums\Payments\PaymentGatewayName::class" multiple />
        <x-admin.filters.text name="search" :label="__('admin.payments.filters.search')" :placeholder="__('admin.payments.filters.search_hint')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.payments.fields.created')" />
        <x-admin.filters.number-range name="amount" :label="__('admin.payments.fields.amount')" step="0.01" min="0" :suffix="config('admin.default_currency')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
