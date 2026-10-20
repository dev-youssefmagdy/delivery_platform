<x-admin.layouts.app :title="__('admin.service_categories.create_title')">
    <x-admin.page-header
        :title="__('admin.service_categories.create_title')"
        :breadcrumbs="[['label' => __('admin.service_categories.title'), 'url' => route('admin.service-categories.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.service-categories.store')" method="POST">
            @include('admin.service-categories._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.service-categories.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
