<x-admin.layouts.app :title="__('admin.cancellation_policies.edit_title')">
    <x-admin.page-header
        :title="__('admin.cancellation_policies.edit_title')"
        :breadcrumbs="[['label' => __('admin.cancellation_policies.title'), 'url' => route('admin.cancellation-policies.index')], ['label' => '#'.$policy->id, 'url' => route('admin.cancellation-policies.show', $policy)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.alert type="info" class="mb-6">{{ __('admin.cancellation_policies.edit_notice') }}</x-admin.alert>

    <x-admin.card>
        <x-admin.form :action="route('admin.cancellation-policies.update', $policy)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $policy->lock_version }}">
            @include('admin.cancellation-policies._form', ['model' => $policy])
            <x-admin.form.actions :cancel="route('admin.cancellation-policies.show', $policy)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
