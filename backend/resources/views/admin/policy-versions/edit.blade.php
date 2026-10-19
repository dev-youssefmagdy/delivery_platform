<x-admin.layouts.app :title="__('admin.policy_versions.edit_title')">
    <x-admin.page-header
        :title="__('admin.policy_versions.edit_title')"
        :breadcrumbs="[['label' => __('admin.policy_versions.title'), 'url' => route('admin.policy-versions.index')], ['label' => $policy->title, 'url' => route('admin.policy-versions.show', $policy)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.policy-versions.update', $policy)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $policy->lock_version }}">
            @include('admin.policy-versions._form', ['model' => $policy])
            <x-admin.form.actions :cancel="route('admin.policy-versions.show', $policy)" :submit="__('admin.policy_versions.save_draft')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
