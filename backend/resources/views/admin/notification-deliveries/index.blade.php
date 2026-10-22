<x-admin.layouts.app :title="__('admin.notification_deliveries.title')">
    <x-admin.page-header
        :title="__('admin.notification_deliveries.title')"
        :subtitle="__('admin.notification_deliveries.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.notification_deliveries.title')]]"
    />

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Notification\NotificationDeliveriesDataTable::TABLE_ID }}">
        <x-admin.filters.enum name="channels" :label="__('admin.notification_deliveries.fields.channel')" :enum="\App\Enums\Notification\NotificationChannel::class" multiple />
        <x-admin.filters.enum name="statuses" :label="__('admin.notification_deliveries.fields.status')" :enum="\App\Enums\Notification\DeliveryStatus::class" multiple />
        <x-admin.filters.enum name="events" :label="__('admin.notification_deliveries.fields.event')" :enum="\App\Enums\Notification\NotificationEvent::class" multiple />
        <x-admin.filters.date-range name="created" :label="__('admin.notification_deliveries.fields.created')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
