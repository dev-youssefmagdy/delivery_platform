<x-admin.layouts.app :title="__('admin.vehicle_categories.edit_title')">
    <x-admin.page-header
        :title="__('admin.vehicle_categories.edit_title')"
        :breadcrumbs="[['label' => __('admin.vehicle_categories.title'), 'url' => route('admin.vehicle-categories.index')], ['label' => $vehicle->name, 'url' => route('admin.vehicle-categories.show', $vehicle)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.vehicle-categories.update', $vehicle)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $vehicle->lock_version }}">
            @include('admin.vehicle-categories._form', ['model' => $vehicle])
            <x-admin.form.actions :cancel="route('admin.vehicle-categories.show', $vehicle)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
