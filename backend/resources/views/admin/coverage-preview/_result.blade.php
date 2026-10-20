{{-- $preview: \App\DTOs\Geography\CoveragePreview --}}
@php($origin = $preview->origin)
<x-admin.card :title="__('admin.coverage.result')">
    <p class="mb-4 text-xs text-slate-500">{{ __('admin.coverage.note') }}</p>

    @foreach ([[__('admin.coverage.origin'), $preview->origin], [__('admin.coverage.destination'), $preview->destination]] as [$title, $area])
        @continue($area === null)
        <section class="mb-6">
            <h3 class="mb-2 text-sm font-semibold text-slate-800">{{ $title }}</h3>
            <x-admin.alert :type="$area->inServiceArea() ? 'success' : 'warning'" class="mb-3">{{ $area->inServiceArea() ? __('admin.coverage.in_area') : __('admin.coverage.outside') }}</x-admin.alert>
            @if ($area->inServiceArea())
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.coverage.city')" :value="$area->city->name" />
                    <x-admin.detail-list.item :label="__('admin.coverage.zone')">
                        <a class="text-brand-700 hover:underline" href="{{ route('admin.service-zones.show', $area->zone) }}">{{ $area->zone->name }}</a>
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.coverage.local_time')"><span class="ltr-only">{{ $area->localTime?->format('Y-m-d H:i') }} ({{ $area->city->timezone }})</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.coverage.restricted')" :value="$area->restrictedPickup() ? __('admin.common.yes') : __('admin.common.no')" />
                    <x-admin.detail-list.item :label="__('admin.coverage.categories')">
                        @forelse ($area->categories as $availability)
                            <div class="flex items-center gap-2 py-0.5">
                                <x-admin.badge :label="$availability->isAvailable() ? __('admin.coverage.available') : $availability->reason->label()" :color="$availability->isAvailable() ? 'success' : 'warning'" />
                                <span>{{ $availability->category->name }}</span>
                            </div>
                        @empty
                            <span class="text-slate-500">{{ __('admin.coverage.no_categories') }}</span>
                        @endforelse
                    </x-admin.detail-list.item>
                </x-admin.detail-list>
            @endif
        </section>
    @endforeach

    @if ($preview->destination !== null)
        <section>
            <h3 class="mb-2 text-sm font-semibold text-slate-800">{{ __('admin.coverage.route') }}</h3>
            @if ($preview->route !== null)
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.coverage.distance')"><span class="ltr-only">{{ number_format($preview->route->distanceMeters / 1000, 1) }} {{ __('admin.coverage.km') }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.coverage.duration')"><span class="ltr-only">{{ (int) round($preview->route->durationSeconds / 60) }} {{ __('admin.coverage.min') }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.coverage.provider')"><span class="ltr-only">{{ $preview->route->provider }}</span></x-admin.detail-list.item>
                </x-admin.detail-list>
            @else
                <x-admin.alert type="warning">{{ __('admin.coverage.route_errors.'.$preview->routeError) }}</x-admin.alert>
            @endif
        </section>
    @endif
</x-admin.card>
