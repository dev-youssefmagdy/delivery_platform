<x-admin.layouts.app :title="__('admin.notification_templates.title')">
    <x-admin.page-header
        :title="__('admin.notification_templates.title')"
        :subtitle="__('admin.notification_templates.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.notification_templates.title')]]"
    >
        @can('create', \App\Models\NotificationTemplate::class)
            <x-admin.button :href="route('admin.notification-templates.create')" icon="plus">{{ __('admin.common.create') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ \App\DataTables\Admin\Notification\NotificationTemplatesDataTable::TABLE_ID }}">
        <x-admin.filters.text name="event_key" :label="__('admin.notification_templates.fields.event')" :placeholder="__('admin.notification_templates.filters.event_hint')" />
        <x-admin.filters.enum name="categories" :label="__('admin.notification_templates.fields.category')" :enum="\App\Enums\Notification\NotificationCategory::class" multiple />
        <x-admin.filters.enum name="channels" :label="__('admin.notification_templates.fields.channel')" :enum="\App\Enums\Notification\NotificationChannel::class" multiple />
        <x-admin.filters.enum name="locales" :label="__('admin.notification_templates.fields.locale')" :enum="\App\Enums\Common\Locale::class" multiple />
        <x-admin.filters.boolean name="active" :label="__('admin.notification_templates.fields.active')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
