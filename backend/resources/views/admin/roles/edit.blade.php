<x-admin.layouts.app :title="__('admin.roles.edit_title')">
    <x-admin.page-header
        :title="__('admin.roles.edit_title')"
        :breadcrumbs="[['label' => __('admin.roles.title'), 'url' => route('admin.roles.index')], ['label' => $role->displayName(), 'url' => route('admin.roles.show', $role)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.roles.update', $role)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $role->lock_version }}">
            @include('admin.roles._form', ['model' => $role])
            <x-admin.form.actions :cancel="route('admin.roles.show', $role)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
