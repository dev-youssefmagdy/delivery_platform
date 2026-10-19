@props(['name', 'label' => null, 'hint' => null, 'value' => null, 'required' => false])
{{-- Leaflet.draw polygon; submits `name` as a JSON list of [lat, lng] points. --}}
@php
    $id = \App\Support\Admin\FieldName::id($name);
    $json = is_array($value) ? json_encode($value) : (string) $value;
@endphp
<x-admin.form.field :name="$name" :label="$label" :hint="$hint ?? __('admin.form.polygon_hint')" :id="$id" :required="$required" :label-for="false" {{ $attributes }}>
    <div class="map-canvas h-80" data-map-polygon data-input="{{ $id }}" role="application" aria-labelledby="{{ $id }}-label"></div>
    <input type="hidden" name="{{ $name }}" id="{{ $id }}" value="{{ $json }}">
</x-admin.form.field>
