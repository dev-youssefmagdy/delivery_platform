<x-admin.layouts.app :title="__('admin.currencies.edit_title')">
    <x-admin.page-header
        :title="__('admin.currencies.edit_title')"
        :breadcrumbs="[['label' => __('admin.currencies.title'), 'url' => route('admin.currencies.index')], ['label' => $currency->code, 'url' => route('admin.currencies.show', $currency)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.currencies.update', $currency)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $currency->lock_version }}">
            @include('admin.currencies._form', ['model' => $currency])
            <x-admin.form.actions :cancel="route('admin.currencies.show', $currency)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
