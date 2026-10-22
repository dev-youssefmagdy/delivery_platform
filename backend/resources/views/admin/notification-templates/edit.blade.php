<x-admin.layouts.app :title="__('admin.notification_templates.edit_title')">
    <x-admin.page-header
        :title="__('admin.notification_templates.edit_title')"
        :subtitle="$event?->label()"
        :breadcrumbs="[['label' => __('admin.notification_templates.title'), 'url' => route('admin.notification-templates.index')], ['label' => '#'.$template->id, 'url' => route('admin.notification-templates.show', $template)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.notification-templates.update', $template)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $template->lock_version }}">
            @include('admin.notification-templates._form', ['model' => $template])
            <x-admin.form.actions :cancel="route('admin.notification-templates.show', $template)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
