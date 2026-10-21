<x-admin.layouts.app :title="__('admin.fare_policies.edit_title')">
    <x-admin.page-header
        :title="__('admin.fare_policies.edit_title')"
        :breadcrumbs="[['label' => __('admin.fare_policies.title'), 'url' => route('admin.fare-policies.index')], ['label' => '#'.$policy->id, 'url' => route('admin.fare-policies.show', $policy)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.alert type="info" class="mb-6">{{ __('admin.fare_policies.edit_notice') }}</x-admin.alert>

    <x-admin.card>
        <x-admin.form :action="route('admin.fare-policies.update', $policy)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $policy->lock_version }}">
            @include('admin.fare-policies._form', ['model' => $policy])
            <x-admin.form.actions :cancel="route('admin.fare-policies.show', $policy)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
