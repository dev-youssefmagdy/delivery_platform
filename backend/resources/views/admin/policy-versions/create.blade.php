<x-admin.layouts.app :title="__('admin.policy_versions.create_title')">
    <x-admin.page-header
        :title="__('admin.policy_versions.create_title')"
        :breadcrumbs="[['label' => __('admin.policy_versions.title'), 'url' => route('admin.policy-versions.index')], ['label' => __('admin.common.create')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.policy-versions.store')" method="POST">
            @include('admin.policy-versions._form', ['model' => null])
            <x-admin.form.actions :cancel="route('admin.policy-versions.index')" :submit="__('admin.policy_versions.save_draft')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
