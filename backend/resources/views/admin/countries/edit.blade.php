<x-admin.layouts.app :title="__('admin.countries.edit_title')">
    <x-admin.page-header
        :title="__('admin.countries.edit_title')"
        :breadcrumbs="[['label' => __('admin.countries.title'), 'url' => route('admin.countries.index')], ['label' => $country->name, 'url' => route('admin.countries.show', $country)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.countries.update', $country)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $country->lock_version }}">
            @include('admin.countries._form', ['model' => $country])
            <x-admin.form.actions :cancel="route('admin.countries.show', $country)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
