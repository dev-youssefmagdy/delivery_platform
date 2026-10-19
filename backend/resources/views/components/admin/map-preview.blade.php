@props(['lat' => null, 'lng' => null, 'polygon' => null, 'label' => null])
{{-- Read-only map of a point and/or polygon ([[lat, lng], …]). --}}
@if (($lat !== null && $lng !== null) || ! empty($polygon))
    <div
        {{ $attributes->class(['map-canvas h-56']) }}
        data-map-preview
        @if ($lat !== null && $lng !== null) data-point="{{ json_encode([(float) $lat, (float) $lng]) }}" @endif
        @if (! empty($polygon)) data-polygon="{{ json_encode($polygon) }}" @endif
        role="img"
        aria-label="{{ $label ?? __('admin.form.map_point_hint') }}"
    ></div>
@else
    <span class="text-sm text-slate-400">{{ __('admin.common.not_set') }}</span>
@endif
