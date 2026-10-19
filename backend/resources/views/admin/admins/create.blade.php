<x-admin.layouts.app :title="__('admin.admins.create_title')">
    <x-admin.page-header
        :title="__('admin.admins.create_title')"
        :breadcrumbs="[['label' => __('admin.admins.title'), 'url' => route('admin.admins.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.admins.store')" method="POST">
            @include('admin.admins._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.admins.index')" :submit="__('admin.admins.send_invitation')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
