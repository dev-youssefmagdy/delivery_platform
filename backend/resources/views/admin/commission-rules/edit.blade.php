<x-admin.layouts.app :title="__('admin.commission_rules.edit_title')">
    <x-admin.page-header
        :title="__('admin.commission_rules.edit_title')"
        :breadcrumbs="[['label' => __('admin.commission_rules.title'), 'url' => route('admin.commission-rules.index')], ['label' => $rule->name, 'url' => route('admin.commission-rules.show', $rule)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.alert type="info" class="mb-6">{{ __('admin.commission_rules.edit_notice') }}</x-admin.alert>

    <x-admin.card>
        <x-admin.form :action="route('admin.commission-rules.update', $rule)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $rule->lock_version }}">
            @include('admin.commission-rules._form', ['model' => $rule])
            <x-admin.form.actions :cancel="route('admin.commission-rules.show', $rule)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
