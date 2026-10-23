@php
    /** @var array<string, mixed> $trip */
    $position = $trip['position'];
    $vehicle = $trip['vehicle'];
    $vehicleText = $vehicle === null ? null : trim(implode(' ', array_filter([$vehicle['color'] ?? null, $vehicle['make'] ?? null, $vehicle['model'] ?? null])));
@endphp
<x-website.layouts.app :title="__('website.trip_share.title')">
    <section class="rounded-xl bg-white p-6 shadow-sm" aria-live="polite">
        <h1 class="text-lg font-semibold">{{ __('website.trip_share.heading') }}</h1>

        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">{{ __('website.trip_share.status') }}</dt>
                <dd class="font-medium" id="trip-status">{{ $trip['status_label'] }}</dd>
            </div>
            @if ($trip['provider_first_name'])
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('website.trip_share.driver') }}</dt>
                    <dd class="font-medium">{{ $trip['provider_first_name'] }}</dd>
                </div>
            @endif
            @if ($vehicleText)
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('website.trip_share.vehicle') }}</dt>
                    <dd class="font-medium">{{ $vehicleText }}@if (! empty($vehicle['plate'])) <span dir="ltr" class="ms-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $vehicle['plate'] }}</span>@endif</dd>
                </div>
            @endif
            <div>
                <dt class="text-slate-500">{{ __('website.trip_share.position') }}</dt>
                <dd class="mt-1" id="trip-position">
                    @if ($position)
                        <a class="text-blue-700 underline" rel="noopener noreferrer" target="_blank" href="https://www.openstreetmap.org/?mlat={{ $position['lat'] }}&mlon={{ $position['lng'] }}#map=16/{{ $position['lat'] }}/{{ $position['lng'] }}">{{ __('website.trip_share.open_map') }}</a>
                        <span class="block text-xs text-slate-500">{{ __('website.trip_share.updated_at', ['time' => $position['recorded_at']->format('H:i')]) }}</span>
                    @else
                        <span class="text-slate-600">{{ __('website.trip_share.position_unknown') }}</span>
                    @endif
                </dd>
            </div>
        </dl>

        @if ($trip['estimated_trip_minutes'])
            <p class="mt-4 text-xs text-slate-500">{{ __('website.trip_share.estimate', ['minutes' => $trip['estimated_trip_minutes']]) }}</p>
        @endif
        <p class="mt-2 text-xs text-slate-500">{{ __('website.trip_share.expires', ['time' => $trip['expires_at']->format('H:i')]) }}</p>
        <p class="mt-2 text-xs text-slate-400">{{ __('website.trip_share.privacy') }} {{ __('website.trip_share.refreshing') }}</p>
    </section>

    <script nonce="{{ $cspNonce ?? '' }}">
        (function () {
            var url = @json(route('website.trip-share.data', ['token' => $token]));
            var openMap = @json(__('website.trip_share.open_map'));
            var unknown = @json(__('website.trip_share.position_unknown'));
            function tick() {
                fetch(url, {headers: {'Accept': 'application/json'}, cache: 'no-store', credentials: 'omit'})
                    .then(function (r) { if (r.status === 404) { window.location.reload(); return null; } return r.json(); })
                    .then(function (d) {
                        if (!d) { return; }
                        document.getElementById('trip-status').textContent = d.status_label;
                        var el = document.getElementById('trip-position');
                        if (d.position) {
                            var a = document.createElement('a');
                            a.className = 'text-blue-700 underline';
                            a.rel = 'noopener noreferrer'; a.target = '_blank'; a.textContent = openMap;
                            a.href = 'https://www.openstreetmap.org/?mlat=' + d.position.lat + '&mlon=' + d.position.lng + '#map=16/' + d.position.lat + '/' + d.position.lng;
                            el.replaceChildren(a);
                        } else {
                            var s = document.createElement('span'); s.className = 'text-slate-600'; s.textContent = unknown; el.replaceChildren(s);
                        }
                    })
                    .catch(function () {});
            }
            setInterval(tick, 15000);
        })();
    </script>
</x-website.layouts.app>
