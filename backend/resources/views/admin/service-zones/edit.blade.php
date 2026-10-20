<x-admin.layouts.app :title="__('admin.service_zones.edit_title')">
    <x-admin.page-header
        :title="__('admin.service_zones.edit_title')"
        :breadcrumbs="[['label' => __('admin.service_zones.title'), 'url' => route('admin.service-zones.index')], ['label' => $zone->name, 'url' => route('admin.service-zones.show', $zone)], ['label' => __('admin.common.edit')]]"
    />

    <x-admin.card>
        <x-admin.form :action="route('admin.service-zones.update', $zone)" method="PUT">
            <input type="hidden" name="lock_version" value="{{ $zone->lock_version }}">
            @include('admin.service-zones._form', ['model' => $zone])
            <x-admin.form.actions :cancel="route('admin.service-zones.show', $zone)" :submit="__('admin.common.save_changes')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
