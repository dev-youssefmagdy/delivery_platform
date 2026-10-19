@props(['label' => null, 'hint' => null, 'latName' => 'lat', 'lngName' => 'lng', 'lat' => null, 'lng' => null, 'required' => false])
{{-- Map with a draggable marker + accessible numeric lat/lng inputs (always editable without the map). --}}
@php
    $latId = \App\Support\Admin\FieldName::id($latName);
    $lngId = \App\Support\Admin\FieldName::id($lngName);
@endphp
<fieldset {{ $attributes->class(['min-w-0 space-y-3']) }}>
    @if ($label)
        <legend class="form-label">{{ $label }}@if ($required)<span class="form-required" aria-hidden="true"> *</span>@endif</legend>
    @endif
    <div class="map-canvas" data-map-point data-lat="{{ $latId }}" data-lng="{{ $lngId }}" role="application" aria-label="{{ $label }}"></div>
    <p class="form-hint !mt-0">{{ $hint ?? __('admin.form.map_point_hint') }}</p>
    <div class="grid grid-cols-2 gap-3">
        <x-admin.form.input :name="$latName" :id="$latId" :label="__('admin.form.lat')" :value="$lat" type="number" step="0.0000001" min="-90" max="90" :required="$required" dir="ltr" />
        <x-admin.form.input :name="$lngName" :id="$lngId" :label="__('admin.form.lng')" :value="$lng" type="number" step="0.0000001" min="-180" max="180" :required="$required" dir="ltr" />
    </div>
</fieldset>
