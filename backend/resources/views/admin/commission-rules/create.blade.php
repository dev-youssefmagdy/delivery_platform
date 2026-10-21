<x-admin.layouts.app :title="__('admin.commission_rules.create_title')">
    <x-admin.page-header
        :title="__('admin.commission_rules.create_title')"
        :breadcrumbs="[['label' => __('admin.commission_rules.title'), 'url' => route('admin.commission-rules.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.commission-rules.store')" method="POST">
            @include('admin.commission-rules._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.commission-rules.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
