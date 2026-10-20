<x-admin.layouts.app :title="__('admin.cities.show_title')">
    <x-admin.page-header
        :title="$city->name"
        :breadcrumbs="[['label' => __('admin.cities.title'), 'url' => route('admin.cities.index')], ['label' => $city->name]]"
    >
        @can('update', $city)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.cities.edit', $city)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('activate', $city)
            <x-admin.confirm-action :url="route('admin.cities.activate', ['city' => $city, 'from_show' => 1])" :title="__('admin.geo.confirm.activate_title')" :message="__('admin.cities.confirm.activate_message')" :confirm-label="__('admin.geo.actions.activate')" variant="success" icon="play">{{ __('admin.geo.actions.activate') }}</x-admin.confirm-action>
        @endcan
        @can('deactivate', $city)
            <x-admin.confirm-action :url="route('admin.cities.deactivate', ['city' => $city, 'from_show' => 1])" :title="__('admin.geo.confirm.deactivate_title')" :message="__('admin.cities.confirm.deactivate_message')" :confirm-label="__('admin.geo.actions.deactivate')" reason-required icon="pause">{{ __('admin.geo.actions.deactivate') }}</x-admin.confirm-action>
        @endcan
        @can('delete', $city)
            <x-admin.confirm-action :url="route('admin.cities.destroy', ['city' => $city, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @unless ($city->active)
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.geo.inactive_notice') }}</x-admin.alert>
    @endunless

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.cities.fields.name').' ('.\App\Enums\Common\Locale::Arabic->label().')'" :value="$city->getTranslation('name', 'ar', false)" />
                    <x-admin.detail-list.item :label="__('admin.cities.fields.name').' ('.\App\Enums\Common\Locale::English->label().')'" :value="$city->getTranslation('name', 'en', false)" />
                    <x-admin.detail-list.item :label="__('admin.cities.fields.country')" :value="$city->country->name" />
                    <x-admin.detail-list.item :label="__('admin.cities.fields.timezone')"><span class="ltr-only">{{ $city->timezone }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.cities.fields.zones')" :value="$city->zones_count" />
                    <x-admin.detail-list.item :label="__('admin.geo.fields.active')">
                        <x-admin.badge :label="$city->active ? __('admin.common.yes') : __('admin.common.no')" :color="$city->active ? 'success' : 'neutral'" />
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.cities.sections.location')">
                        <x-admin.map-preview :lat="$city->center->lat" :lng="$city->center->lng" />
                    </x-admin.detail-list.item>
                </x-admin.detail-list>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="activity" :url="route('admin.cities.activity', $city)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>
