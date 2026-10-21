<x-admin.layouts.app :title="__('admin.fare_policies.create_title')">
    <x-admin.page-header
        :title="__('admin.fare_policies.create_title')"
        :breadcrumbs="[['label' => __('admin.fare_policies.title'), 'url' => route('admin.fare-policies.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.fare-policies.store')" method="POST">
            @include('admin.fare-policies._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.fare-policies.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
