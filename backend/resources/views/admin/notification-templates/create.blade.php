<x-admin.layouts.app :title="__('admin.notification_templates.create_title')">
    <x-admin.page-header
        :title="__('admin.notification_templates.create_title')"
        :breadcrumbs="[['label' => __('admin.notification_templates.title'), 'url' => route('admin.notification-templates.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.notification-templates.store')" method="POST">
            @include('admin.notification-templates._form', ['model' => null, 'event' => null])
            <x-admin.form.actions :cancel="route('admin.notification-templates.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
