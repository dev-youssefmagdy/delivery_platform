<x-admin.layouts.app :title="__('admin.roles.create_title')">
    <x-admin.page-header
        :title="__('admin.roles.create_title')"
        :breadcrumbs="[['label' => __('admin.roles.title'), 'url' => route('admin.roles.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.roles.store')" method="POST">
            @include('admin.roles._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.roles.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
