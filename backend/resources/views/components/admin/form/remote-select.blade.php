@props(['name', 'lookup', 'label' => null, 'hint' => null, 'selected' => [], 'id' => null, 'multiple' => false, 'placeholder' => null, 'wrapperClass' => null])
{{-- Tom Select backed by /admin/lookups/{lookup}. `selected`: [id => label] of the current value(s). --}}
@php
    $id ??= \App\Support\Admin\FieldName::id($name);
    $required = (bool) $attributes->get('required', false);
@endphp
<x-admin.form.field :name="$name" :label="$label" :hint="$hint" :id="$id" :required="$required" :class="$wrapperClass">
    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($multiple) multiple @endif
        data-remote-select
        data-lookup-url="{{ route('admin.lookups', $lookup) }}"
        data-placeholder="{{ $placeholder ?? __('admin.form.search_placeholder') }}"
        @if ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->class(['form-control']) }}
    >
        @if (! $multiple)
            <option value=""></option>
        @endif
        @foreach ($selected as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" selected>{{ $optionLabel }}</option>
        @endforeach
    </select>
</x-admin.form.field>
