<x-admin.layouts.app :title="__('admin.cancellation_policies.create_title')">
    <x-admin.page-header
        :title="__('admin.cancellation_policies.create_title')"
        :breadcrumbs="[['label' => __('admin.cancellation_policies.title'), 'url' => route('admin.cancellation-policies.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.cancellation-policies.store')" method="POST">
            @include('admin.cancellation-policies._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.cancellation-policies.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
