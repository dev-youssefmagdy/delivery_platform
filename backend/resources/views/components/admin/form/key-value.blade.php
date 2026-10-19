@props(['name', 'label' => null, 'hint' => null, 'value' => [], 'keyLabel' => null, 'valueLabel' => null, 'max' => 50])
{{-- Key/value pairs (submits name[i][key], name[i][value]); `value` may be an associative array or a list of pairs. --}}
@php
    $pairs = [];
    foreach ((array) $value as $k => $v) {
        $pairs[] = is_array($v) && array_key_exists('key', $v) ? $v : ['key' => (string) $k, 'value' => is_scalar($v) ? (string) $v : json_encode($v)];
    }
@endphp
<x-admin.form.repeater
    :name="$name"
    :label="$label"
    :hint="$hint"
    :items="$pairs"
    :max="$max"
    :fields="[
        ['name' => 'key', 'label' => $keyLabel ?? __('admin.form.key'), 'required' => true, 'attributes' => ['maxlength' => 100, 'dir' => 'ltr']],
        ['name' => 'value', 'label' => $valueLabel ?? __('admin.form.value'), 'attributes' => ['maxlength' => 500]],
    ]"
    {{ $attributes }}
/>
