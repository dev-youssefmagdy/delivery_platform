<x-admin.layouts.app :title="__('admin.service_zones.create_title')">
    <x-admin.page-header
        :title="__('admin.service_zones.create_title')"
        :breadcrumbs="[['label' => __('admin.service_zones.title'), 'url' => route('admin.service-zones.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.service-zones.store')" method="POST">
            @include('admin.service-zones._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.service-zones.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
