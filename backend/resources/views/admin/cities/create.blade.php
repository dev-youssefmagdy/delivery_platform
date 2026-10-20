<x-admin.layouts.app :title="__('admin.cities.create_title')">
    <x-admin.page-header
        :title="__('admin.cities.create_title')"
        :breadcrumbs="[['label' => __('admin.cities.title'), 'url' => route('admin.cities.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.cities.store')" method="POST">
            @include('admin.cities._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.cities.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
