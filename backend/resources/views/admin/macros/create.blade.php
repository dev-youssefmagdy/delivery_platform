<x-admin.layouts.app :title="__('admin.macros.create_title')">
    <x-admin.page-header
        :title="__('admin.macros.create_title')"
        :breadcrumbs="[['label' => __('admin.macros.title'), 'url' => route('admin.macros.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.macros.store')" method="POST">
            @include('admin.macros._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.macros.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
