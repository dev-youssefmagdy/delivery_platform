<x-admin.layouts.app :title="__('admin.currencies.create_title')">
    <x-admin.page-header
        :title="__('admin.currencies.create_title')"
        :breadcrumbs="[['label' => __('admin.currencies.title'), 'url' => route('admin.currencies.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.currencies.store')" method="POST">
            @include('admin.currencies._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.currencies.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
