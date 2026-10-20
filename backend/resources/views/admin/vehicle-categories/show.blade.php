<x-admin.layouts.app :title="__('admin.vehicle_categories.show_title')">
    <x-admin.page-header
        :title="$vehicle->name"
        :breadcrumbs="[['label' => __('admin.vehicle_categories.title'), 'url' => route('admin.vehicle-categories.index')], ['label' => $vehicle->name]]"
    >
        @can('update', $vehicle)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.vehicle-categories.edit', $vehicle)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('activate', $vehicle)
            <x-admin.confirm-action :url="route('admin.vehicle-categories.activate', ['vehicleCategory' => $vehicle, 'from_show' => 1])" :title="__('admin.catalog.confirm.activate_title')" :message="__('admin.vehicle_categories.confirm.activate_message')" :confirm-label="__('admin.catalog.actions.activate')" variant="success" icon="play">{{ __('admin.catalog.actions.activate') }}</x-admin.confirm-action>
        @endcan
        @can('deactivate', $vehicle)
            <x-admin.confirm-action :url="route('admin.vehicle-categories.deactivate', ['vehicleCategory' => $vehicle, 'from_show' => 1])" :title="__('admin.catalog.confirm.deactivate_title')" :message="__('admin.vehicle_categories.confirm.deactivate_message')" :confirm-label="__('admin.catalog.actions.deactivate')" reason-required icon="pause">{{ __('admin.catalog.actions.deactivate') }}</x-admin.confirm-action>
        @endcan
        @can('delete', $vehicle)
            <x-admin.confirm-action :url="route('admin.vehicle-categories.destroy', ['vehicleCategory' => $vehicle, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @unless ($vehicle->active)
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.catalog.inactive_notice') }}</x-admin.alert>
    @endunless

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.code')"><span class="ltr-only">{{ $vehicle->code }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.vehicle_categories.fields.name').' ('.\App\Enums\Common\Locale::Arabic->label().')'" :value="$vehicle->getTranslation('name', 'ar', false)" />
                    <x-admin.detail-list.item :label="__('admin.vehicle_categories.fields.name').' ('.\App\Enums\Common\Locale::English->label().')'" :value="$vehicle->getTranslation('name', 'en', false)" />
                    <x-admin.detail-list.item :label="__('admin.vehicle_categories.fields.description')" :value="$vehicle->getTranslation('description', app()->getLocale(), true)" />
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.icon')"><span class="ltr-only">{{ $vehicle->icon ?? '—' }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.map_marker')"><span class="ltr-only">{{ $vehicle->map_marker ?? '—' }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.vehicle_categories.fields.passenger_capacity')" :value="$vehicle->passenger_capacity" />
                    <x-admin.detail-list.item :label="__('admin.vehicle_categories.fields.luggage_capacity')" :value="$vehicle->luggage_capacity" />
                    <x-admin.detail-list.item :label="__('admin.vehicle_categories.fields.max_payload_kg')" :value="$vehicle->max_payload_kg" />
                    <x-admin.detail-list.item :label="__('admin.vehicle_categories.fields.max_volume_m3')" :value="$vehicle->max_volume_m3" />
                    <x-admin.detail-list.item :label="__('admin.vehicle_categories.fields.service_categories')" :value="$vehicle->service_categories_count" />
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.sort_order')" :value="$vehicle->sort_order" />
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.active')">
                        <x-admin.badge :label="$vehicle->active ? __('admin.common.yes') : __('admin.common.no')" :color="$vehicle->active ? 'success' : 'neutral'" />
                    </x-admin.detail-list.item>
                </x-admin.detail-list>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="activity" :url="route('admin.vehicle-categories.activity', $vehicle)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>
