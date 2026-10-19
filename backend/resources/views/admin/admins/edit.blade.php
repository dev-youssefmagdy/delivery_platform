@php($admin = $details->admin)
<x-admin.layouts.app :title="__('admin.admins.edit_title')">
    <x-admin.page-header
        :title="__('admin.admins.edit_title')"
        :breadcrumbs="[['label' => __('admin.admins.title'), 'url' => route('admin.admins.index')], ['label' => $admin->name, 'url' => route('admin.admins.show', $admin)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.admins.update', $admin)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $admin->lock_version }}">
            @include('admin.admins._form', ['model' => $admin])
            <x-admin.form.actions :cancel="route('admin.admins.show', $admin)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
