<x-admin.layouts.app :title="__('admin.cities.edit_title')">
    <x-admin.page-header
        :title="__('admin.cities.edit_title')"
        :breadcrumbs="[['label' => __('admin.cities.title'), 'url' => route('admin.cities.index')], ['label' => $city->name, 'url' => route('admin.cities.show', $city)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.cities.update', $city)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $city->lock_version }}">
            @include('admin.cities._form', ['model' => $city])
            <x-admin.form.actions :cancel="route('admin.cities.show', $city)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
