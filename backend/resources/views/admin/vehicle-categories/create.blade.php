<x-admin.layouts.app :title="__('admin.vehicle_categories.create_title')">
    <x-admin.page-header
        :title="__('admin.vehicle_categories.create_title')"
        :breadcrumbs="[['label' => __('admin.vehicle_categories.title'), 'url' => route('admin.vehicle-categories.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.vehicle-categories.store')" method="POST">
            @include('admin.vehicle-categories._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.vehicle-categories.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
