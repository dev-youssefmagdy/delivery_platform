<x-admin.layouts.app :title="__('admin.service_families.create_title')">
    <x-admin.page-header
        :title="__('admin.service_families.create_title')"
        :breadcrumbs="[['label' => __('admin.service_families.title'), 'url' => route('admin.service-families.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.service-families.store')" method="POST">
            @include('admin.service-families._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.service-families.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
