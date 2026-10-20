<x-admin.layouts.app :title="__('admin.service_families.edit_title')">
    <x-admin.page-header
        :title="__('admin.service_families.edit_title')"
        :breadcrumbs="[['label' => __('admin.service_families.title'), 'url' => route('admin.service-families.index')], ['label' => $family->name, 'url' => route('admin.service-families.show', $family)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.service-families.update', $family)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $family->lock_version }}">
            @include('admin.service-families._form', ['model' => $family])
            <x-admin.form.actions :cancel="route('admin.service-families.show', $family)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
