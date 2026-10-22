<x-admin.layouts.app :title="__('admin.webhooks.title')">
    <x-admin.page-header
        :title="__('admin.webhooks.title')"
        :subtitle="__('admin.webhooks.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.webhooks.title')]]"
    />

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Payments\GatewayWebhookEventsDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="outcomes" :label="__('admin.webhooks.fields.outcome')" :enum="\App\Enums\Payments\WebhookOutcome::class" multiple />
        <x-admin.filters.boolean name="needs_attention" :label="__('admin.webhooks.filters.needs_attention')" />
        <x-admin.filters.enum name="gateways" :label="__('admin.webhooks.fields.gateway')" :enum="\App\Enums\Payments\PaymentGatewayName::class" multiple />
        <x-admin.filters.enum name="kinds" :label="__('admin.webhooks.fields.kind')" :enum="\App\Enums\Payments\WebhookEventKind::class" multiple />
        <x-admin.filters.boolean name="signature_valid" :label="__('admin.webhooks.fields.signature')" />
        <x-admin.filters.text name="search" :label="__('admin.webhooks.filters.search')" :placeholder="__('admin.webhooks.filters.search_hint')" />
        <x-admin.filters.date-range name="received_at" :label="__('admin.webhooks.fields.received')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
