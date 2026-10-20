<x-admin.layouts.app :title="__('admin.service_zones.show_title')">
    <x-admin.page-header
        :title="$zone->name"
        :breadcrumbs="[['label' => __('admin.service_zones.title'), 'url' => route('admin.service-zones.index')], ['label' => $zone->name]]"
    >
        @can('update', $zone)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.service-zones.edit', $zone)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('activate', $zone)
            <x-admin.confirm-action :url="route('admin.service-zones.activate', ['serviceZone' => $zone, 'from_show' => 1])" :title="__('admin.service_zones.confirm.activate_title')" :message="__('admin.service_zones.confirm.activate_message')" :confirm-label="__('admin.geo.actions.activate')" variant="success" icon="play">{{ __('admin.geo.actions.activate') }}</x-admin.confirm-action>
        @endcan
        @can('suspend', $zone)
            <x-admin.confirm-action :url="route('admin.service-zones.suspend', ['serviceZone' => $zone, 'from_show' => 1])" :title="__('admin.service_zones.confirm.suspend_title')" :message="__('admin.service_zones.confirm.suspend_message')" :confirm-label="__('admin.geo.actions.suspend')" reason-required icon="pause">{{ __('admin.geo.actions.suspend') }}</x-admin.confirm-action>
        @endcan
        @can('delete', $zone)
            <x-admin.confirm-action :url="route('admin.service-zones.destroy', ['serviceZone' => $zone, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($zone->status !== \App\Enums\Geo\ZoneStatus::Active)
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.service_zones.draft_notice') }}</x-admin.alert>
    @endif
    @if ($overlaps->isNotEmpty())
        <x-admin.alert type="warning" class="mb-6">{{ __('admin.service_zones.overlap_warning', ['zones' => $overlaps->map(fn ($other) => $other->name)->implode('، ')]) }}</x-admin.alert>
    @endif

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.service_zones.tabs.details'), 'icon' => 'document'],
            'availability' => ['label' => __('admin.service_zones.tabs.availability'), 'icon' => 'squares'],
            'overlaps' => ['label' => __('admin.service_zones.tabs.overlaps'), 'icon' => 'map'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.service_zones.fields.name').' ('.\App\Enums\Common\Locale::Arabic->label().')'" :value="$zone->getTranslation('name', 'ar', false)" />
                    <x-admin.detail-list.item :label="__('admin.service_zones.fields.name').' ('.\App\Enums\Common\Locale::English->label().')'" :value="$zone->getTranslation('name', 'en', false)" />
                    <x-admin.detail-list.item :label="__('admin.service_zones.fields.city')">
                        <a class="text-brand-700 hover:underline" href="{{ route('admin.cities.show', $zone->city) }}">{{ $zone->city->name }}</a>
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.service_zones.fields.status')"><x-admin.badge :value="$zone->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.service_zones.fields.restricted_pickup')">
                        <x-admin.badge :label="$zone->restricted_pickup ? __('admin.common.yes') : __('admin.common.no')" :color="$zone->restricted_pickup ? 'warning' : 'neutral'" />
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.service_zones.fields.area')"><span class="ltr-only">{{ number_format($areaKm2, 2) }} km²</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.service_zones.fields.operating_hours')">
                        @forelse ($zone->operating_hours ?? [] as $window)
                            <div class="ltr-only">{{ \App\Enums\Catalog\Weekday::from((int) $window['day'])->label() }} {{ $window['from'] }}–{{ $window['to'] }}</div>
                        @empty
                            24/7
                        @endforelse
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.service_zones.fields.notes')" :value="$zone->notes" />
                    @if ($zone->is_demo)
                        <x-admin.detail-list.item :label="__('admin.service_zones.fields.demo')"><x-admin.badge :label="__('admin.common.yes')" color="info" /></x-admin.detail-list.item>
                    @endif
                    <x-admin.detail-list.item :label="__('admin.service_zones.fields.polygon')">
                        <x-admin.map-preview :polygon="$zone->polygon->toArray()" />
                    </x-admin.detail-list.item>
                </x-admin.detail-list>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="availability">
                @can('update', $zone)
                    @include('admin.service-zones._availability')
                @endcan
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="overlaps">
                @forelse ($overlaps as $other)
                    <p class="py-1"><a class="text-brand-700 hover:underline" href="{{ route('admin.service-zones.show', $other) }}">{{ $other->name }}</a> <span class="text-sm text-slate-500">({{ $other->city->name }})</span></p>
                @empty
                    <p class="text-sm text-slate-600">{{ __('admin.service_zones.overlap_none') }}</p>
                @endforelse
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="activity" :url="route('admin.service-zones.activity', $zone)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>
